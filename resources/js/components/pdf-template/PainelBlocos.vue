<script setup lang="ts">
import { BLOCOS, type DefinicaoBloco } from '@/lib/pdf-template/blocos';

/**
 * A paleta de blocos — um componente Vue comum lendo um array TS.
 * O GrapesJS não sabe que ela existe: o vínculo é só o
 * `Canvas.startDrag()` no início do arraste.
 */

const emit = defineEmits<{
    arrastar: [conteudo: Record<string, unknown>];
    soltar: [];
    adicionar: [conteudo: Record<string, unknown>];
}>();

function aoIniciarArraste(evento: DragEvent, bloco: DefinicaoBloco) {
    // o GrapesJS posiciona o indicador de drop a partir do mouse;
    // o dataTransfer só precisa existir pro HTML5 permitir o arraste
    evento.dataTransfer?.setData('text/plain', bloco.id);
    emit('arrastar', bloco.conteudo);
}
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <button
            v-for="bloco in BLOCOS"
            :key="bloco.id"
            type="button"
            draggable="true"
            class="flex cursor-grab items-center gap-2.5 rounded-lg border bg-card px-3 py-2 text-left text-sm transition-colors hover:border-primary hover:bg-accent active:cursor-grabbing"
            @dragstart="aoIniciarArraste($event, bloco)"
            @dragend="emit('soltar')"
            @click="emit('adicionar', bloco.conteudo)"
        >
            <component :is="bloco.icone" class="size-4 text-muted-foreground" />
            {{ bloco.rotulo }}
        </button>

        <p class="mt-1 text-xs leading-relaxed text-muted-foreground">
            Arraste para posicionar, ou clique para adicionar ao fim.
        </p>
    </div>
</template>
