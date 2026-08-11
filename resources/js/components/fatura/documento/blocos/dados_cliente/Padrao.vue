<script setup lang="ts">
import { usarTemaFatura } from '@/composables/useTemaFatura';
import type { DadosFatura, OpcoesBloco } from '@/types/fatura';

/**
 * Porte de `resources/views/fatura/blocos/dados_cliente.blade.php`.
 *
 * Primeiro bloco a consumir o tema: o `{{ $c['arredondamento'] }}` do
 * Blade vira o `inject` provido pelo `DocumentoFatura`.
 */

defineProps<{ dados: DadosFatura; opcoes: OpcoesBloco }>();

const c = usarTemaFatura();
</script>

<template>
    <div class="bg-gray-100 p-3" :class="c.arredondamento">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-base font-bold">{{ dados.customer.name }}</p>
                <p class="text-xs text-gray-700">
                    Doc: {{ dados.customer.document }}
                    <template v-if="opcoes.mostrar_endereco">
                        · {{ dados.customer.address?.full_address ?? '-' }}
                    </template>
                </p>
            </div>
            <div class="text-right whitespace-nowrap">
                <p class="text-xs text-gray-500 uppercase">Mês de Referência</p>
                <p class="font-bold">{{ dados.reference_month_label }}</p>
            </div>
        </div>
    </div>
</template>
