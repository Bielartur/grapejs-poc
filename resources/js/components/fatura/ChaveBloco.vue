<script setup lang="ts">
/**
 * Toggle. Existe como componente próprio porque aparece em dois níveis
 * (o bloco e cada opção dele) e o `ui/` do projeto não tem switch.
 */

defineProps<{ modelValue: boolean; desabilitado?: boolean; rotulo: string }>();

const emit = defineEmits<{ 'update:modelValue': [valor: boolean] }>();
</script>

<template>
    <button
        type="button"
        role="switch"
        :aria-checked="modelValue"
        :aria-label="rotulo"
        :disabled="desabilitado"
        class="relative inline-flex h-5 w-9 shrink-0 items-center rounded-full transition-colors disabled:cursor-not-allowed disabled:opacity-40"
        :class="modelValue ? 'bg-primary' : 'bg-input'"
        @click="emit('update:modelValue', !modelValue)"
    >
        <span
            class="inline-block size-4 rounded-full bg-white shadow-sm transition-transform"
            :class="modelValue ? 'translate-x-4.5' : 'translate-x-0.5'"
        />
    </button>
</template>
