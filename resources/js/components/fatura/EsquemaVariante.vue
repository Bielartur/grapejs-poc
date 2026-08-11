<script setup lang="ts">
import { computed } from 'vue';
import { esquemaDe } from '@/components/fatura/esquemas';
import FormaEsquema from '@/components/fatura/FormaEsquema.vue';

/**
 * O PREVIEW DE UMA VARIANTE, NA SIDEBAR
 *
 * A silhueta do componente: onde ficam os quadros, quantas colunas tem a
 * tabela, se o logo está ao lado ou acima. Não é o componente encolhido
 * — é o desenho dele, e é de propósito: a pergunta que o cartão responde
 * é "com que cara?", não "o que está escrito?".
 *
 * Quem quer ver o conteúdo de verdade tem o preview grande ao lado, no
 * tamanho da folha.
 */

const props = defineProps<{ idBloco: string; variante: string }>();

const esquema = computed(() => esquemaDe(props.idBloco, props.variante));
</script>

<template>
    <div class="flex min-h-24 flex-col justify-center gap-2 bg-muted/20 p-4">
        <template v-if="esquema">
            <FormaEsquema
                v-for="(forma, indice) in esquema"
                :key="indice"
                :forma="forma"
            />
        </template>

        <p v-else class="text-center text-xs text-muted-foreground">
            Esta variante ainda não tem desenho.
        </p>
    </div>
</template>
