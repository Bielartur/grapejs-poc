<script setup lang="ts">
import { computed } from 'vue';
import { usarEstilosBloco } from '@/composables/useEstilosBloco';
import { usarTemaFatura } from '@/composables/useTemaFatura';
import { cn } from '@/lib/utils';
import type { DadosFatura, OpcoesBloco } from '@/types/fatura';

/**
 * O AVISO DE FALTA DE ENERGIA
 *
 * Quem religa a rede é a DISTRIBUIDORA, não a emissora da fatura — daí o
 * telefone vir de `dados.distributor` e não do `company`. Sem
 * distribuidora no cadastro o bloco não sai: as opções do editor decidem
 * o que MOSTRAR, nunca inventam dado que não existe.
 *
 * As UCs saem dos itens da fatura, que é onde elas já moram. O bloco não
 * ganha lista própria porque duas listas de unidade divergiriam.
 */

const props = defineProps<{ dados: DadosFatura; opcoes: OpcoesBloco }>();

const c = usarTemaFatura();
const e = usarEstilosBloco();

/** Sem `Set` a mesma UC repetiria a cada item que ela tiver. */
const unidades = computed(() => [
    ...new Set(props.dados.invoice_items.map((item) => item.consumerUnit.code)),
]);
</script>

<template>
    <div
        v-if="dados.distributor"
        v-bind="
            e(
                'moldura',
                cn(
                    'mt-2 flex items-center justify-between gap-4 border border-amber-200 bg-amber-50 p-3',
                    c.arredondamento,
                ),
            )
        "
    >
        <div class="flex items-start gap-2">
            <svg
                class="mt-0.5 h-4 w-4 shrink-0 text-amber-600"
                fill="currentColor"
                viewBox="0 0 20 20"
            >
                <path
                    fill-rule="evenodd"
                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                    clip-rule="evenodd"
                />
            </svg>
            <div>
                <p v-bind="e('titulo', 'font-semibold text-amber-800')">
                    Faltou energia? Acione a {{ dados.distributor.name }}
                </p>
                <p
                    v-if="opcoes.mostrar_identificacao"
                    v-bind="
                        e(
                            'corpo',
                            'mt-1 flex flex-wrap items-center gap-1 text-amber-900',
                        )
                    "
                >
                    <span>Informe:</span>
                    <span
                        class="rounded border border-amber-300 bg-white px-1.5 py-0.5 font-medium"
                    >
                        {{ dados.customer.document }}
                    </span>
                    <span
                        v-for="unidade in unidades"
                        :key="unidade"
                        class="rounded border border-amber-300 bg-white px-1.5 py-0.5 font-medium"
                    >
                        {{ unidade }}
                    </span>
                </p>
            </div>
        </div>

        <div v-if="opcoes.mostrar_telefone" class="shrink-0 text-right">
            <p v-bind="e('destaque', 'text-lg font-bold text-amber-800')">
                &#9742; {{ dados.distributor.phone }}
            </p>
            <p class="text-amber-700">
                {{ dados.distributor.service_hours }}
            </p>
        </div>
    </div>
</template>
