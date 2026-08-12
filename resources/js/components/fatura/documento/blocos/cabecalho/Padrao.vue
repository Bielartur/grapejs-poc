<script setup lang="ts">
import { usarEstilosBloco } from '@/composables/useEstilosBloco';
import { dataBr } from '@/lib/formato';
import type { DadosFatura, OpcoesBloco } from '@/types/fatura';

/**
 * Porte de `resources/views/fatura/blocos/cabecalho.blade.php`.
 *
 * Tradução literal, sem "melhorar" a marcação: qualquer liberdade aqui
 * vira diferença entre o que o usuário vê e o PDF emitido.
 */

defineProps<{ dados: DadosFatura; opcoes: OpcoesBloco }>();

/**
 * Os papéis que o catálogo declara pra este bloco. `e('titulo')` devolve
 * as classes escolhidas no editor — string vazia quando nada foi mexido,
 * e aí a marcação abaixo manda sozinha.
 *
 * SEMPRE via `cn()` quando o elemento já tem classe do mesmo eixo.
 * `class="text-lg" :class="e('titulo')"` com o token em `text-xs` deixa
 * as DUAS classes no elemento, e quem vence é a ordem do CSS gerado pelo
 * Tailwind — não a do atributo. O `cn()` (twMerge) resolve o conflito
 * pelo último, que é o que o usuário acabou de escolher.
 */
const e = usarEstilosBloco();
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
                <p v-bind="e('titulo', 'text-lg font-bold')">
                    {{ dados.company.fantasy_name }}
                </p>
                <div v-bind="e('corpo')">
                    <p>{{ dados.company.name }}</p>
                    <p>
                        CNPJ: &nbsp;<span>{{ dados.company.document }}</span>
                    </p>

                    <p v-if="opcoes.mostrar_contato">
                        {{ dados.company.phone }} | {{ dados.company.email }}
                    </p>
                </div>
            </div>
        </div>

        <!-- o papel vai em cada <p>, não no contêiner: `text-sm` do filho
             venceria um tamanho posto no pai, e o controle não faria nada -->
        <div class="text-right">
            <p v-bind="e('destaque', 'text-sm')">
                Fatura: {{ dados.invoice.code }}
            </p>
            <p v-bind="e('destaque', 'text-sm')">
                Emissão: {{ dataBr(dados.invoice.issue_date) }}
            </p>
        </div>
    </div>
</template>
