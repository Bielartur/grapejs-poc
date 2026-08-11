<script setup lang="ts">
import { computed } from 'vue';
import { dinheiro } from '@/lib/formato';
import type { DadosFatura, OpcoesBloco } from '@/types/fatura';

/**
 * Porte de `resources/views/fatura/blocos/detalhamento_uc.blade.php`.
 *
 * Este é o bloco que motivou manter o documento em Blade: o `@foreach`
 * aninhado (itens → subitens) com `colspan` calculado era o exemplo de
 * "coisa que um editor visual de HTML não representaria". Em Vue ele
 * vira `v-for` aninhado e um `computed` — a lógica sobrevive inteira.
 *
 * `apelido_unidade` merece nota: é o tipo de decisão que costuma viver
 * espalhada como flag no banco. Trazê-la pro catálogo a transformou em
 * opção do bloco sem mudar nada de estrutura.
 */

const props = defineProps<{ dados: DadosFatura; opcoes: OpcoesBloco }>();

/** O cabeçalho da unidade ocupa todas as colunas menos a de valor. */
const colunasAntesDoValor = computed(() =>
    props.opcoes.coluna_desconto ? 4 : 3,
);
</script>

<template>
    <div>
        <h2 class="border-b pb-4 text-xl font-semibold text-gray-900">
            Detalhamento por Unidade Consumidora
        </h2>

        <div class="mt-6">
            <!-- A tabela é uma unidade de quebra: ou cabe inteira na folha,
                 ou desce inteira. Só parte se for mais alta que uma folha,
                 e aí não há para onde empurrar. -->
            <table
                data-unidade
                class="min-w-full table-fixed font-mono text-sm"
            >
                <colgroup>
                    <col style="width: 42%" />
                    <col style="width: 8%" />
                    <col style="width: 22%" />
                    <col v-if="opcoes.coluna_desconto" style="width: 8%" />
                    <col style="width: 20%" />
                </colgroup>

                <thead class="bg-gray-100">
                    <tr>
                        <th
                            class="px-4 py-2 text-left text-xs font-semibold text-gray-900"
                        >
                            Descrição
                        </th>
                        <th
                            class="px-4 py-2 text-right text-xs font-semibold text-gray-900"
                        >
                            Qtd.
                        </th>
                        <th
                            class="px-4 py-2 text-right text-xs font-semibold text-gray-900"
                        >
                            Preço
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
                    <template
                        v-for="item in dados.invoice_items"
                        :key="item.id"
                    >
                        <tr class="border-t border-black">
                            <th
                                :colspan="colunasAntesDoValor"
                                class="px-4 py-2 text-left"
                            >
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

                                    <span
                                        class="text-sm font-light text-gray-600"
                                    >
                                        {{ item.subtitle }}
                                    </span>
                                    <span>
                                        Nº de Dias:
                                        {{
                                            item.consumerUnitBill
                                                ?.period_days ?? '-'
                                        }}
                                    </span>
                                </div>
                            </th>
                            <th
                                class="px-4 py-2 text-right text-sm font-semibold text-gray-900"
                            >
                                <span>{{ dinheiro(item.amount) }}</span>
                            </th>
                        </tr>

                        <tr
                            v-for="(subitem, indice) in item.invoiceSubitems"
                            :key="`${item.id}-${indice}`"
                            class="border-t"
                            :class="
                                indice === 0
                                    ? 'border-gray-300'
                                    : 'border-gray-200'
                            "
                        >
                            <td
                                class="px-4 py-2 pl-8 text-sm text-gray-500"
                                :class="{ 'line-through': !subitem.chargeable }"
                            >
                                {{ subitem.description }}
                            </td>
                            <td
                                class="px-4 py-2 text-right text-sm text-gray-500"
                                :class="{ 'line-through': !subitem.chargeable }"
                            >
                                {{ subitem.quantity }}
                            </td>
                            <td
                                class="px-4 py-2 text-right text-sm text-gray-500"
                                :class="{ 'line-through': !subitem.chargeable }"
                            >
                                <span>{{ dinheiro(subitem.price) }}</span>
                            </td>
                            <td
                                v-if="opcoes.coluna_desconto"
                                class="px-4 py-2 text-right text-sm text-gray-500"
                                :class="{ 'line-through': !subitem.chargeable }"
                            >
                                {{ subitem.discount_label }}
                            </td>
                            <td
                                class="px-4 py-2 text-right text-sm text-gray-500"
                                :class="{ 'line-through': !subitem.chargeable }"
                            >
                                <span>{{ dinheiro(subitem.amount) }}</span>
                            </td>
                        </tr>
                    </template>
                </tbody>

                <tfoot v-if="opcoes.linha_total" class="bg-white">
                    <tr>
                        <!-- linha vazia só pra respiro, como no original -->
                        <td
                            style="border: 0"
                            :colspan="colunasAntesDoValor"
                        ></td>
                    </tr>
                    <tr>
                        <td
                            style="border: 0"
                            :colspan="colunasAntesDoValor - 1"
                        ></td>
                        <td class="px-2 py-2 text-left font-bold text-gray-900">
                            Total
                        </td>
                        <td
                            class="px-2 py-2 text-right font-bold text-gray-900"
                        >
                            <span>{{ dinheiro(dados.invoice.amount) }}</span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</template>
