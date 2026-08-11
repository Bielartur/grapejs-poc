<script setup lang="ts">
import type { DadosFatura, OpcoesBloco } from '@/types/fatura';

/**
 * Porte de `resources/views/fatura/blocos/pix.blade.php`.
 *
 * A condição externa (`$bank_billet && $bank_billet->pix`) é do
 * original e continua aqui: as opções do editor decidem o que MOSTRAR,
 * nunca inventam dado que não existe.
 */

defineProps<{ dados: DadosFatura; opcoes: OpcoesBloco }>();
</script>

<template>
    <div v-if="dados.bank_billet?.pix" class="flex justify-between gap-2">
        <div
            v-if="opcoes.mostrar_qrcode"
            class="boleto-border w-1/3 p-2 text-center"
        >
            <!-- O QR Code de verdade seria uma <img> gerada pelo backend.
                 Gerar imagem é exatamente o tipo de coisa que nunca vira
                 responsabilidade do editor. -->
            <div
                class="mx-auto flex h-32 max-w-32 items-center justify-center border border-gray-300"
            >
                <span class="text-xs text-gray-400">QR CODE</span>
            </div>
            <p class="mt-1">Pix QR Code</p>
        </div>

        <div v-if="opcoes.mostrar_copia_cola" class="boleto-border flex-1 p-2">
            <p><strong>Pix Copia e Cola:</strong></p>
            <div class="truncate">
                <span>{{ dados.bank_billet.pix }}</span>
            </div>
        </div>
    </div>
</template>
