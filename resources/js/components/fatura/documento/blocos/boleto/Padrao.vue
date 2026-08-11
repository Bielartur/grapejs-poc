<script setup lang="ts">
import { dataBr, dinheiro } from '@/lib/formato';
import type { DadosFatura, OpcoesBloco } from '@/types/fatura';

/**
 * Porte de `resources/views/fatura/blocos/boleto.blade.php`.
 *
 * As grades `grid-cols-4` / `grid-cols-5` reproduzem o formato clássico
 * da ficha de compensação. Traduzi-las em outra primitiva não traria
 * nada e só afastaria o bloco do documento que ele imita.
 */

defineProps<{ dados: DadosFatura; opcoes: OpcoesBloco }>();
</script>

<template>
    <div>
        <div class="boleto-border flex items-center justify-between p-2 py-0.5">
            <div>
                <p class="text-lg font-bold">
                    {{ dados.bank.code ?? '000' }} |
                    {{ dados.bank.name ?? 'Banco não informado' }}
                </p>
            </div>
            <div class="text-right">
                <p class="text-xl font-bold">
                    <template v-if="dados.bank_billet">
                        {{ dados.boleto_serial_number }}
                    </template>
                </p>
            </div>
        </div>

        <div class="mt-1 grid grid-cols-4 gap-1">
            <div class="boleto-border col-span-3 p-2 py-0.5">
                <p>
                    <strong>Local de Pagamento:</strong> Pagável em qualquer
                    banco até o vencimento
                </p>
            </div>
            <div class="boleto-border p-2 py-0.5">
                <p>
                    <strong>Vencimento:</strong>
                    {{ dataBr(dados.invoice.due_date) }}
                </p>
            </div>
        </div>

        <div class="mt-1 grid grid-cols-4 gap-1">
            <div class="boleto-border col-span-3 p-2 py-0.5">
                <p>
                    <strong>Cedente:</strong> {{ dados.company.name }} - CNPJ:
                    {{ dados.company.document }}
                </p>
            </div>
        </div>

        <div class="mt-1 grid grid-cols-5 gap-1">
            <div class="boleto-border p-2 py-0.5">
                <p>
                    <strong>Data do Documento:</strong>
                    {{ dataBr(dados.invoice.issue_date) }}
                </p>
            </div>
            <div class="boleto-border p-2 py-0.5">
                <p>
                    <strong>Nº do Documento:</strong> {{ dados.invoice.code }}
                </p>
            </div>
            <div class="boleto-border p-2 py-0.5">
                <p><strong>Espécie Doc.:</strong></p>
            </div>
            <div class="boleto-border p-2 py-0.5">
                <p><strong>Aceite:</strong></p>
            </div>
            <div class="boleto-border p-2 py-0.5">
                <p>
                    <strong>Data Processamento:</strong>
                    {{ dataBr(dados.invoice.issue_date) }}
                </p>
            </div>
        </div>

        <div v-if="opcoes.mostrar_instrucoes" class="mt-1 grid grid-cols-1">
            <div class="boleto-border p-2 py-0.5">
                <p><strong>Instruções:</strong></p>
                <p>- Pagamento via Pix ou boleto até o vencimento.</p>
            </div>
        </div>

        <div class="mt-1 grid grid-cols-4 gap-1">
            <div class="boleto-border col-span-3 p-2 py-0.5">
                <p>
                    <strong>Sacado:</strong> {{ dados.customer.name }} - CPF:
                    {{ dados.customer.document }}
                </p>
                <p>
                    WhatsApp: {{ dados.customer.phone }} | Email:
                    {{ dados.customer.email }}
                </p>
            </div>
            <div class="boleto-border p-2 py-0.5">
                <p>
                    <strong>Valor Cobrado:</strong>
                    {{ dinheiro(dados.invoice.amount) }}
                </p>
            </div>
        </div>
    </div>
</template>
