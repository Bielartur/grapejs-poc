<script setup lang="ts">
import { dinheiro } from '@/lib/formato';
import type { DadosFatura, ItemFatura, OpcoesBloco } from '@/types/fatura';

/**
 * Variante `resumida` do detalhamento: uma linha por unidade, sem abrir
 * os subitens.
 *
 * É a forma para quem manda a fatura junto das contas da concessionária
 * — o detalhe já está no anexo, e aqui basta o fechamento por UC.
 *
 * `coluna_desconto` continua valendo: sem os subitens não há uma coluna
 * de desconto por linha, então a coluna mostra os descontos distintos
 * aplicados naquela unidade. Uma opção do bloco que não fizesse nada em
 * alguma variante pareceria defeito.
 */

defineProps<{ dados: DadosFatura; opcoes: OpcoesBloco }>();

function descontos(item: ItemFatura): string {
    const rotulos = item.invoiceSubitems
        .filter((s) => s.chargeable && s.discount_label)
        .map((s) => s.discount_label);

    return [...new Set(rotulos)].join(' / ') || '—';
}
</script>

<template>
    <div>
        <h2 class="border-b pb-4 text-xl font-semibold text-gray-900">
            Detalhamento por Unidade Consumidora
        </h2>

        <div class="mt-6">
            <table
                data-unidade
                class="min-w-full table-fixed font-mono text-sm"
            >
                <colgroup>
                    <col style="width: 52%" />
                    <col style="width: 14%" />
                    <col v-if="opcoes.coluna_desconto" style="width: 14%" />
                    <col style="width: 20%" />
                </colgroup>

                <thead class="bg-gray-100">
                    <tr>
                        <th
                            class="px-4 py-2 text-left text-xs font-semibold text-gray-900"
                        >
                            Unidade consumidora
                        </th>
                        <th
                            class="px-4 py-2 text-right text-xs font-semibold text-gray-900"
                        >
                            Nº de dias
                        </th>
                        <th
                            v-if="opcoes.coluna_desconto"
                            class="px-4 py-2 text-right text-xs font-semibold text-gray-900"
                        >
                            Desconto
                        </th>
                        <th
                            class="px-4 py-2 text-right text-xs font-semibold text-gray-900"
                        >
                            Valor
                        </th>
                    </tr>
                </thead>

                <tbody class="bg-white">
                    <tr
                        v-for="item in dados.invoice_items"
                        :key="item.id"
                        class="border-t border-gray-300"
                    >
                        <td class="px-4 py-2 text-left">
                            <div class="flex flex-col">
                                <span>{{ item.title }}</span>

                                <span
                                    v-if="
                                        opcoes.apelido_unidade &&
                                        item.consumerUnit.alias
                                    "
                                    class="text-xs font-light text-gray-400"
                                >
                                    {{ item.consumerUnit.alias }}
                                </span>

                                <span class="text-xs font-light text-gray-600">
                                    {{ item.subtitle }}
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-2 text-right text-sm text-gray-500">
                            {{ item.consumerUnitBill?.period_days ?? '-' }}
                        </td>
                        <td
                            v-if="opcoes.coluna_desconto"
                            class="px-4 py-2 text-right text-sm text-gray-500"
                        >
                            {{ descontos(item) }}
                        </td>
                        <td
                            class="px-4 py-2 text-right text-sm font-semibold text-gray-900"
                        >
                            {{ dinheiro(item.amount) }}
                        </td>
                    </tr>
                </tbody>

                <tfoot v-if="opcoes.linha_total" class="bg-white">
                    <tr class="border-t-2 border-black">
                        <td
                            class="px-4 py-2 text-left font-bold text-gray-900"
                            :colspan="opcoes.coluna_desconto ? 3 : 2"
                        >
                            Total
                        </td>
                        <td
                            class="px-4 py-2 text-right font-bold text-gray-900"
                        >
                            {{ dinheiro(dados.invoice.amount) }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>
