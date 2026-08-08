<script setup lang="ts">
import { Code2, Redo2, SquareDashed, Trash2, Undo2 } from '@lucide/vue';
import { onMounted, ref, useTemplateRef } from 'vue';
import DialogSaida from '@/components/pdf-template/DialogSaida.vue';
import PainelBlocos from '@/components/pdf-template/PainelBlocos.vue';
import PainelPropriedades from '@/components/pdf-template/PainelPropriedades.vue';
import { Button } from '@/components/ui/button';
import { useEditorPdf, type SaidaTemplate } from '@/composables/useEditorPdf';

/**
 * O GrapesJS não impõe layout de interface. A única coisa que ele
 * renderiza aqui é o canvas (o iframe da folha). Toolbar, paleta e
 * painel de propriedades são componentes Vue lendo/escrevendo pela API
 * do editor, exposta pelo composable.
 */

const canvas = useTemplateRef<HTMLElement>('canvas');

const editor = useEditorPdf();

const dialogAberto = ref(false);
const saida = ref<SaidaTemplate>({ html: '', css: '' });

onMounted(() => editor.montar(canvas.value!));

function abrirSaida() {
    saida.value = editor.exportar();
    dialogAberto.value = true;
}

function confirmarLimpeza() {
    if (window.confirm('Apagar todo o conteúdo do documento?')) {
        editor.limpar();
    }
}
</script>

<template>
    <div class="flex h-full min-h-0 flex-col overflow-hidden rounded-xl border bg-card">
        <div class="flex flex-wrap items-center gap-1 border-b bg-muted/40 px-3 py-2">
            <Button
                variant="ghost"
                size="sm"
                :disabled="!editor.podeDesfazer.value"
                @click="editor.desfazer"
            >
                <Undo2 /> Desfazer
            </Button>
            <Button
                variant="ghost"
                size="sm"
                :disabled="!editor.podeRefazer.value"
                @click="editor.refazer"
            >
                <Redo2 /> Refazer
            </Button>

            <div class="mx-1 h-5 w-px bg-border" />

            <Button
                variant="ghost"
                size="sm"
                :class="editor.contornos.value && 'bg-accent'"
                @click="editor.alternarContornos"
            >
                <SquareDashed /> Contornos
            </Button>
            <Button
                variant="ghost"
                size="sm"
                class="text-destructive"
                @click="confirmarLimpeza"
            >
                <Trash2 /> Limpar
            </Button>

            <Button size="sm" class="ml-auto" @click="abrirSaida">
                <Code2 /> Ver saída
            </Button>
        </div>

        <div class="flex min-h-0 flex-1">
            <aside class="w-52 shrink-0 overflow-y-auto border-r bg-muted/20 p-3">
                <h3
                    class="mb-2 text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                >
                    Blocos
                </h3>
                <PainelBlocos
                    @arrastar="editor.iniciarArraste"
                    @soltar="editor.terminarArraste"
                    @adicionar="editor.adicionar"
                />
            </aside>

            <!-- O canvas do GrapesJS: um iframe com o tamanho de uma A4.
                 O container PRECISA de largura e altura explícitas — o
                 .gjs-editor é height:100% e colapsaria sem elas. -->
            <div class="min-w-0 flex-1 overflow-auto bg-muted/50 p-6">
                <div
                    ref="canvas"
                    class="mx-auto h-[297mm] w-[210mm] bg-white shadow-lg ring-1 ring-black/10"
                />
            </div>

            <aside class="w-72 shrink-0 overflow-y-auto border-l bg-muted/20 p-3">
                <PainelPropriedades
                    :nome="editor.nomeSelecionado.value"
                    :tipo="editor.tipoSelecionado.value"
                    :traits="editor.traits.value"
                    :atributos="editor.atributos.value"
                    :classes="editor.classes.value"
                    @definir-atributo="editor.definirAtributo"
                    @aplicar-utilitario="editor.aplicarUtilitario"
                    @remover="editor.removerSelecionado"
                />
            </aside>
        </div>

        <div
            v-if="!editor.pronto.value"
            class="border-t px-3 py-1 text-xs text-muted-foreground"
        >
            Carregando editor…
        </div>
    </div>

    <DialogSaida v-model:aberto="dialogAberto" :saida="saida" />
</template>

<style>
/*
 * O GrapesJS traz um CSS próprio para a UI dele. Como desligamos todos
 * os painéis (`custom: true`), sobra só o que envolve o canvas — e ele
 * precisa de dois ajustes para conviver com este projeto.
 */
@import 'grapesjs/dist/css/grapes.min.css';

/*
 * 1) O canvas nasce deslocado 40px no topo e 15% na lateral, reservando
 *    espaço para os painéis padrão que removemos. Sem isso a folha
 *    aparece estreita e cortada.
 */
.gjs-editor {
    --gjs-canvas-top: 0px;
    --gjs-left-width: 0px;
    background-color: transparent;
}

.gjs-cv-canvas {
    inset: 0;
    width: 100%;
    height: 100%;
    padding: 0;
    background-color: transparent;
}

/*
 * 2) CONFLITO COM O TAILWIND: o preflight aplica `svg { display: block }`
 *    globalmente. Os ícones do GrapesJS não declaram largura/altura —
 *    contam com o dimensionamento padrão de `inline`. Com `block` eles
 *    estouram para o tamanho do container e tomam a tela inteira.
 *    Vale para qualquer app Tailwind que embuta o GrapesJS.
 */
.gjs-toolbar-item svg,
.gjs-badge__icon svg,
.gjs-frame-wrapper__icon > svg {
    display: inline-block;
    width: 16px;
    height: 16px;
}
</style>
