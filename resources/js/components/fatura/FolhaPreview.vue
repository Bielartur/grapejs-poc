<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';

/**
 * Uma página do layout, recortada em folhas A4 de verdade.
 *
 * Cada folha é uma janela de 794×1123 com `overflow: hidden` sobre o
 * mesmo documento, deslocado pra cima em múltiplos da altura da folha.
 * Assim o preview nunca mostra uma página mais alta do que o papel: onde
 * o Chromium quebraria, aqui a folha acaba.
 *
 * O documento é o mesmo em todos os iframes — é sempre o HTML que o
 * Blade produziu. O componente não sabe nada sobre faturas: mede
 * retângulos, recorta e desenha marcações por cima.
 */

const props = defineProps<{
    html: string;
    /** 1-based, só para rotular. */
    numero: number;
    selecionado: string | null;
}>();

const emit = defineEmits<{
    /** Quantas folhas de PDF esta página ocupa. */
    medido: [folhas: number];
    selecionar: [id: string | null];
}>();

/**
 * A4 a 96dpi. O iframe é fixado nesta largura pro documento ser medido
 * na mesma caixa em que o Chromium vai paginar — medir numa largura
 * qualquer daria contagem de folhas errada.
 */
const LARGURA_A4 = 794;
const ALTURA_A4 = 1123;

const conteudo = ref(ALTURA_A4);

/**
 * Onde cada folha começa, em pixels do topo do documento. Não são
 * múltiplos de `ALTURA_A4`: uma folha termina antes do fim do papel
 * quando o próximo bloco não caberia inteiro.
 */
const cortes = ref<number[]>([0]);

const destaque = ref<{ topo: number; altura: number } | null>(null);

let pendente: number | undefined;

/**
 * Documento da primeira folha, guardado no `load` em vez de por
 * template ref: dentro de um `v-for` o ref viraria um array, e só a
 * primeira folha interessa — as outras são o mesmo documento.
 */
let referencia: Document | null = null;

/**
 * Simula a paginação do Chromium sob `break-inside: avoid`.
 *
 * Percorre os blocos na ordem: enquanto o fim do bloco couber dentro da
 * folha corrente, segue; quando não couber, a folha termina ali e a
 * próxima começa no TOPO desse bloco — ele desce inteiro, sem ser
 * partido ao meio.
 *
 * A exceção é um bloco mais alto que a folha inteira: aí não há para
 * onde empurrar e ele parte mesmo, em folhas cheias. O navegador faz
 * exatamente isso quando `break-inside: avoid` é impossível de honrar.
 */
function calcularCortes(doc: Document, total: number): number[] {
    /*
     * As unidades MAIS INTERNAS. Um bloco inteiro é marcado, mas os
     * grandes marcam pedaços por dentro (uma UC no histórico, a tabela
     * do detalhamento). Se a gente paginasse pelas externas, um bloco
     * mais alto que a folha voltaria a cortar em qualquer lugar — é
     * justamente o caso que motivou a marcação fina.
     */
    const unidades = [
        ...doc.querySelectorAll<HTMLElement>('[data-unidade]'),
    ].filter((el) => !el.querySelector('[data-unidade]'));

    const inicios = [0];
    const ultimo = () => inicios[inicios.length - 1];

    for (const unidade of unidades) {
        const topo = unidade.offsetTop;
        const fim = topo + unidade.offsetHeight;

        if (fim - ultimo() <= ALTURA_A4) {
            continue;
        }

        // não coube: a folha corrente termina onde esta unidade começa,
        // e ela vai inteira pra próxima
        if (topo > ultimo()) {
            inicios.push(topo);
        }

        // mais alta que uma folha: não há para onde empurrar, então
        // parte em folhas cheias — o navegador faz o mesmo quando
        // `break-inside: avoid` é impossível de honrar
        while (fim - ultimo() > ALTURA_A4) {
            inicios.push(ultimo() + ALTURA_A4);
        }
    }

    // sobra depois da última unidade (padding do rodapé da página)
    while (total - ultimo() > ALTURA_A4) {
        inicios.push(ultimo() + ALTURA_A4);
    }

    return inicios;
}

function medir() {
    const doc = referencia;

    if (!doc?.body) {
        return;
    }

    // a altura do iframe não influencia isto: o corpo do documento não
    // tem altura relativa, então `scrollHeight` é o conteúdo de fato
    conteudo.value = doc.body.scrollHeight;
    cortes.value = calcularCortes(doc, conteudo.value);

    emit('medido', cortes.value.length);
    posicionarDestaque();
}

