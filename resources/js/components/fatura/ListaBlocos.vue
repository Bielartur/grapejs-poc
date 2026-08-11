<script setup lang="ts">
import { ChevronRight, GripVertical } from '@lucide/vue';
import { ref } from 'vue';
import { iconeDoBloco } from '@/components/fatura/icones';
import { Badge } from '@/components/ui/badge';
import type {
    BlocoCatalogo,
    Layout,
    Posicao,
} from '@/composables/useLayoutFatura';

/**
 * O PRIMEIRO NÍVEL DA SIDEBAR
 *
 * Aqui mora só a ESTRUTURA do documento: que blocos existem, em que
 * ordem e em qual página. Escolher a forma de cada um é assunto da
 * subpágina (`PainelVariantes`), aberta ao clicar na linha.
 *
 * Os interruptores saíram daqui. Ligar e desligar virou a escolha "sem
 * nenhum" na lista de variantes, o que deixou esta lista com uma
 * responsabilidade só — e cada linha passou a poder dizer QUAL forma
 * está em uso, informação que o interruptor não tinha como dar.
 *
 * O arraste usa a API nativa do HTML5, sem biblioteca: são poucas
 * dezenas de itens numa lista vertical, e o `dataTransfer` só precisa
 * carregar a posição de origem.
 */

defineProps<{
    layout: Layout;
    catalogo: Record<string, BlocoCatalogo>;
    /** Quantas folhas de PDF cada página ocupa, medido no preview. */
    folhasPorPagina: number[];
    selecionado: string | null;
}>();

const emit = defineEmits<{
    abrir: [pos: Posicao];
    mover: [de: Posicao, para: Posicao];
    novaPagina: [de: Posicao];
}>();

const arrastando = ref<Posicao | null>(null);
const alvo = ref<Posicao | null>(null);

function iniciarArraste(evento: DragEvent, pos: Posicao) {
    arrastando.value = pos;
    // o HTML5 aborta um dragstart que não escreve nada no dataTransfer
    evento.dataTransfer?.setData('text/plain', `${pos.pagina}:${pos.indice}`);

    if (evento.dataTransfer) {
        evento.dataTransfer.effectAllowed = 'move';
    }
}

/**
 * Marca a posição de inserção conforme o cursor esteja na metade de cima
 * ou de baixo da linha — é o que permite soltar no fim de uma página sem
 * precisar de uma zona de drop separada.
 */
function sobrepor(evento: DragEvent, pos: Posicao) {
    evento.preventDefault();

    const linha = evento.currentTarget as HTMLElement;
    const meio = linha.getBoundingClientRect().top + linha.offsetHeight / 2;

    alvo.value = {
        pagina: pos.pagina,
        indice: evento.clientY < meio ? pos.indice : pos.indice + 1,
    };
}

function soltar() {
    if (arrastando.value && alvo.value) {
        emit('mover', arrastando.value, alvo.value);
    }

    arrastando.value = null;
    alvo.value = null;
}

const ehAlvo = (pagina: number, indice: number) =>
    alvo.value?.pagina === pagina && alvo.value?.indice === indice;

/** O que a linha diz embaixo do nome: a forma em uso, ou que não há. */
function resumo(definicao: BlocoCatalogo | undefined, variante: string | null) {
    if (!definicao) {
        return '';
    }

    if (!variante) {
        return definicao.rotulo_vazio ?? `Sem nenhum ${definicao.rotulo}`;
    }

    return definicao.variantes[variante]?.rotulo ?? variante;
}
</script>

<template>
    <div class="flex flex-col gap-4" @dragend="soltar">
        <div>
            <h2 class="text-sm font-semibold">Blocos da fatura</h2>
            <p class="text-xs leading-relaxed text-muted-foreground">
                Clique em um bloco para escolher o componente. Arraste pela alça
                para reordenar — inclusive de uma página para outra.
            </p>
        </div>

        <div
            v-for="(blocos, pagina) in layout.paginas"
            :key="`pagina-${pagina}`"
            class="flex flex-col gap-1.5"
        >
            <div class="flex items-baseline justify-between px-1">
                <span
                    class="text-[11px] font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    Página {{ pagina + 1 }}
                </span>
                <span class="text-[11px] text-muted-foreground">
                    {{ blocos.length }}
                    {{ blocos.length === 1 ? 'bloco' : 'blocos' }} ·
                    {{ folhasPorPagina[pagina] ?? 1 }}
                    {{
                        (folhasPorPagina[pagina] ?? 1) === 1
                            ? 'folha'
                            : 'folhas'
                    }}
                </span>
            </div>

            <div
                v-for="(bloco, indice) in blocos"
                :key="bloco.id"
                class="rounded-lg border transition-colors"
                :class="[
                    selecionado === bloco.id
                        ? 'border-primary ring-1 ring-primary'
                        : 'border-border',
                    ehAlvo(pagina, indice) ? 'border-t-2 border-t-primary' : '',
                    // sem variante o bloco não sai no PDF: a linha fica
                    // apagada, do mesmo jeito que um bloco indisponível
                    !bloco.variante ? 'opacity-60' : '',
                ]"
                @dragover="sobrepor($event, { pagina, indice })"
                @drop.prevent="soltar"
            >
                <div class="flex items-center gap-2 p-2.5">
                    <button
                        type="button"
                        draggable="true"
                        class="cursor-grab text-muted-foreground active:cursor-grabbing"
                        aria-label="Reordenar bloco"
                        @dragstart="iniciarArraste($event, { pagina, indice })"
                    >
                        <GripVertical class="size-4" />
                    </button>

                    <component
                        :is="iconeDoBloco(catalogo[bloco.id]?.icone)"
                        class="size-4 shrink-0 text-muted-foreground"
                    />

                    <button
                        type="button"
                        class="flex min-w-0 flex-1 items-center gap-2 text-left"
                        @click="emit('abrir', { pagina, indice })"
                    >
                        <span class="min-w-0 flex-1">
                            <span class="flex items-center gap-1.5">
                                <span class="text-sm font-medium">
                                    {{ catalogo[bloco.id]?.rotulo ?? bloco.id }}
                                </span>
                                <Badge
                                    v-if="!catalogo[bloco.id]?.disponivel"
                                    variant="secondary"
                                    class="px-1.5 py-0 text-[10px]"
                                >
                                    Em breve
                                </Badge>
                            </span>
                            <span
                                class="block truncate text-xs leading-snug text-muted-foreground"
                            >
                                {{ resumo(catalogo[bloco.id], bloco.variante) }}
                            </span>
                        </span>

                        <ChevronRight
                            class="size-4 shrink-0 text-muted-foreground"
                        />
                    </button>
                </div>
            </div>

            <!-- soltar aqui acrescenta ao fim da página, mesmo ela vazia -->
            <div
                class="h-2 rounded transition-colors"
                :class="ehAlvo(pagina, blocos.length) ? 'bg-primary/40' : ''"
                @dragover.prevent="alvo = { pagina, indice: blocos.length }"
                @drop.prevent="soltar"
            />
        </div>

        <button
            type="button"
            class="rounded-lg border border-dashed py-2 text-xs text-muted-foreground transition-colors hover:border-primary hover:text-foreground"
            @dragover.prevent
            @drop.prevent="arrastando && emit('novaPagina', arrastando)"
        >
            Arraste um bloco aqui para criar uma página
        </button>
    </div>
</template>
