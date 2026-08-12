<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Download, Lock, Minus, Plus, Save, Undo2, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import ListaBlocos from '@/components/fatura/ListaBlocos.vue';
import PainelElemento from '@/components/fatura/PainelElemento.vue';
import PainelVariantes from '@/components/fatura/PainelVariantes.vue';
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
    Posicao,
    Tema,
} from '@/composables/useLayoutFatura';
import { dashboard } from '@/routes';
import { baixar } from '@/routes/fatura/pdf';
import type { DadosFatura } from '@/types/fatura';

/**
 * EDITOR DE LAYOUT DA FATURA
 *
 * O usuário escolhe QUAIS blocos entram, em que ordem e com QUAL forma —
 * nunca monta layout livre.
 *
 * A sidebar tem dois níveis. O primeiro é a estrutura do documento
 * (`ListaBlocos`); clicar num bloco abre o segundo (`PainelVariantes`),
 * onde as formas daquele bloco aparecem renderizadas de verdade. Esta
 * página é quem guarda em qual dos dois se está — os painéis não sabem
 * um do outro.
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
    /*
     * O vocabulário de estilo, já vindo do backend. Ainda sem consumidor
     * na tela: o painel que os usa é a sidebar direita, que não existe
     * ainda. Quais eixos cada elemento aceita vem dentro do `catalogo`.
     */
    estiloEscalas: Record<string, Record<string, string>>;
    estiloPaleta: Record<string, string>;
    estiloEixosDeCor: string[];
}>();

const editor = useLayoutFatura(props.layout);

/**
 * Qual bloco está aberto na subpágina — `null` é o primeiro nível.
 *
 * Guarda a POSIÇÃO, não o id, porque é posição o que os comandos do
 * editor (`definirVariante`, `definirOpcao`) recebem. O bloco em si sai
 * daí por `blocoAberto`, sempre lido do estado atual: assim editar
 * enquanto a subpágina está aberta não deixa uma cópia velha na tela.
 */
const abertoEm = ref<Posicao | null>(null);

const blocoAberto = computed(() =>
    abertoEm.value
        ? (editor.layout.value.paginas[abertoEm.value.pagina]?.[
              abertoEm.value.indice
          ] ?? null)
        : null,
);

const selecionado = ref<string | null>(null);

/**
 * O elemento em edição dentro do bloco selecionado.
 *
 * É o que abre a sidebar da direita. Guarda só o nome porque o bloco já
 * está em `abertoEm`/`selecionado` — e porque o mesmo nome (`titulo`)
 * existe em vários blocos, então sem o par ele não identifica nada.
 */
const elementoSelecionado = ref<string | null>(null);

/** A definição do elemento em edição, ou `null` quando não há um. */
const elementoEmEdicao = computed(() => {
    const bloco = blocoAberto.value;
    const nome = elementoSelecionado.value;

    if (!bloco || !nome) {
        return null;
    }

    const definicao = props.catalogo[bloco.id]?.elementos?.[nome];

    return definicao ? { bloco, nome, definicao } : null;
});

const folhasPorPagina = ref<number[]>([]);
const zoom = ref(1);
const salvando = ref(false);
const gerandoPdf = ref(false);
const erroPdf = ref<string | null>(null);

const totalFolhas = computed(() =>
    folhasPorPagina.value.reduce((soma, n) => soma + n, 0),
);

/** Abrir a subpágina também destaca o bloco no preview. */
function abrir(pos: Posicao) {
    abertoEm.value = pos;
    selecionado.value =
        editor.layout.value.paginas[pos.pagina]?.[pos.indice]?.id ?? null;
}

function fechar() {
    abertoEm.value = null;
    selecionado.value = null;
    elementoSelecionado.value = null;
}

/**
 * Clicar no documento: abre a subpágina do bloco à esquerda e, quando o
 * clique caiu num elemento marcado, o painel dele à direita.
 *
 * A busca é por id porque é só isso que o preview sabe — ele lê o
 * `data-bloco` do DOM. Funciona porque cada bloco do catálogo aparece no
 * máximo uma vez no layout.
 */
function selecionarNoPreview(id: string | null, elemento: string | null) {
    if (!id) {
        fechar();

        return;
    }

    editor.layout.value.paginas.forEach((blocos, pagina) => {
        const indice = blocos.findIndex((b) => b.id === id);

        if (indice >= 0) {
            abrir({ pagina, indice });
        }
    });

    elementoSelecionado.value = elemento;
}

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
            <!--
                Os dois níveis se substituem no mesmo espaço, em vez de
                empilhar: a subpágina precisa da largura inteira pros
                cartões de esquema caberem sem espremer.
            -->
            <aside class="w-96 shrink-0 overflow-y-auto border-r p-4">
                <PainelVariantes
                    v-if="blocoAberto"
                    :bloco="blocoAberto"
                    :definicao="catalogo[blocoAberto.id]"
                    @voltar="fechar"
                    @definir-variante="
                        abertoEm && editor.definirVariante(abertoEm, $event)
                    "
                    @definir-opcao="
                        (chave, valor) =>
                            abertoEm &&
                            editor.definirOpcao(abertoEm, chave, valor)
                    "
                />

                <template v-else>
                    <ListaBlocos
                        :layout="editor.layout.value"
                        :catalogo="catalogo"
                        :folhas-por-pagina="folhasPorPagina"
                        :selecionado="selecionado"
                        @abrir="abrir"
                        @mover="editor.mover"
                        @nova-pagina="editor.novaPagina"
                    />

                    <section class="mt-6 flex flex-col gap-3 border-t pt-4">
                        <h2 class="text-sm font-semibold">Aparência</h2>
                        <p
                            class="text-xs leading-relaxed text-muted-foreground"
                        >
                            Ajustes sutis que valem para o documento inteiro.
                            Não há controle de posição ou tamanho — isso é do
                            bloco.
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
                                    editor.layout.value.tema[chave] =
                                        String($event)
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
                </template>
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
                        :elemento-selecionado="elementoSelecionado"
                        :zoom="zoom"
                        @medido="folhasPorPagina = $event"
                        @selecionar="selecionarNoPreview"
                    />
                </div>
            </main>

            <!--
                A TERCEIRA COLUNA — o painel do elemento.

                Só existe quando há elemento selecionado, e some junto com
                ele. Ocupar espaço fixo custaria largura do papel o tempo
                todo por uma tela que na maior parte do tempo não está em
                uso.
            -->
            <aside
                v-if="elementoEmEdicao"
                class="w-72 shrink-0 overflow-y-auto border-l p-4"
            >
                <PainelElemento
                    :bloco="elementoEmEdicao.bloco"
                    :rotulo-bloco="catalogo[elementoEmEdicao.bloco.id].rotulo"
                    :elemento="elementoEmEdicao.nome"
                    :rotulo-elemento="elementoEmEdicao.definicao.rotulo"
                    :eixos="elementoEmEdicao.definicao.eixos"
                    :escalas="estiloEscalas"
                    :paleta="estiloPaleta"
                    :eixos-de-cor="estiloEixosDeCor"
                    @fechar="elementoSelecionado = null"
                    @definir-estilo="
                        (eixo, valor) =>
                            abertoEm &&
                            editor.definirEstilo(
                                abertoEm,
                                elementoSelecionado!,
                                eixo,
                                valor,
                            )
                    "
                />
            </aside>
        </div>
    </div>
</template>
