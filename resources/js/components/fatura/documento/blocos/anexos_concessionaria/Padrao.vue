<script setup lang="ts">
import { usarTemaFatura } from '@/composables/useTemaFatura';
import type { DadosFatura, OpcoesBloco } from '@/types/fatura';

/**
 * Porte de `resources/views/fatura/blocos/anexos_concessionaria.blade.php`.
 *
 * No original a seção inteira depende de `request()->has('view')` — ela
 * só aparece na visualização em tela, nunca no PDF baixado. Essa
 * condição é da geração, não do layout, então fica fora do editor: aqui
 * o bloco só decide SE faz parte do documento.
 */

defineProps<{ dados: DadosFatura; opcoes: OpcoesBloco }>();

const c = usarTemaFatura();
</script>

<template>
    <div v-if="dados.bills_to_include.length > 0">
        <h2 class="mb-6 border-b pb-4 text-xl font-semibold text-gray-900">
            Faturas da Concessionária Anexadas
        </h2>

        <div class="py-12 text-center">
            <p class="mx-auto mb-8 max-w-2xl text-gray-600">
                Quando você fizer o download desta fatura, as seguintes faturas
                da concessionária serão automaticamente anexadas ao PDF:
            </p>

            <div
                class="mx-auto max-w-2xl bg-gray-50 p-6"
                :class="c.arredondamento"
            >
                <div class="space-y-3">
                    <div
                        v-for="anexo in dados.bills_to_include"
                        :key="anexo.bill.bill_number"
                        class="flex items-center justify-between bg-white p-4 shadow-xs"
                        :class="c.arredondamento"
                    >
                        <div class="flex items-center">
                            <div
                                class="mr-3 flex h-8 w-8 items-center justify-center rounded-full bg-blue-100"
                            >
                                <svg
                                    class="h-4 w-4 text-blue-600"
                                    fill="currentColor"
                                    viewBox="0 0 20 20"
                                >
                                    <path
                                        fill-rule="evenodd"
                                        d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z"
                                        clip-rule="evenodd"
                                    />
                                </svg>
                            </div>
                            <div class="text-left">
                                <div class="font-medium text-gray-900">
                                    {{ anexo.consumer_unit.code }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ anexo.bill.reference_month }}
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-sm font-medium text-gray-700">
                                Fatura {{ anexo.bill.bill_number }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="mx-auto mt-8 max-w-2xl border border-amber-200 bg-amber-50 p-4"
                :class="c.arredondamento"
            >
                <div class="flex items-center justify-center">
                    <svg
                        class="mr-2 h-5 w-5 text-amber-600"
                        fill="currentColor"
                        viewBox="0 0 20 20"
                    >
                        <path
                            fill-rule="evenodd"
                            d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <span class="text-sm font-medium text-amber-800">
                        Total de {{ dados.bills_to_include.length }} fatura(s)
                        da concessionária será(ão) anexada(s) automaticamente
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
