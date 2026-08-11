<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import DocumentoFatura from '@/components/fatura/documento/DocumentoFatura.vue';
import type { BlocoLayout, Tema } from '@/composables/useLayoutFatura';
import type { DadosFatura } from '@/types/fatura';

/**
 * Uma página do layout, recortada em folhas A4 de verdade.
 *
 * Cada folha é uma janela de 794×1123 com `overflow: hidden` sobre o
 * mesmo documento, deslocado pra cima em múltiplos da altura da folha.
 * Assim o preview nunca mostra uma página mais alta do que o papel: onde
 * o Chromium quebraria, aqui a folha acaba.
 *
 * O documento é o mesmo em todas as folhas — os componentes de
 * `documento/`, exatamente os que o Browsershot vai renderizar. Este
 * componente não sabe nada sobre faturas: mede retângulos, recorta e
 * desenha marcações por cima.
 */

const props = defineProps<{
    blocos: BlocoLayout[];
    tema: Tema;
    dados: DadosFatura;
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
 * A4 a 96dpi. A largura é fixada pro documento ser medido na mesma caixa
 * em que o Chromium vai paginar — medir noutra largura daria contagem de
 * folhas errada.
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

/**
 * A primeira folha é quem mede. Ela é a única com deslocamento zero, o
 * que faz o `offsetTop` dos elementos lá dentro já ser a posição no
 * documento — nas outras viria somado ao deslocamento.
 */
const raiz = ref<HTMLElement | null>(null);

let observador: ResizeObserver | null = null;

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
 *
 * Usa `offsetTop`/`offsetHeight`, não `getBoundingClientRect`: o zoom do
 * editor aplica `transform: scale()`, que escala os rects mas não os
 * valores de layout. Com rects, a contagem de folhas mudaria junto com o
 * zoom.
 */
function calcularCortes(raizEl: HTMLElement, total: number): number[] {
    /*
     * As unidades MAIS INTERNAS. Um bloco inteiro é marcado, mas os
     * grandes marcam pedaços por dentro (uma UC no histórico, a tabela
     * do detalhamento). Se a gente paginasse pelas externas, um bloco
     * mais alto que a folha voltaria a cortar em qualquer lugar — é
     * justamente o caso que motivou a marcação fina.
     */
    const unidades = [
        ...raizEl.querySelectorAll<HTMLElement>('[data-unidade]'),
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
        // parte em folhas cheias
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
    const el = raiz.value;

    if (!el) {
        return;
    }

    conteudo.value = el.offsetHeight;

    const novos = calcularCortes(el, conteudo.value);

    /*
     * Só reatribui quando muda de verdade. Mais folhas mudam o DOM, o
     * `ResizeObserver` reage e chamaria `medir()` de novo — sem esta
     * comparação, isso seria um laço.
     */
    if (novos.join() !== cortes.value.join()) {
        cortes.value = novos;
    }

    emit('medido', cortes.value.length);
    posicionarDestaque();
}

/**
 * Localiza o bloco selecionado pelo `data-bloco` e lê a posição dele.
 * Continua por consulta ao DOM, e não por refs espalhados pelos nove
 * componentes: o destaque é do editor, não do documento, e mantê-lo
 * assim deixa os blocos sem saber que existe um editor.
 */
function posicionarDestaque() {
    const el = raiz.value;

    if (!el || !props.selecionado) {
        destaque.value = null;

        return;
    }

    const alvo = el.querySelector<HTMLElement>(
        `[data-bloco="${props.selecionado}"]`,
    );

    destaque.value = alvo
        ? { topo: alvo.offsetTop, altura: alvo.offsetHeight }
        : null;
}

/** Clicar no documento seleciona o bloco correspondente na lista. */
function aoClicar(evento: MouseEvent) {
    const alvo = (evento.target as HTMLElement | null)?.closest?.(
        '[data-bloco]',
    );

    emit('selecionar', alvo?.getAttribute('data-bloco') ?? null);
}

/** Deslocamento do documento dentro da janela de uma folha (1-based). */
const deslocamento = (folha: number) => -cortes.value[folha - 1];

/**
 * Altura visível de uma folha: até onde o próximo corte manda, nunca
 * até o fim do papel.
 *
 * Sem isto o trecho entre o corte e o fim da folha apareceria DUAS vezes
 * — no rodapé de uma folha e no topo da seguinte. O resto do papel fica
 * em branco, que é exatamente o que a quebra de página faz.
 */
function alturaVisivel(folha: number): number {
    const inicio = cortes.value[folha - 1];
    const fim = cortes.value[folha] ?? conteudo.value;

    return Math.min(ALTURA_A4, fim - inicio);
}

/*
 * O `ResizeObserver` substitui o `setTimeout(400)` da versão em iframe:
 * fontes e imagens chegavam depois do `load` e mudavam a altura, e a
 * espera fixa era um chute. Agora a medida refaz quando o documento
 * muda de tamanho de verdade.
 */
watch(raiz, (el) => {
    observador?.disconnect();

    if (!el) {
        return;
    }

    observador = new ResizeObserver(() => medir());
    observador.observe(el);
});

// mudar bloco, opção ou tema muda o layout sem necessariamente mudar a
// altura total — daí remedir também na mudança de props
watch(
    () => [props.blocos, props.tema, props.dados],
    () => void nextTick(medir),
    { deep: true },
);

watch(() => props.selecionado, posicionarDestaque);

onMounted(() => void nextTick(medir));
onBeforeUnmount(() => observador?.disconnect());
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
                    <div
                        :ref="
                            (el) => {
                                if (folha === 1) {
                                    raiz = el as HTMLElement | null;
                                }
                            }
                        "
                        class="absolute left-0"
                        :style="{
                            top: `${deslocamento(folha)}px`,
                            width: `${LARGURA_A4}px`,
                        }"
                        @click="aoClicar"
                    >
                        <DocumentoFatura
                            :paginas="[blocos]"
                            :tema="tema"
                            :dados="dados"
                        />
                    </div>
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
