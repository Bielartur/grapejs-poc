<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download, Lock, Minus, Plus, Save, Undo2, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import ListaBlocos from '@/components/fatura/ListaBlocos.vue';
import PreviewFatura from '@/components/fatura/PreviewFatura.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useLayoutFatura } from '@/composables/useLayoutFatura';
import type {
    BlocoCatalogo,
    Layout,
    Tema,
} from '@/composables/useLayoutFatura';
import { dashboard } from '@/routes';
import { baixar } from '@/routes/fatura/pdf';
import type { DadosFatura } from '@/types/fatura';

/**
 * EDITOR DE LAYOUT DA FATURA
 *
 * O usuário escolhe QUAIS blocos entram e em que ordem — nunca monta
 * layout livre.
 *
 * O documento é renderizado pelos componentes de `fatura/documento/`, os
 * MESMOS que o Browsershot abre pra gerar o PDF. Por isso o preview não
 * é uma aproximação — e por isso nenhuma marcação de fatura pode ser
 * escrita fora daquela pasta.
 */

const props = defineProps<{
    catalogo: Record<string, BlocoCatalogo>;
    layout: Layout;
    /** Valores válidos de cada chave do tema, vindos do PHP. */
    temaOpcoes: Record<keyof Tema, Record<string, string>>;
    /** Fatura de exemplo, para o documento renderizado em Vue. */
    dados: DadosFatura;
}>();

const editor = useLayoutFatura(props.layout);

const selecionado = ref<string | null>(null);
const folhasPorPagina = ref<number[]>([]);
const zoom = ref(1);
const salvando = ref(false);
const gerandoPdf = ref(false);
const erroPdf = ref<string | null>(null);

const totalFolhas = computed(() =>
    folhasPorPagina.value.reduce((soma, n) => soma + n, 0),
);

async function salvar() {
    salvando.value = true;
    await editor.persistir();
    salvando.value = false;
}

/**
 * Gera o PDF da configuração que está na tela, salva ou não.
 *
 * É POST porque o schema inteiro vai no corpo — e a resposta é binária,
 * daí baixar via blob em vez de navegar para a URL.
 */
async function baixarPdf() {
    gerandoPdf.value = true;
    erroPdf.value = null;

    try {
        const resposta = await fetch(baixar.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
            },
            body: JSON.stringify(editor.layout.value),
        });

        if (!resposta.ok) {
            throw new Error(`HTTP ${resposta.status}`);
        }

        const url = URL.createObjectURL(await resposta.blob());
        const link = document.createElement('a');

        link.href = url;
        link.download = 'fatura.pdf';
        link.click();

        URL.revokeObjectURL(url);
    } catch (e) {
        erroPdf.value = e instanceof Error ? e.message : 'Falha ao gerar';
    } finally {
        gerandoPdf.value = false;
    }
}

const ajustarZoom = (delta: number) =>
    (zoom.value = Math.min(
        1.5,
        Math.max(0.5, +(zoom.value + delta).toFixed(2)),
    ));
</script>

