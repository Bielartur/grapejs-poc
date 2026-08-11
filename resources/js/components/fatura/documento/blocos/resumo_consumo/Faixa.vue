<script setup lang="ts">
import { usarTemaFatura } from '@/composables/useTemaFatura';
import { dinheiro } from '@/lib/formato';
import type { DadosFatura, OpcoesBloco } from '@/types/fatura';

/**
 * Variante `faixa` do resumo: o total a pagar vira o elemento dominante,
 * numa faixa larga, e os indicadores descem pra uma linha discreta.
 *
 * O "Total a Pagar" segue sem opção pra esconder — uma fatura sem valor
 * não é uma fatura. Essa invariante é do catálogo, não da variante, e é
 * por isso que ela se repete igual nas três.
 */

defineProps<{ dados: DadosFatura; opcoes: OpcoesBloco }>();

const c = usarTemaFatura();
</script>

<template>
    <div class="mt-1">
        <h3 class="text-lg font-medium text-gray-900">Resumo de Consumo</h3>

        <div
            class="mt-2 flex items-center justify-between border-2 px-5 py-4"
            :class="[c.arredondamento, c.destaque_fundo, c.destaque_borda]"
        >
            <div>
                <span class="text-sm font-medium text-gray-600">
                    Total a Pagar
                </span>
                <div
                    v-if="opcoes.mostrar_valor_original"
                    class="text-sm text-gray-500 line-through"
                >
                    {{ dinheiro(dados.original_amount) }}
                </div>
            </div>

            <p class="text-4xl font-extrabold" :class="c.destaque_texto">
                {{ dinheiro(dados.invoice.amount) }}
            </p>
        </div>

        <div class="mt-2 flex flex-wrap gap-x-8 gap-y-1 text-sm">
            <p v-if="opcoes.mostrar_creditos" class="text-gray-700">
                <span class="font-semibold">
                    {{ dados.credits_used }} kWh
                </span>
                compensados
                <span v-if="dados.active_kwh_used > 0" class="text-gray-500">
                    de {{ dados.active_kwh_used }} kWh consumidos
                </span>
            </p>

            <p v-if="opcoes.mostrar_economia" class="text-gray-700">
                Economia de
                <span class="font-semibold" :class="c.destaque_texto">
                    {{ dinheiro(dados.total_saved) }}
                    ({{ dados.total_saved_percentage }})
                </span>
            </p>
        </div>

        <div class="mt-2 space-y-0.5 text-xs text-gray-600">
            <p>
                <strong>*Créditos Compensados:</strong> KW/h oriundos da Energia
                Solar
            </p>
            <p>
                <strong>*Consumidos:</strong> Consumo Total da(s) sua(s)
                unidade(s)
            </p>
        </div>
    </div>
</template>
