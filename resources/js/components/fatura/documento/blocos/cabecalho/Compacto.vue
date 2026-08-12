<script setup lang="ts">
import { usarEstilosBloco } from '@/composables/useEstilosBloco';
import { usarTemaFatura } from '@/composables/useTemaFatura';
import { dataBr } from '@/lib/formato';
import { cn } from '@/lib/utils';
import type { DadosFatura, OpcoesBloco } from '@/types/fatura';

/**
 * Variante `compacto` do cabeçalho.
 *
 * Mesma informação do `Padrao`, numa faixa só: economiza altura na
 * primeira folha, que é onde a disputa por espaço acontece.
 *
 * As opções são as MESMAS do bloco (`mostrar_logo`, `mostrar_contato`) —
 * variante troca a forma, não o que o bloco sabe mostrar. Um toggle que
 * funcionasse só em uma das formas pareceria quebrado nas outras.
 */

defineProps<{ dados: DadosFatura; opcoes: OpcoesBloco }>();

const c = usarTemaFatura();
const e = usarEstilosBloco();
</script>

<template>
    <div
        class="flex items-center justify-between gap-4 border px-4 py-3"
        :class="[c.arredondamento, c.destaque_fundo, c.destaque_borda]"
    >
        <div class="flex min-w-0 items-center gap-3">
            <template v-if="opcoes.mostrar_logo">
                <img
                    v-if="dados.company_logo_url"
                    :src="dados.company_logo_url"
                    alt="Company Logo"
                    class="h-8 w-auto max-w-28 object-contain"
                />
                <div
                    v-else
                    class="flex h-8 w-24 shrink-0 items-center justify-center border border-dashed border-gray-300 text-[10px] text-gray-400"
                >
                    LOGO
                </div>
            </template>

            <div class="min-w-0">
                <p
                    v-bind="
                        e('titulo', cn('truncate font-bold', c.destaque_texto))
                    "
                >
                    {{ dados.company.fantasy_name }}
                </p>
                <p v-bind="e('corpo', 'truncate text-xs text-gray-600')">
                    CNPJ: {{ dados.company.document }}
                    <template v-if="opcoes.mostrar_contato">
                        · {{ dados.company.phone }} · {{ dados.company.email }}
                    </template>
                </p>
            </div>
        </div>

        <div class="shrink-0 text-right whitespace-nowrap">
            <p v-bind="e('destaque', 'text-xs font-semibold')">
                Fatura {{ dados.invoice.code }}
            </p>
            <p v-bind="e('destaque', 'text-xs text-gray-600')">
                {{ dataBr(dados.invoice.issue_date) }}
            </p>
        </div>
    </div>
</template>
