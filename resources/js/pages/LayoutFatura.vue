<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Lock, Minus, Plus, Save, Undo2, X } from '@lucide/vue';
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
import { dashboard } from '@/routes';
import type {
    BlocoCatalogo,
    Layout,
    Tema,
} from '@/composables/useLayoutFatura';

/**
 * EDITOR DE LAYOUT DA FATURA
 *
 * O usuário escolhe QUAIS blocos entram e em que ordem — nunca monta
 * layout livre. Cada bloco é um partial Blade no backend, o mesmo que
 * gera o PDF, então o preview não é uma aproximação: é o documento.
 */

const props = defineProps<{
    catalogo: Record<string, BlocoCatalogo>;
    layout: Layout;
    /** Valores válidos de cada chave do tema, vindos do PHP. */
    temaOpcoes: Record<keyof Tema, Record<string, string>>;
}>();

const editor = useLayoutFatura(props.layout);

const selecionado = ref<string | null>(null);
const folhasPorPagina = ref<number[]>([]);
const zoom = ref(1);
const salvando = ref(false);

const totalFolhas = computed(() =>
    folhasPorPagina.value.reduce((soma, n) => soma + n, 0),
);

async function salvar() {
    salvando.value = true;
    await editor.persistir();
    salvando.value = false;
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
                    <p
                        v-if="editor.erro.value"
                        class="m-4 rounded-lg border border-destructive/40 bg-destructive/10 p-3 text-sm text-destructive"
                    >
                        Não foi possível renderizar o preview:
                        {{ editor.erro.value }}
                    </p>

                    <PreviewFatura
                        :paginas="editor.paginas.value"
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
