<script setup lang="ts">
import { usarTemaFatura } from '@/composables/useTemaFatura';
import { dinheiro } from '@/lib/formato';
import type { DadosFatura, OpcoesBloco } from '@/types/fatura';

/**
 * Variante `cartoes` do detalhamento: um quadro por unidade em vez de
 * uma tabela única.
 *
 * Cada cartão é uma `data-unidade` própria, e isso muda a paginação de
 * verdade: a tabela do `Padrao` só pode descer inteira pra próxima
 * folha, enquanto aqui a quebra cai entre dois cartões. Numa fatura com
 * muitas UCs é a diferença entre uma folha quase vazia e o documento
 * fechando junto.
 */

defineProps<{ dados: DadosFatura; opcoes: OpcoesBloco }>();

const c = usarTemaFatura();
</script>

<template>
    <div>
        <h2 class="border-b pb-4 text-xl font-semibold text-gray-900">
            Detalhamento por Unidade Consumidora
        </h2>

        <div class="mt-4 flex flex-col gap-3">
            <div
                v-for="item in dados.invoice_items"
                :key="item.id"
                data-unidade
                class="border border-gray-300"
                :class="c.arredondamento"
            >
                <div
                    class="flex items-start justify-between gap-4 border-b border-gray-300 bg-gray-50 px-4 py-2"
                >
                    <div class="min-w-0">
                        <p class="font-semibold text-gray-900">
                            {{ item.title }}
                        </p>

                        <p
                            v-if="
                                opcoes.apelido_unidade &&
                                item.consumerUnit.alias
                            "
                            class="text-xs text-gray-400"
                        >
                            {{ item.consumerUnit.alias }}
                        </p>

                        <p class="text-xs text-gray-600">
                            {{ item.subtitle }} · Nº de dias:
                            {{ item.consumerUnitBill?.period_days ?? '-' }}
                        </p>
                    </div>

                    <p class="shrink-0 font-semibold whitespace-nowrap">
                        {{ dinheiro(item.amount) }}
                    </p>
                </div>

                <dl class="divide-y divide-gray-200 px-4 font-mono text-xs">
                    <div
                        v-for="(subitem, indice) in item.invoiceSubitems"
                        :key="`${item.id}-${indice}`"
                        class="flex items-baseline justify-between gap-4 py-1.5"
                        :class="{ 'line-through': !subitem.chargeable }"
                    >
                        <dt class="min-w-0 text-gray-600">
                            {{ subitem.description }}
                            <span class="text-gray-400">
                                ({{ subitem.quantity }} ×
                                {{ dinheiro(subitem.price)
                                }}<template
                                    v-if="
                                        opcoes.coluna_desconto &&
                                        subitem.discount_label
                                    "
                                    >, {{ subitem.discount_label }}</template
                                >)
                            </span>
                        </dt>
                        <dd class="shrink-0 whitespace-nowrap text-gray-700">
                            {{ dinheiro(subitem.amount) }}
                        </dd>
                    </div>
                </dl>
            </div>

            <div
                v-if="opcoes.linha_total"
                class="flex items-baseline justify-between border-t-2 border-black px-4 pt-2"
            >
                <span class="font-bold text-gray-900">Total</span>
                <span class="font-bold text-gray-900">
                    {{ dinheiro(dados.invoice.amount) }}
                </span>
            </div>
        </div>
    </div>
</template>
