<script setup lang="ts">
import {
    Barcode,
    Building2,
    ChevronDown,
    FileText,
    GripVertical,
    History,
    LineChart,
    Paperclip,
    QrCode,
    Receipt,
    Table,
    User,
} from '@lucide/vue';
import type { Component as ComponenteVue } from '@lucide/vue';
import { ref } from 'vue';
import ChaveBloco from '@/components/fatura/ChaveBloco.vue';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    BlocoCatalogo,
    Layout,
    Posicao,
    ValorOpcao,
} from '@/composables/useLayoutFatura';

/**
 * A lista de blocos. É aqui que mora TODA a interação do editor — o
 * preview ao lado é somente leitura, o que dispensa ter versão Vue da
 * fatura e mantém o Blade como fonte única da marcação.
 *
 * O arraste usa a API nativa do HTML5, sem biblioteca: são poucas
 * dezenas de itens numa lista vertical, e o `dataTransfer` só precisa
 * carregar a posição de origem.
 */

const props = defineProps<{
    layout: Layout;
    catalogo: Record<string, BlocoCatalogo>;
    /** Quantas folhas de PDF cada página ocupa, medido no preview. */
    folhasPorPagina: number[];
    selecionado: string | null;
}>();

const emit = defineEmits<{
    alternarAtivo: [pos: Posicao];
    definirOpcao: [pos: Posicao, chave: string, valor: ValorOpcao];
    mover: [de: Posicao, para: Posicao];
    novaPagina: [de: Posicao];
    selecionar: [id: string | null];
}>();

const ICONES: Record<string, ComponenteVue> = {
    building: Building2,
    user: User,
    receipt: Receipt,
    'qr-code': QrCode,
    barcode: Barcode,
    'file-text': FileText,
    'line-chart': LineChart,
    table: Table,
    history: History,
    paperclip: Paperclip,
};

const expandido = ref<string | null>(null);
const arrastando = ref<Posicao | null>(null);
const alvo = ref<Posicao | null>(null);

function alternarExpansao(id: string) {
    expandido.value = expandido.value === id ? null : id;
}

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

function opcoesDo(id: string) {
    return Object.entries(props.catalogo[id]?.opcoes ?? {});
}
</script>

<template>
    <div class="flex flex-col gap-4" @dragend="soltar">
        <div>
            <h2 class="text-sm font-semibold">Blocos da fatura</h2>
            <p class="text-xs leading-relaxed text-muted-foreground">
                Arraste pela alça para reordenar — inclusive de uma página para
                outra.
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
                    !catalogo[bloco.id]?.disponivel ? 'opacity-60' : '',
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
                        :is="ICONES[catalogo[bloco.id]?.icone] ?? FileText"
                        class="size-4 shrink-0 text-muted-foreground"
                    />

                    <button
                        type="button"
                        class="min-w-0 flex-1 text-left"
                        @click="
                            emit(
                                'selecionar',
                                selecionado === bloco.id ? null : bloco.id,
                            )
                        "
                    >
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
                            class="block text-xs leading-snug text-muted-foreground"
                        >
                            {{ catalogo[bloco.id]?.descricao }}
                        </span>
                    </button>

                    <ChaveBloco
                        :model-value="bloco.ativo"
                        :desabilitado="!catalogo[bloco.id]?.disponivel"
                        :rotulo="`Exibir ${catalogo[bloco.id]?.rotulo}`"
                        @update:model-value="
                            emit('alternarAtivo', { pagina, indice })
                        "
                    />

                    <button
                        v-if="opcoesDo(bloco.id).length"
                        type="button"
                        class="text-muted-foreground transition-transform"
                        :class="expandido === bloco.id ? 'rotate-180' : ''"
                        aria-label="Opções do bloco"
                        @click="alternarExpansao(bloco.id)"
                    >
                        <ChevronDown class="size-4" />
                    </button>
                </div>

                <div
                    v-if="expandido === bloco.id && opcoesDo(bloco.id).length"
                    class="flex flex-col gap-2.5 border-t bg-muted/30 px-3 py-2.5"
                >
                    <p
                        class="text-[10px] font-semibold tracking-wider text-muted-foreground uppercase"
                    >
                        Opções do bloco
                    </p>

                    <div
                        v-for="[chave, definicao] in opcoesDo(bloco.id)"
                        :key="chave"
                        class="flex items-center justify-between gap-3"
                    >
                        <Label class="text-xs font-normal">{{
                            definicao.rotulo
                        }}</Label>

                        <ChaveBloco
                            v-if="definicao.tipo === 'bool'"
                            :model-value="Boolean(bloco.opcoes[chave])"
                            :rotulo="definicao.rotulo"
                            @update:model-value="
                                emit(
                                    'definirOpcao',
                                    { pagina, indice },
                                    chave,
                                    $event,
                                )
                            "
                        />

                        <Select
                            v-else
                            :model-value="String(bloco.opcoes[chave])"
                            @update:model-value="
                                emit(
                                    'definirOpcao',
                                    { pagina, indice },
                                    chave,
                                    String($event),
                                )
                            "
                        >
                            <SelectTrigger class="h-7 w-40 text-xs">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="(rotulo, valor) in definicao.valores"
                                    :key="valor"
                                    :value="String(valor)"
                                >
                                    {{ rotulo }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
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
