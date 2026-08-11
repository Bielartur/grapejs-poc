<script setup lang="ts">
import type { DadosFatura, OpcoesBloco } from '@/types/fatura';

/**
 * Porte de `resources/views/fatura/blocos/codigo_barras.blade.php`.
 *
 * O código de barras de verdade seria um SVG gerado no backend. Aqui
 * usamos a `.barcode-placeholder` que o CSS do documento já define —
 * gerar imagem segue sendo trabalho do servidor, não do editor.
 */

defineProps<{ dados: DadosFatura; opcoes: OpcoesBloco }>();
</script>

<template>
    <div class="boleto-border p-2 py-0.5">
        <div class="mx-auto h-[75px] w-[600px]">
            <div class="barcode-placeholder w-full"></div>
        </div>

        <p
            v-if="opcoes.mostrar_linha_digitavel && dados.barcode_line"
            class="mt-1 text-center"
        >
            <strong>{{ dados.barcode_line }}</strong>
        </p>
    </div>
</template>