/**
 * Localiza o bloco selecionado pelo `data-bloco` e lê a posição dele. É
 * o que dispensa replicar a marcação em componente Vue só pra conseguir
 * desenhar o contorno.
 */
function posicionarDestaque() {
    const doc = referencia;

    if (!doc || !props.selecionado) {
        destaque.value = null;

        return;
    }

    const el = doc.querySelector<HTMLElement>(
        `[data-bloco="${props.selecionado}"]`,
    );

    destaque.value = el
        ? { topo: el.offsetTop, altura: el.offsetHeight }
        : null;
}

/**
 * `folha` é 1-based. Só a primeira mede — as outras são o mesmo
 * documento deslocado e mediriam exatamente o mesmo valor.
 */
function aoCarregar(evento: Event, folha: number) {
    const doc = (evento.target as HTMLIFrameElement).contentDocument;

    if (!doc) {
        return;
    }

    // clicar no documento seleciona o bloco correspondente na lista;
    // vale em qualquer folha, não só na primeira
    doc.addEventListener('click', (clique) => {
        const alvo = (clique.target as HTMLElement | null)?.closest?.(
            '[data-bloco]',
        );

        emit('selecionar', alvo?.getAttribute('data-bloco') ?? null);
    });

    if (folha !== 1) {
        return;
    }

    referencia = doc;
    medir();

    // fontes e imagens chegam depois do load e mudam a altura
    window.clearTimeout(pendente);
    pendente = window.setTimeout(medir, 400);
}

/** Deslocamento do documento dentro da janela de uma folha (1-based). */
const deslocamento = (folha: number) => -cortes.value[folha - 1];

/**
 * Altura visível de uma folha: até onde o próximo corte manda, nunca
 * até o fim do papel.
 *
 * Sem isto o trecho entre o corte e o fim da folha aparecia DUAS vezes —
 * no rodapé de uma folha e no topo da seguinte —, porque a janela tinha
 * sempre uma A4 de altura mesmo quando o corte vinha antes. O resto do
 * papel fica em branco, que é exatamente o que a quebra de página faz.
 */
function alturaVisivel(folha: number): number {
    const inicio = cortes.value[folha - 1];
    const fim = cortes.value[folha] ?? conteudo.value;

    return Math.min(ALTURA_A4, fim - inicio);
}

watch(() => props.selecionado, posicionarDestaque);

onBeforeUnmount(() => window.clearTimeout(pendente));
</script>

<template>
    <div class="flex flex-col items-center gap-2">
        <span
            class="rounded-full border bg-background px-2.5 py-0.5 text-[11px] whitespace-nowrap text-muted-foreground"
        >
            Página {{ numero }}
            <template v-if="cortes.length > 1">
                · {{ cortes.length }} folhas no PDF
            </template>
        </span>

        <div class="flex flex-col items-center gap-4">
            <div
                v-for="folha in cortes.length"
                :key="folha"
                class="relative bg-white shadow-lg ring-1 ring-black/10"
                :style="{ width: `${LARGURA_A4}px`, height: `${ALTURA_A4}px` }"
            >
                <!--
                    Janela do trecho que cabe nesta folha. O papel tem
                    sempre uma A4; a janela pode ser menor, e o que sobra
                    fica em branco — igual à quebra de página real.
                -->
                <div
                    class="relative overflow-hidden"
                    :style="{ height: `${alturaVisivel(folha)}px` }"
                >
                    <iframe
                        :srcdoc="html"
                        :title="`Página ${numero}, folha ${folha} da fatura`"
                        scrolling="no"
                        class="absolute left-0 w-full border-0"
                        :style="{
                            top: `${deslocamento(folha)}px`,
                            height: `${Math.max(conteudo, ALTURA_A4)}px`,
                        }"
                        @load="aoCarregar($event, folha)"
                    />
                </div>

                <!-- camada de marcação: nada aqui entra no PDF -->
                <div
                    class="pointer-events-none absolute inset-0 overflow-hidden"
                >
                    <div
                        v-if="destaque"
                        class="absolute right-0 left-0 rounded-sm bg-primary/5 ring-2 ring-primary"
                        :style="{
                            top: `${destaque.topo + deslocamento(folha)}px`,
                            height: `${destaque.altura}px`,
                        }"
                    />

                    <span
                        v-if="cortes.length > 1"
                        class="absolute right-2 bottom-2 rounded bg-muted px-1.5 py-0.5 text-[10px] text-muted-foreground"
                    >
                        folha {{ folha }}/{{ cortes.length }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
