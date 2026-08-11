<script setup lang="ts">
import { computed } from 'vue';
import { dinheiro } from '@/lib/formato';
import type { DadosFatura, MesHistorico, OpcoesBloco } from '@/types/fatura';

/**
 * Porte de `resources/views/fatura/blocos/historico_consumo.blade.php`.
 *
 * O `array_slice(..., -$meses)` do Blade vira `slice(-meses)`: os mais
 * recentes ficam no fim da lista, então o corte é pelo fim.
 *
 * Cuidado do contrato: `histories_by_item` é chaveado pelo id do item,
 * e a travessia JSON converte a chave inteira em string. Daí o
 * `String(item.id)` no acesso — sem isso o histórico vinha vazio e o
 * bloco sumia sem erro nenhum.
 */

const props = defineProps<{ dados: DadosFatura; opcoes: OpcoesBloco }>();

const meses = computed(() => Number(props.opcoes.meses) || 6);

/** Só as unidades que têm histórico entram — igual ao `@continue`. */
const unidades = computed(() =>
    props.dados.invoice_items
        .map((item) => ({
            item,
            historico: (
                props.dados.histories_by_item[String(item.id)] ?? []
            ).slice(-meses.value) as MesHistorico[],
        }))
        .filter(({ historico }) => historico.length > 0),
);
</script>

<template>
    <div v-if="dados.invoice_items.length > 0">
        <h2 class="border-b pb-4 text-xl font-semibold text-gray-900">
            Histórico de Consumo por Unidade Consumidora
        </h2>

        <div class="mt-6">
            <!-- Cada unidade consumidora é uma unidade de quebra: o título e
                 a tabela dela descem juntos pra próxima folha em vez de o
                 corte cair no meio dos meses. O bloco todo é alto demais pra
                 caber numa folha, então é aqui que a regra precisa morar. -->
            <div
                v-for="{ item, historico } in unidades"
                :key="item.id"
                data-unidade
                class="mb-6"
            >
                <h3 class="border-b pb-4 text-lg font-semibold text-gray-900">
                    {{ item.consumerUnit.code }} - {{ item.title }}
                </h3>

                <table class="min-w-full font-mono text-sm">
                    <thead class="bg-gray-100">
                        <tr>
                            <th
                                class="px-4 py-2 text-left text-xs font-semibold text-gray-900"
                            >
                                Mês
                            </th>
                            <th
                                class="px-4 py-2 text-right text-xs font-semibold text-gray-900"
                            >
                                Créditos Consumidos (kWh)
                            </th>
                            <th
                                class="px-4 py-2 text-right text-xs font-semibold text-gray-900"
                            >
                                Créditos Faturados (kWh)
                            </th>
                            <th
                                class="px-4 py-2 text-right text-xs font-semibold text-gray-900"
                            >
                                Saldo de Créditos (kWh)
                            </th>
                            <th
                                class="px-4 py-2 text-right text-xs font-semibold text-gray-900"
                            >
                                Valor Economizado (R$)
                            </th>
                            <th
                                class="px-4 py-2 text-right text-xs font-semibold text-gray-900"
                            >
                                Valor (R$)
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white">
                        <tr
                            v-for="mes in historico"
                            :key="mes.reference_month"
                            class="border-t border-black"
                        >
                            <td
                                class="px-4 py-2 text-sm font-semibold text-gray-900"
                            >
                                {{ mes.reference_month }}
                            </td>
                            <td
                                class="px-4 py-2 text-right text-sm font-semibold text-gray-900"
                            >
                                {{ mes.credits_used }}
                            </td>
                            <td
                                class="px-4 py-2 text-right text-sm font-semibold text-gray-900"
                            >
                                {{ mes.credits_charged }}
                            </td>
                            <td
                                class="px-4 py-2 text-right text-sm font-semibold text-gray-900"
                            >
                                {{ mes.credits_balance }}
                            </td>
                            <td
                                class="px-4 py-2 text-right text-sm font-semibold text-gray-900"
                            >
                                {{ dinheiro(mes.total_saved) }}
                            </td>
                            <td
                                class="px-4 py-2 text-right text-sm font-semibold text-gray-900"
                            >
                                {{ dinheiro(mes.amount) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
