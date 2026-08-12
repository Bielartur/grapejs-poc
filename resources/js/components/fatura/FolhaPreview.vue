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
    /** Elemento selecionado DENTRO do bloco, quando há um. */
    elementoSelecionado: string | null;
}>();

const emit = defineEmits<{
    /** Quantas folhas de PDF esta página ocupa. */
    medido: [folhas: number];
    selecionar: [id: string | null, elemento: string | null];
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
 * O elemento selecionado dentro do bloco. Guarda também largura e
 * esquerda, ao contrário do destaque do bloco: um bloco ocupa a linha
 * inteira, um elemento não — marcar a linha toda apontaria pro lugar
 * errado num cabeçalho de duas colunas.
 */
const destaqueElemento = ref<{
    topo: number;
    altura: number;
    largura: number;
    esquerda: number;
} | null>(null);

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
        destaqueElemento.value = null;

        return;
    }

    const alvo = el.querySelector<HTMLElement>(
        `[data-bloco="${props.selecionado}"]`,
    );

    destaque.value = alvo
        ? { topo: alvo.offsetTop, altura: alvo.offsetHeight }
        : null;

    destaqueElemento.value = medirElemento(alvo);
}

/**
 * O retângulo do elemento selecionado, em coordenadas do documento.
 *
 * `offsetTop` é relativo ao ancestral posicionado, e um elemento no meio
 * do bloco pode ter outro `offsetParent` que não a raiz — daí somar a
 * cadeia até chegar nela, em vez de ler o valor cru como o destaque do
 * bloco faz. O bloco é filho direto da raiz; o elemento não é.
 */
function medirElemento(
    bloco: HTMLElement | null,
): { topo: number; altura: number; largura: number; esquerda: number } | null {
    if (!bloco || !props.elementoSelecionado) {
        return null;
    }

    const alvo = bloco.querySelector<HTMLElement>(
        `[data-elemento="${props.elementoSelecionado}"]`,
    );

    if (!alvo) {
        return null;
    }

    let topo = 0;
    let esquerda = 0;
    let atual: HTMLElement | null = alvo;

    while (atual && atual !== raiz.value) {
        topo += atual.offsetTop;
        esquerda += atual.offsetLeft;
        atual = atual.offsetParent as HTMLElement | null;
    }

    return {
        topo,
        esquerda,
        altura: alvo.offsetHeight,
        largura: alvo.offsetWidth,
    };
}

/**
 * Clicar no documento seleciona o bloco E, quando o clique cai num
 * elemento marcado, o elemento dentro dele.
 *
 * Os dois `closest` sobem a partir do mesmo alvo, então clicar no nome
 * da empresa devolve `cabecalho` + `titulo`, e clicar no espaço entre
 * elementos devolve `cabecalho` + `null` — que é o que fecha o painel de
 * estilo sem fechar a seleção do bloco.
 */
function aoClicar(evento: MouseEvent) {
    const origem = evento.target as HTMLElement | null;
    const bloco = origem?.closest?.('[data-bloco]');
    const elemento = origem?.closest?.('[data-elemento]');

    emit(
        'selecionar',
        bloco?.getAttribute('data-bloco') ?? null,
        elemento?.getAttribute('data-elemento') ?? null,
    );
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

watch(() => [props.selecionado, props.elementoSelecionado], posicionarDestaque);

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
                        <!-- `editavel` liga o realce de hover dos
                             elementos; ver o <style> no fim do arquivo -->
                        <DocumentoFatura
                            class="editavel"
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
                    <!-- o bloco: contorno discreto, é só contexto -->
                    <div
                        v-if="destaque"
                        class="absolute right-0 left-0 rounded-sm"
                        :class="
                            elementoSelecionado
                                ? 'ring-1 ring-primary/30'
                                : 'bg-primary/5 ring-2 ring-primary'
                        "
                        :style="{
                            top: `${destaque.topo + deslocamento(folha)}px`,
                            height: `${destaque.altura}px`,
                        }"
                    />

                    <!-- o elemento: é ele que está sendo editado, então
                         ganha o destaque forte quando existe -->
                    <div
                        v-if="destaqueElemento"
                        class="absolute rounded-xs bg-primary/10 ring-2 ring-primary"
                        :style="{
                            top: `${destaqueElemento.topo + deslocamento(folha)}px`,
                            left: `${destaqueElemento.esquerda}px`,
                            width: `${destaqueElemento.largura}px`,
                            height: `${destaqueElemento.altura}px`,
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

<!--
    O REALCE DE HOVER É DO EDITOR, NÃO DO DOCUMENTO.

    Mora aqui, escopado, e não em `documento.css`: aquela folha é
    carregada TAMBÉM pela casca que o Browsershot abre, e uma regra de
    hover lá sairia no PDF (não visualmente, mas como CSS morto que
    alguém teria que explicar depois).

    `:deep()` é necessário porque os elementos marcados são renderizados
    pelos componentes de bloco, que estão fora do escopo deste arquivo.
-->
<style scoped>
.editavel :deep([data-elemento]) {
    cursor: pointer;
    transition: outline-color 120ms;
    outline: 1px dashed transparent;
    outline-offset: 2px;
}

.editavel :deep([data-elemento]:hover) {
    outline-color: var(--color-primary);
}
</style>
