<script setup lang="ts">
import { Check, Copy } from '@lucide/vue';
import { computed, nextTick, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { SaidaTemplate } from '@/composables/useEditorPdf';
import { espelharEstilosDoApp } from '@/lib/pdf-template/estilos-app';
import { aplicarDados } from '@/lib/pdf-template/schema';

const props = defineProps<{ saida: SaidaTemplate }>();
const aberto = defineModel<boolean>('aberto', { required: true });

type Aba = 'html' | 'css' | 'preview';
const aba = ref<Aba>('html');
const copiado = ref(false);
const iframe = ref<HTMLIFrameElement | null>(null);

const ABAS: { id: Aba; rotulo: string }[] = [
    { id: 'html', rotulo: 'HTML do template' },
    { id: 'css', rotulo: 'CSS gerado' },
    { id: 'preview', rotulo: 'Com dados reais' },
];

/**
 * Simula o que o PHP faria: varrer o HTML atrás de [data-bind] e trocar
 * o conteúdo pelo dado real do registro. É a prova de que o template
 * exportado é suficiente pra gerar o documento final.
 */
const htmlComDados = computed(() => aplicarDados(props.saida.html));

const conteudoAtual = computed(() =>
    aba.value === 'css' ? props.saida.css : props.saida.html,
);

async function copiar() {
    await navigator.clipboard.writeText(conteudoAtual.value);
    copiado.value = true;
    setTimeout(() => (copiado.value = false), 1500);
}

/** Monta a pré-visualização num iframe, com o CSS do app espelhado. */
async function renderizarPreview() {
    await nextTick();
    const doc = iframe.value?.contentDocument;
    if (!doc) return;

    doc.open();
    doc.write('<!doctype html><html><head></head><body></body></html>');
    doc.close();

    espelharEstilosDoApp(doc);

    const estilosDoTemplate = doc.createElement('style');
    estilosDoTemplate.textContent = props.saida.css;
    doc.head.appendChild(estilosDoTemplate);

    doc.body.className = 'bg-white p-8';
    doc.body.innerHTML = htmlComDados.value;
}

watch([aba, aberto], () => {
    if (aberto.value && aba.value === 'preview') renderizarPreview();
});
</script>

<template>
    <Dialog v-model:open="aberto">
        <DialogContent class="flex max-h-[85vh] flex-col sm:max-w-4xl">
            <DialogHeader>
                <DialogTitle>Saída do editor</DialogTitle>
                <DialogDescription>
                    É isto que seria salvo e enviado ao pipeline PHP de
                    geração de PDF.
                </DialogDescription>
            </DialogHeader>

            <div class="flex gap-1 rounded-lg bg-muted p-1">
                <button
                    v-for="item in ABAS"
                    :key="item.id"
                    type="button"
                    class="flex-1 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="
                        aba === item.id
                            ? 'bg-background shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="aba = item.id"
                >
                    {{ item.rotulo }}
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-auto rounded-lg border">
                <iframe
                    v-show="aba === 'preview'"
                    ref="iframe"
                    class="h-[55vh] w-full bg-white"
                    title="Pré-visualização com dados reais"
                />

                <pre
                    v-if="aba !== 'preview'"
                    class="overflow-auto p-4 text-xs leading-relaxed"
                ><code>{{ conteudoAtual || '/* vazio */' }}</code></pre>
            </div>

            <p
                v-if="aba === 'css'"
                class="text-xs leading-relaxed text-muted-foreground"
            >
                Só aparece aqui o CSS que o próprio editor gerou. O visual
                do documento vem das classes Tailwind no HTML — o
                renderizador de PDF precisa receber a folha do Tailwind
                junto.
            </p>

            <p
                v-else-if="aba === 'html'"
                class="text-xs leading-relaxed text-muted-foreground"
            >
                Repare que os
                <code class="rounded bg-muted px-1">&lt;span data-bind&gt;</code>
                saem vazios: o placeholder que você vê no canvas é
                desenhado pelo Vue e não faz parte do template.
            </p>

            <div class="flex justify-end">
                <Button
                    v-if="aba !== 'preview'"
                    variant="outline"
                    size="sm"
                    @click="copiar"
                >
                    <Check v-if="copiado" />
                    <Copy v-else />
                    {{ copiado ? 'Copiado' : 'Copiar' }}
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