<template>
    <Head title="Layout da fatura" />

    <!--
        Esta página não herda o AppLayout (ver o resolvedor em `app.ts`):
        ela É a casca. Daí `h-svh` + `overflow-hidden` aqui em cima — é o
        que prende a altura e faz o scroll acontecer só na coluna do
        preview, em vez de na janela inteira.
    -->
    <div class="flex h-svh flex-col overflow-hidden bg-background">
        <header
            class="flex flex-wrap items-start justify-between gap-4 border-b px-4 py-3"
        >
            <div class="flex items-start gap-3">
                <!-- única saída da tela cheia -->
                <Button
                    as-child
                    variant="ghost"
                    size="icon-sm"
                    title="Sair do editor"
                    class="mt-0.5"
                >
                    <Link :href="dashboard()">
                        <X />
                    </Link>
                </Button>

                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-lg font-semibold">Layout da fatura</h1>
                        <Badge variant="secondary" class="gap-1 text-[11px]">
                            <Lock class="size-3" /> Somente gestores
                        </Badge>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Modelo global da empresa · vale para todas as faturas
                        emitidas a partir do salvamento.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span v-if="erroPdf" class="text-xs text-destructive">
                    PDF falhou: {{ erroPdf }}
                </span>

                <!-- gera do estado atual da tela, salvo ou não -->
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="gerandoPdf"
                    @click="baixarPdf"
                >
                    <Download />
                    {{ gerandoPdf ? 'Gerando…' : 'Baixar PDF' }}
                </Button>

                <Button
                    variant="outline"
                    size="sm"
                    :disabled="!editor.sujo.value"
                    @click="editor.descartar"
                >
                    <Undo2 /> Descartar
                </Button>
                <Button
                    size="sm"
                    :disabled="!editor.sujo.value || salvando"
                    @click="salvar"
                >
                    <Save /> {{ salvando ? 'Salvando…' : 'Salvar layout' }}
                </Button>
            </div>
        </header>

        <div class="flex min-h-0 flex-1">
            <aside class="w-96 shrink-0 overflow-y-auto border-r p-4">
                <ListaBlocos
                    :layout="editor.layout.value"
                    :catalogo="catalogo"
                    :folhas-por-pagina="folhasPorPagina"
                    :selecionado="selecionado"
                    @alternar-ativo="editor.alternarAtivo"
                    @definir-opcao="editor.definirOpcao"
                    @mover="editor.mover"
                    @nova-pagina="editor.novaPagina"
                    @selecionar="selecionado = $event"
                />

                <section class="mt-6 flex flex-col gap-3 border-t pt-4">
                    <h2 class="text-sm font-semibold">Aparência</h2>
                    <p class="text-xs leading-relaxed text-muted-foreground">
                        Ajustes sutis que valem para o documento inteiro. Não há
                        controle de posição ou tamanho — isso é do bloco.
                    </p>

                    <div
                        v-for="(valores, chave) in temaOpcoes"
                        :key="chave"
                        class="flex items-center justify-between gap-3"
                    >
                        <Label class="text-xs font-normal capitalize">{{
                            chave
                        }}</Label>
                        <Select
                            :model-value="editor.layout.value.tema[chave]"
                            @update:model-value="
                                editor.layout.value.tema[chave] = String($event)
                            "
                        >
                            <SelectTrigger class="h-7 w-40 text-xs">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="(rotulo, valor) in valores"
                                    :key="valor"
                                    :value="String(valor)"
                                >
                                    {{ rotulo }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </section>
            </aside>

            <main class="flex min-w-0 flex-1 flex-col">
                <div
                    class="flex flex-wrap items-center gap-3 border-b px-4 py-2"
                >
                    <span class="text-sm text-muted-foreground">
                        Fatura de exemplo · Maria Silva Andrade
                    </span>

                    <div class="ml-auto flex items-center gap-1">
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            @click="ajustarZoom(-0.1)"
                        >
                            <Minus />
                        </Button>
                        <span class="w-12 text-center text-sm tabular-nums">
                            {{ Math.round(zoom * 100) }}%
                        </span>
                        <Button
                            variant="ghost"
                            size="icon-sm"
                            @click="ajustarZoom(0.1)"
                        >
                            <Plus />
                        </Button>
                    </div>

                    <span class="text-sm text-muted-foreground">
                        {{ totalFolhas }}
                        {{ totalFolhas === 1 ? 'folha' : 'folhas' }} no PDF
                    </span>
                </div>

                <div class="min-h-0 flex-1 overflow-auto bg-muted/40">
                    <!--
                        Recebe a configuração, não HTML: os componentes do
                        documento reagem direto ao estado do editor. É o
                        mesmo documento que o Browsershot renderiza.
                    -->
                    <PreviewFatura
                        :paginas="editor.layout.value.paginas"
                        :tema="editor.layout.value.tema"
                        :dados="dados"
                        :selecionado="selecionado"
                        :zoom="zoom"
                        @medido="folhasPorPagina = $event"
                        @selecionar="selecionado = $event"
                    />
                </div>
            </main>
        </div>
    </div>
</template>
