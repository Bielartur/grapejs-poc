<script setup lang="ts">
import type { Component } from 'vue';
import BlocoPendente from '@/components/fatura/documento/BlocoPendente.vue';
import AnexosConcessionaria from '@/components/fatura/documento/blocos/AnexosConcessionaria.vue';
import Boleto from '@/components/fatura/documento/blocos/Boleto.vue';
import Cabecalho from '@/components/fatura/documento/blocos/Cabecalho.vue';
import CodigoBarras from '@/components/fatura/documento/blocos/CodigoBarras.vue';
import DadosCliente from '@/components/fatura/documento/blocos/DadosCliente.vue';
import DetalhamentoUc from '@/components/fatura/documento/blocos/DetalhamentoUc.vue';
import HistoricoConsumo from '@/components/fatura/documento/blocos/HistoricoConsumo.vue';
import Pix from '@/components/fatura/documento/blocos/Pix.vue';
import ResumoConsumo from '@/components/fatura/documento/blocos/ResumoConsumo.vue';
import type { BlocoLayout, Tema } from '@/composables/useLayoutFatura';
import { proverTemaFatura } from '@/composables/useTemaFatura';
import type { DadosFatura } from '@/types/fatura';

/**
 * O DOCUMENTO, EM VUE
 *
 * Porte de `resources/views/fatura/documento.blade.php`. Mesma estrutura:
 * uma div por página, uma <section data-bloco> por bloco ativo.
 *
 * O `@include` com nome dinâmico virou `<component :is>`, e isso trocou
 * a natureza de um erro: o Blade estourava quando o partial não existia,
 * o Vue renderiza vazio e cala. Daí o `BlocoPendente` — um id do
 * catálogo sem componente aqui precisa APARECER, não sumir.
 *
 * As marcações `data-bloco` e `data-unidade` seguem idênticas: a
 * primeira o editor usa pra destacar a seção, a segunda comanda a quebra
 * de folha no preview e no PDF.
 */

const props = defineProps<{
    paginas: BlocoLayout[][];
    tema: Tema;
    dados: DadosFatura;
}>();

// uma vez pro documento inteiro; cada bloco injeta o que usa
proverTemaFatura(() => props.tema);

/**
 * O pareamento com o `CatalogoBlocos` do PHP. Toda chave daqui precisa
 * existir lá, e todo id de lá que esteja `disponivel` precisa existir
 * aqui — `grafico` é a única ausência legítima, porque o catálogo o
 * declara indisponível. O teste de pareamento trava isso.
 */
const COMPONENTES: Record<string, Component> = {
    cabecalho: Cabecalho,
    dados_cliente: DadosCliente,
    resumo_consumo: ResumoConsumo,
    pix: Pix,
    codigo_barras: CodigoBarras,
    boleto: Boleto,
    detalhamento_uc: DetalhamentoUc,
    historico_consumo: HistoricoConsumo,
    anexos_concessionaria: AnexosConcessionaria,
};
</script>

<template>
    <!-- `documento-fatura` é o escopo do CSS do documento; sem ela não
         há borda de tabela nem fonte de boleto. Ver `documento.css`. -->
    <div class="documento-fatura">
        <div
            v-for="(blocos, pagina) in paginas"
            :key="`pagina-${pagina}`"
            :data-pagina="pagina + 1"
            class="boleto-text mx-auto max-w-4xl bg-white px-6 py-8"
            :class="{ 'page-break': pagina > 0 }"
        >
            <template v-for="bloco in blocos" :key="bloco.id">
                <section
                    v-if="bloco.ativo"
                    :data-bloco="bloco.id"
                    data-unidade
                    class="mb-4"
                >
                    <component
                        :is="COMPONENTES[bloco.id]"
                        v-if="COMPONENTES[bloco.id]"
                        :dados="dados"
                        :opcoes="bloco.opcoes"
                    />
                    <BlocoPendente v-else :id-bloco="bloco.id" />
                </section>
            </template>
        </div>
    </div>
</template>
