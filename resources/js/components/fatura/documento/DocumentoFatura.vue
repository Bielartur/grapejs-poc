<script setup lang="ts">
import BlocoDocumento from '@/components/fatura/documento/BlocoDocumento.vue';
import type { BlocoLayout, Tema } from '@/composables/useLayoutFatura';
import { proverTemaFatura } from '@/composables/useTemaFatura';
import type { DadosFatura } from '@/types/fatura';

/**
 * O DOCUMENTO, EM VUE
 *
 * Uma div por página, uma <section data-bloco> por bloco com variante
 * escolhida.
 *
 * `bloco.variante` é `null` quando o usuário escolheu "sem nenhum" — o
 * que antes era `ativo: false`. Escolher a forma e escolher se o bloco
 * aparece viraram a MESMA decisão, e some daqui o estado impossível
 * "ligado, mas sem componente pra desenhar".
 *
 * O `<component :is>` renderiza vazio e cala quando o mapa não tem a
 * chave. Daí o `BlocoPendente` — uma variante que o catálogo conhece mas
 * o mapa não precisa APARECER, não sumir.
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
            <BlocoDocumento
                v-for="bloco in blocos"
                :key="bloco.id"
                :bloco="bloco"
                :tema="tema"
                :dados="dados"
            />
        </div>
    </div>
</template>
