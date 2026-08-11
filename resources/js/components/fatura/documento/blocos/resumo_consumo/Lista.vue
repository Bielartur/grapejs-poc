<script setup lang="ts">
import { usarTemaFatura } from '@/composables/useTemaFatura';
import { dinheiro } from '@/lib/formato';
import type { DadosFatura, OpcoesBloco } from '@/types/fatura';

/**
 * Variante `lista` do resumo: linhas rótulo/valor, sem cartões.
 *
 * É a forma mais barata em altura das três — existe pro caso de a
 * primeira folha estar disputada e o detalhamento precisar caber junto.
 */

defineProps<{ dados: DadosFatura; opcoes: OpcoesBloco }>();

const c = usarTemaFatura();
</script>

<template>
    <div class="mt-1">
        <h3 class="text-lg font-medium text-gray-900">Resumo de Consumo</h3>

        <dl class="mt-2 divide-y divide-gray-200 border-y border-gray-200">
            <div
                v-if="opcoes.mostrar_creditos"
                class="flex items-baseline justify-between py-1.5"
            >
                <dt class="text-sm text-gray-600">Créditos compensados</dt>
                <dd class="text-sm font-semibold text-gray-900">
                    {{ dados.credits_used }} kWh
                    <span
                        v-if="dados.active_kwh_used > 0"
                        class="font-normal text-gray-500"
                    >
                        de {{ dados.active_kwh_used }} kWh
                    </span>
                </dd>
            </div>

            <div
                v-if="opcoes.mostrar_valor_original"
                class="flex items-baseline justify-between py-1.5"
            >
                <dt class="text-sm text-gray-600">Valor original</dt>
                <dd class="text-sm text-gray-600 line-through">
                    {{ dinheiro(dados.original_amount) }}
                </dd>
            </div>

            <div
                v-if="opcoes.mostrar_economia"
                class="flex items-baseline justify-between py-1.5"
            >
                <dt class="text-sm text-gray-600">Economia do mês</dt>
                <dd class="text-sm font-semibold" :class="c.destaque_texto">
                    {{ dinheiro(dados.total_saved) }}
                    ({{ dados.total_saved_percentage }})
                </dd>
            </div>

            <div class="flex items-baseline justify-between py-2">
                <dt class="font-semibold text-gray-900">Total a pagar</dt>
                <dd class="text-2xl font-extrabold text-gray-900">
                    {{ dinheiro(dados.invoice.amount) }}
                </dd>
            </div>
        </dl>

        <p class="mt-1 text-xs text-gray-600">
            <strong>*Créditos Compensados:</strong> KW/h oriundos da Energia
            Solar · <strong>*Consumidos:</strong> Consumo Total da(s) sua(s)
            unidade(s)
        </p>
    </div>
</template>
