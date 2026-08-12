<script setup lang="ts">
import BlocoPendente from '@/components/fatura/documento/BlocoPendente.vue';
import { componenteDe } from '@/components/fatura/documento/componentes';
import type { BlocoLayout, Tema } from '@/composables/useLayoutFatura';
import { proverEstilosBloco } from '@/composables/useEstilosBloco';
import type { DadosFatura } from '@/types/fatura';

/**
 * UM BLOCO DO DOCUMENTO.
 *
 * Existe por uma razão só, mas suficiente: `provide` é por instância de
 * componente, e os estilos são por bloco. No `DocumentoFatura` os blocos
 * saem de um `v-for` no template — não há onde chamar
 * `proverEstilosBloco` uma vez para cada um. Este invólucro é esse
 * "onde".
 *
 * A `<section data-bloco>` fica aqui junto porque é o mesmo escopo: a
 * marcação que o editor usa pra achar o bloco e a que o preview usa pra
 * paginar são a mesma, e separá-las de quem provê o estilo só criaria
 * dois lugares pra manter em sincronia.
 */

const props = defineProps<{
    bloco: BlocoLayout;
    tema: Tema;
    dados: DadosFatura;
}>();

proverEstilosBloco(() => props.bloco.estilos);
</script>

<template>
    <section
        v-if="bloco.variante"
        :data-bloco="bloco.id"
        data-unidade
        class="mb-4"
    >
        <component
            :is="componenteDe(bloco.id, bloco.variante)"
            v-if="componenteDe(bloco.id, bloco.variante)"
            :dados="dados"
            :opcoes="bloco.opcoes"
        />
        <BlocoPendente v-else :id-bloco="bloco.id" :variante="bloco.variante" />
    </section>
</template>
