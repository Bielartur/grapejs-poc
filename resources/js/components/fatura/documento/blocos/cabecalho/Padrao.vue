<script setup lang="ts">
import { dataBr } from '@/lib/formato';
import type { DadosFatura, OpcoesBloco } from '@/types/fatura';

/**
 * Porte de `resources/views/fatura/blocos/cabecalho.blade.php`.
 *
 * Tradução literal, sem "melhorar" a marcação: qualquer liberdade aqui
 * vira diferença entre o que o usuário vê e o PDF emitido.
 */

defineProps<{ dados: DadosFatura; opcoes: OpcoesBloco }>();
</script>

<template>
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <template v-if="opcoes.mostrar_logo">
                <img
                    v-if="dados.company_logo_url"
                    :src="dados.company_logo_url"
                    alt="Company Logo"
                    class="h-14 w-auto max-w-40 object-contain"
                />
                <!-- Sem logo cadastrada o original simplesmente some com o
                     bloco; no editor um vazio silencioso confunde, então
                     mostramos o espaço reservado. -->
                <div
                    v-else
                    class="flex h-14 w-40 shrink-0 items-center justify-center border border-dashed border-gray-300 text-xs text-gray-400"
                >
                    LOGO
                </div>
            </template>

            <div>
                <p class="text-lg font-bold">
                    {{ dados.company.fantasy_name }}
                </p>
                <p>{{ dados.company.name }}</p>
                <p>
                    CNPJ: &nbsp;<span>{{ dados.company.document }}</span>
                </p>

                <p v-if="opcoes.mostrar_contato">
                    {{ dados.company.phone }} | {{ dados.company.email }}
                </p>
            </div>
        </div>

        <div class="text-right">
            <p class="text-sm">Fatura: {{ dados.invoice.code }}</p>
            <p class="text-sm">
                Emissão: {{ dataBr(dados.invoice.issue_date) }}
            </p>
        </div>
    </div>
</template>
