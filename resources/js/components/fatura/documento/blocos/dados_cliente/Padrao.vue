<script setup lang="ts">
import { usarEstilosBloco } from '@/composables/useEstilosBloco';
import { usarTemaFatura } from '@/composables/useTemaFatura';
import { cn } from '@/lib/utils';
import type { DadosFatura, OpcoesBloco } from '@/types/fatura';

/**
 * Porte de `resources/views/fatura/blocos/dados_cliente.blade.php`.
 *
 * Primeiro bloco a consumir o tema: o `{{ $c['arredondamento'] }}` do
 * Blade vira o `inject` provido pelo `DocumentoFatura`.
 */

defineProps<{ dados: DadosFatura; opcoes: OpcoesBloco }>();

const c = usarTemaFatura();
const e = usarEstilosBloco();
</script>

<template>
    <div v-bind="e('moldura', cn('bg-gray-100 p-3', c.arredondamento))">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p v-bind="e('titulo', 'text-base font-bold')">
                    {{ dados.customer.name }}
                </p>
                <p v-bind="e('corpo', 'text-xs text-gray-700')">
                    Doc: {{ dados.customer.document }}
                    <template v-if="opcoes.mostrar_endereco">
                        · {{ dados.customer.address?.full_address ?? '-' }}
                    </template>
                </p>
            </div>
            <div class="text-right whitespace-nowrap">
                <p v-bind="e('corpo', 'text-xs text-gray-500 uppercase')">
                    Mês de Referência
                </p>
                <p v-bind="e('corpo', 'font-bold')">
                    {{ dados.reference_month_label }}
                </p>
            </div>
        </div>
    </div>
</template>
