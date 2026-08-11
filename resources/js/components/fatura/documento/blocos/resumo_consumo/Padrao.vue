<script setup lang="ts">
import { usarTemaFatura } from '@/composables/useTemaFatura';
import { dinheiro } from '@/lib/formato';
import type { DadosFatura, OpcoesBloco } from '@/types/fatura';

/**
 * Porte de `resources/views/fatura/blocos/resumo_consumo.blade.php`.
 *
 * É o bloco que exercita a tradução inteira: `@money`, as três classes
 * de destaque e condicional aninhada. Se este bate com o Blade, os
 * outros são mecânicos.
 *
 * O "Total a Pagar" continua sem opção pra esconder — uma fatura sem
 * valor não é uma fatura. Essa invariante é do catálogo, não do
 * componente, e sobreviveu à troca de engine sem precisar de nada aqui.
 */

defineProps<{ dados: DadosFatura; opcoes: OpcoesBloco }>();

const c = usarTemaFatura();
</script>

<template>
    <div class="mt-1">
        <h3 class="text-lg font-medium text-gray-900">Resumo de Consumo</h3>

        <div class="mt-1 flex flex-wrap gap-4">
            <div
                v-if="opcoes.mostrar_creditos"
                class="min-w-40 flex-1 border bg-gray-50 p-4"
                :class="c.arredondamento"
            >
                <span class="text-sm font-medium text-gray-700">
                    Créditos Compensados
                </span>
                <div class="mt-2 text-2xl font-bold text-gray-900">
                    {{ dados.credits_used }} <span class="text-lg">kWh</span>
                </div>
                <span
                    v-if="dados.active_kwh_used > 0"
                    class="text-[14px] text-gray-500"
                >
                    de {{ dados.active_kwh_used }} kWh consumidos
                </span>
            </div>

            <div
                v-if="opcoes.mostrar_valor_original"
                class="min-w-40 flex-1 border bg-gray-50 p-4"
                :class="c.arredondamento"
            >
                <span class="text-sm font-medium text-gray-600">
                    Valor Original
                </span>
                <div
                    class="mt-2 text-lg font-medium text-gray-600 line-through"
                >
                    {{ dinheiro(dados.original_amount) }}
                </div>
            </div>

            <div
                v-if="opcoes.mostrar_economia"
                class="min-w-40 flex-1 border p-4"
                :class="[c.arredondamento, c.destaque_fundo, c.destaque_borda]"
            >
                <span class="text-sm font-medium text-gray-600">Economia</span>
                <div class="mt-2 text-xl font-bold" :class="c.destaque_texto">
                    {{ dinheiro(dados.total_saved) }}
                    <span class="text-2xl">
                        ({{ dados.total_saved_percentage }})
                    </span>
                </div>
            </div>
        </div>

        <div class="mt-1 flex items-start justify-between py-2">
            <div class="space-y-0.5 text-xs whitespace-nowrap text-gray-600">
                <p>
                    <strong>*Créditos Compensados:</strong> KW/h oriundos da
                    Energia Solar
                </p>
                <p>
                    <strong>*Consumidos:</strong> Consumo Total da(s) sua(s)
                    unidade(s)
                </p>
            </div>
            <div class="text-right">
                <p
                    class="text-3xl font-extrabold whitespace-nowrap text-gray-900"
                >
                    Total a Pagar:
                    <span
                        class="bg-gray-200 px-1 py-1"
                        :class="c.arredondamento"
                    >
                        {{ dinheiro(dados.invoice.amount) }}
                    </span>
                </p>
            </div>
        </div>
    </div>
</template>
