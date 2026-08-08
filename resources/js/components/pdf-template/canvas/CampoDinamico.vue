<script setup lang="ts">
import { computed } from 'vue';
import { campoPorChave } from '@/lib/pdf-template/schema';

/**
 * Este componente é renderizado DENTRO do iframe do canvas do GrapesJS.
 * As classes Tailwind funcionam lá porque o composable espelha as folhas
 * de estilo do app para dentro do iframe.
 *
 * O ponto importante: o que o Vue desenha aqui é APENAS a aparência de
 * edição. O `editor.getHtml()` serializa o *model* do GrapesJS, que para
 * este tipo é só `<span data-bind="..."></span>` — vazio. Então esse
 * placeholder nunca vaza pro PDF.
 */

const props = defineProps<{ campo: string }>();

const info = computed(() => campoPorChave(props.campo));
</script>

<template>
    <span
        v-if="info"
        :title="`Vinculado a: ${info.label}`"
        class="inline-block rounded-xs bg-violet-500/10 px-1 text-[inherit] outline-1 outline-offset-1 outline-dashed outline-violet-500"
    >
        <span class="align-super text-[0.6em] text-violet-500">◆</span>
        {{ info.sample }}
    </span>

    <span
        v-else
        :title="`Campo inexistente no schema: ${campo}`"
        class="inline-block rounded-xs bg-red-500/10 px-1 text-[inherit] outline-1 outline-offset-1 outline-dashed outline-red-500"
    >
        <span class="align-super text-[0.6em] text-red-500">▲</span>
        {{ campo }}
    </span>
</template>
