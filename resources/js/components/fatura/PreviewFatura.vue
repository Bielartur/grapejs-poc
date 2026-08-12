<script setup lang="ts">
import { ref, watch } from 'vue';
import FolhaPreview from '@/components/fatura/FolhaPreview.vue';
import type { BlocoLayout, Tema } from '@/composables/useLayoutFatura';
import type { DadosFatura } from '@/types/fatura';

/**
 * A pilha de páginas. Só orquestra: cada `FolhaPreview` cuida do próprio
 * recorte em folhas, e este componente junta as medidas pra reportar
 * quantas folhas o PDF terá no total.
 *
 * Recebe a configuração, não HTML. Antes chegava uma string por página,
 * renderizada pelo Blade a cada alteração; agora os componentes do
 * documento reagem direto ao estado do editor.
 */

const props = defineProps<{
    paginas: BlocoLayout[][];
    tema: Tema;
    dados: DadosFatura;
    selecionado: string | null;
    elementoSelecionado: string | null;
    zoom: number;
}>();

const emit = defineEmits<{
    medido: [folhasPorPagina: number[]];
    selecionar: [id: string | null, elemento: string | null];
}>();

const folhas = ref<number[]>([]);

// uma página removida deixaria a medida antiga pra trás
watch(
    () => props.paginas.length,
    (n) => (folhas.value = folhas.value.slice(0, n)),
);

function registrar(indice: number, quantas: number) {
    folhas.value[indice] = quantas;

    if (folhas.value.length === props.paginas.length) {
        emit('medido', [...folhas.value]);
    }
}
</script>

<template>
    <!-- sem scroll próprio: quem rola é o contêiner da página, senão
         sobram duas barras e o zoom horizontal fica numa e o vertical
         na outra -->
    <div class="flex justify-center px-8 py-10">
        <!--
            O zoom escala a pilha inteira. `origin-top` mantém o topo no
            lugar; o espaço que a pilha ocupa no fluxo não muda com
            `scale`, então o container externo é quem rola.
        -->
        <div
            class="origin-top transition-transform"
            :style="{ transform: `scale(${zoom})` }"
        >
            <div class="flex flex-col items-center gap-12">
                <FolhaPreview
                    v-for="(blocos, indice) in paginas"
                    :key="indice"
                    :blocos="blocos"
                    :tema="tema"
                    :dados="dados"
                    :numero="indice + 1"
                    :selecionado="selecionado"
                    :elemento-selecionado="elementoSelecionado"
                    @medido="registrar(indice, $event)"
                    @selecionar="
                        (id, elemento) => emit('selecionar', id, elemento)
                    "
                />
            </div>
        </div>
    </div>
</template>
