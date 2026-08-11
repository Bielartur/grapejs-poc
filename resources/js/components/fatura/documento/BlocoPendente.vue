<script setup lang="ts">
/**
 * O BLOCO QUE AINDA NÃO EXISTE EM VUE
 *
 * Durante as Fases 1 e 2 este componente é carga estrutural, não
 * enfeite: a maior parte dos blocos ainda mora no Blade, e um buraco
 * silencioso faria a comparação entre as duas engines MENTIR — o
 * documento em Vue pareceria quase igual só porque o que falta some sem
 * deixar rastro.
 *
 * Terminada a migração ele continua, como guarda: um id que o
 * `CatalogoBlocos` conhece mas que não tem componente registrado aparece
 * aqui em vez de virar espaço em branco.
 */

defineProps<{ idBloco: string }>();
</script>

<template>
    <div
        class="flex items-center gap-3 border border-dashed border-amber-400 bg-amber-50 px-4 py-3 text-amber-900"
    >
        <span class="font-mono text-xs font-semibold">{{ idBloco }}</span>
        <span class="text-xs">
            ainda não tem componente Vue — este bloco só renderiza no preview em
            Blade.
        </span>
    </div>
</template>
