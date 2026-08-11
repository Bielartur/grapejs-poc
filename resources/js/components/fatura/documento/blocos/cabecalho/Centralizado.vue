<script setup lang="ts">
import { usarTemaFatura } from '@/composables/useTemaFatura';
import { dataBr } from '@/lib/formato';
import type { DadosFatura, OpcoesBloco } from '@/types/fatura';

/**
 * Variante `centralizado` do cabeçalho: logo em cima, tudo no eixo, e um
 * filete de destaque separando do resto do documento.
 *
 * Gasta mais altura que as outras duas — é a escolha de quem prioriza a
 * marca sobre o aproveitamento da folha.
 */

defineProps<{ dados: DadosFatura; opcoes: OpcoesBloco }>();

const c = usarTemaFatura();
</script>

<template>
    <div class="flex flex-col items-center gap-2 pb-3 text-center">
        <template v-if="opcoes.mostrar_logo">
            <img
                v-if="dados.company_logo_url"
                :src="dados.company_logo_url"
                alt="Company Logo"
                class="h-16 w-auto max-w-52 object-contain"
            />
            <div
                v-else
                class="flex h-16 w-40 items-center justify-center border border-dashed border-gray-300 text-xs text-gray-400"
            >
                LOGO
            </div>
        </template>

        <div>
            <p class="text-xl font-bold">{{ dados.company.fantasy_name }}</p>
            <p class="text-sm text-gray-600">{{ dados.company.name }}</p>
            <p class="text-sm text-gray-600">
                CNPJ: {{ dados.company.document }}
            </p>
            <p v-if="opcoes.mostrar_contato" class="text-sm text-gray-600">
                {{ dados.company.phone }} | {{ dados.company.email }}
            </p>
        </div>

        <div
            class="mt-1 w-full border-t-2 pt-2 text-sm font-medium"
            :class="[c.destaque_borda, c.destaque_texto]"
        >
            Fatura {{ dados.invoice.code }} · Emissão em
            {{ dataBr(dados.invoice.issue_date) }}
        </div>
    </div>
</template>
