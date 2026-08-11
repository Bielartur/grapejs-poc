<script setup lang="ts">
/**
 * A VARIANTE QUE AINDA NÃO EXISTE EM VUE
 *
 * O `<component :is>` renderiza vazio e cala quando o mapa não tem a
 * chave: uma variante declarada no catálogo sem componente registrado em
 * `documento/componentes.ts` não quebraria nada — a seção só sumiria do
 * documento, em silêncio, e ninguém perceberia até um cliente reclamar
 * de fatura sem cabeçalho.
 *
 * O teste de pareamento impede que isso chegue em produção; este
 * componente é o que torna o buraco visível enquanto se desenvolve.
 */

defineProps<{ idBloco: string; variante: string }>();
</script>

<template>
    <div
        class="flex items-center gap-3 border border-dashed border-amber-400 bg-amber-50 px-4 py-3 text-amber-900"
    >
        <span class="font-mono text-xs font-semibold">
            {{ idBloco }}/{{ variante }}
        </span>
        <span class="text-xs">
            está no catálogo mas não tem componente Vue registrado em
            <code>documento/componentes.ts</code>.
        </span>
    </div>
</template>
