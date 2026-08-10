import grapesjs, { type Component, type Editor } from 'grapesjs';
import { computed, onBeforeUnmount, ref, shallowRef } from 'vue';
import { TEMPLATE_INICIAL } from '@/lib/pdf-template/blocos';
import { espelharEstilosDoApp } from '@/lib/pdf-template/estilos-app';
import { registrarTipos } from '@/lib/pdf-template/tipos-componente';
import type { GrupoUtilitario } from '@/lib/pdf-template/utilitarios-tailwind';

export type SaidaTemplate = { html: string; css: string };

export type TraitLeve = {
    name: string;
    label: string;
    type: string;
    options: { value: string; label: string }[];
};

/**
 * O GrapesJS é construído sobre Backbone: o estado dele vive em models
 * que emitem eventos, não em refs do Vue. Este composable é a ponte —
 * escuta os eventos do editor e espelha o que interessa em refs, pra
 * que os componentes Vue possam só renderizar e editar.
 */
export function useEditorPdf() {
    // shallowRef: o Editor é um grafo de objetos Backbone enorme.
    // Deixar o Vue torná-lo reativo em profundidade seria desastroso.
    const editor = shallowRef<Editor | null>(null);
    const selecionado = shallowRef<Component | null>(null);

    const pronto = ref(false);
    const podeDesfazer = ref(false);
    const podeRefazer = ref(false);
    const contornos = ref(true);

    /**
     * Backbone não avisa o Vue quando um model muda. Em vez de tentar
     * tornar o Component reativo, mantemos um contador: qualquer evento
     * de mudança o incrementa, e os `computed` abaixo o leem como
     * dependência pra recalcular.
     */
    const revisao = ref(0);
    const marcarSujo = () => (revisao.value += 1);

    function montar(container: HTMLElement) {
        const inst = grapesjs.init({
            container,

            // Nenhuma UI padrão do GrapesJS: a interface é 100% Vue.
            // Só o canvas (o iframe do documento) é dele.
            panels: { defaults: [] },
            blockManager: { custom: true },
            styleManager: { custom: true },
            traitManager: { custom: true },
            assetManager: { custom: true },
            selectorManager: {
                custom: true,
                // estilo aplicado ao COMPONENTE e não à classe, pra editar
                // um elemento não afetar os irmãos parecidos
                componentFirst: true,
            },

            // Sem persistência: é protótipo. No sistema real, um endpoint.
            storageManager: false,

            // O tamanho da folha A4 vem do container no componente Vue;
            // aqui o editor só preenche o que recebe.
            height: '100%',
            width: '100%',
            keepUnusedStyles: false,

            // CSS que vale só dentro do canvas (a "folha de papel").
            // Não passa pelo CssComposer, então não sai no getCss().
            canvasCss: `
                body {
                    padding: 18mm 16mm;
                    box-sizing: border-box;
                    background: #fff;
                    margin: 0;
                }

                /*
                 * Uma coluna vazia tem altura zero — o GrapesJS não
                 * consegue desenhar indicador de drop numa área sem
                 * pixels, e o bloco arrastado acaba caindo como irmão da
                 * linha. Reservar altura é o que torna a coluna um alvo
                 * de fato. Vale só no canvas: no PDF a coluna vazia volta
                 * a não ocupar espaço.
                 */
                [data-coluna]:empty {
                    min-height: 64px;
                    outline: 1px dashed #d4d4d8;
                    outline-offset: -1px;
                    border-radius: 4px;
                }

                [data-coluna]:empty::after {
                    content: 'Solte um bloco aqui';
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 64px;
                    font-family: ui-sans-serif, system-ui, sans-serif;
                    font-size: 11px;
                    color: #a1a1aa;
                }
            `,
        });

        // Precisa rodar ANTES do parse do conteúdo, senão os
        // <span data-bind> do template não viram `campo-dinamico`.
        registrarTipos(inst);
        inst.setComponents(TEMPLATE_INICIAL);

        // O wrapper é o <body> do canvas. Não-selecionável pra o usuário
        // não estilizar o documento inteiro sem querer.
        inst.getWrapper()?.set({ selectable: false, hoverable: false });

        inst.on('component:selected', () => {
            selecionado.value = inst.getSelected() ?? null;
            marcarSujo();
        });
        inst.on('component:deselected', () => {
            selecionado.value = null;
            marcarSujo();
        });

        inst.on('update component:update', () => {
            podeDesfazer.value = inst.UndoManager.hasUndo();
            podeRefazer.value = inst.UndoManager.hasRedo();
            marcarSujo();
        });

        inst.on('load', () => {
            // sem isso o Tailwind não existe dentro do iframe do canvas
            const doc = inst.Canvas.getDocument();
            if (doc) espelharEstilosDoApp(doc);

            // comando do core que desenha o tracejado em volta dos
            // componentes dentro do canvas
            inst.runCommand('sw-visibility');
            pronto.value = true;
        });

        editor.value = inst;
    }

    // --- estado do componente selecionado, exposto reativamente ---

    const classes = computed<string[]>(() => {
        revisao.value;
        return selecionado.value?.getClasses() ?? [];
    });

    const atributos = computed<Record<string, string>>(() => {
        revisao.value;
        return (selecionado.value?.getAttributes() ?? {}) as Record<
            string,
            string
        >;
    });

    const nomeSelecionado = computed(() => {
        revisao.value;
        const c = selecionado.value;
        if (!c) return null;
        return (
            (c.get('name') as string) ||
            (c.get('type') as string) ||
            (c.get('tagName') as string)
        );
    });

    const tipoSelecionado = computed(() => {
        revisao.value;
        return (selecionado.value?.get('type') as string) ?? null;
    });

    /**
     * Traits declarados no Component Type, achatados pro Vue renderizar.
     * A definição continua morando no type — o painel só lê. Assim,
     * adicionar um trait novo não exige mexer na UI.
     */
    const traits = computed<TraitLeve[]>(() => {
        revisao.value;
        const c = selecionado.value;
        if (!c) return [];

        return c.getTraits().map((t) => ({
            name: t.get('name') ?? '',
            label: (t.get('label') as string) || (t.get('name') ?? ''),
            type: (t.get('type') as string) ?? 'text',
            options: ((t.get('options') as Record<string, string>[]) ?? []).map(
                (o) => ({
                    value: String(o.value ?? o.id),
                    label: String(o.label ?? o.name ?? o.value ?? o.id),
                }),
            ),
        }));
    });

    function definirAtributo(nome: string, valor: string) {
        selecionado.value?.addAttributes({ [nome]: valor });
        marcarSujo();
    }

    /** Qual opção do grupo está aplicada no componente selecionado. */
    function utilitarioAtivo(grupo: GrupoUtilitario): string | null {
        const atuais = classes.value;
        return grupo.opcoes.find((o) => atuais.includes(o.classe))?.classe ?? null;
    }

    /**
     * Aplica um utilitário Tailwind removendo antes as outras opções do
     * mesmo grupo. A remoção é por nome exato dentro da lista do grupo —
     * por isso trocar o alinhamento (`text-center`) não apaga o tamanho
     * (`text-lg`) nem a cor (`text-zinc-600`), que também começam com
     * `text-`. Clicar na opção já ativa a desmarca.
     */
    function aplicarUtilitario(grupo: GrupoUtilitario, classe: string) {
        const c = selecionado.value;
        if (!c) return;

        const jaAtivo = c.getClasses().includes(classe);
        grupo.opcoes.forEach((o) => c.removeClass(o.classe));
        if (!jaAtivo) c.addClass(classe);

        marcarSujo();
    }

    // --- ações da toolbar ---

    const desfazer = () => editor.value?.UndoManager.undo();
    const refazer = () => editor.value?.UndoManager.redo();
    const limpar = () => editor.value?.Components.clear();
    const removerSelecionado = () => selecionado.value?.remove();

    function alternarContornos() {
        const inst = editor.value;
        if (!inst) return;
        contornos.value = !contornos.value;
        contornos.value
            ? inst.runCommand('sw-visibility')
            : inst.stopCommand('sw-visibility');
    }

    /**
     * Arrastar da paleta Vue pro canvas. `Canvas.startDrag` é a API
     * genérica de drag-and-drop customizado do GrapesJS — não exige
     * registrar nada no BlockManager, o que nos deixa manter a paleta
     * como dados TS renderizados por um componente Vue.
     */
    function iniciarArraste(conteudo: Record<string, unknown>) {
        editor.value?.Canvas.startDrag({ content: conteudo });
    }

    const terminarArraste = () => editor.value?.Canvas.endDrag();

    /** Fallback de clique: acrescenta o bloco no fim do documento. */
    function adicionar(conteudo: Record<string, unknown>) {
        const inst = editor.value;
        if (!inst) return;
        const [novo] = inst.addComponents(conteudo);
        if (novo) inst.select(novo);
    }

    /** A saída que alimentaria o pipeline PHP de HTML → PDF. */
    function exportar(): SaidaTemplate {
        const inst = editor.value;
        if (!inst) return { html: '', css: '' };

        return {
            html: inst.getHtml({ cleanId: true }),
            css: inst.getCss({ keepUnusedStyles: false }) ?? '',
        };
    }

    onBeforeUnmount(() => {
        editor.value?.destroy();
        editor.value = null;
        pronto.value = false;
    });

    return {
        editor,
        pronto,
        selecionado,
        nomeSelecionado,
        tipoSelecionado,
        classes,
        atributos,
        traits,
        podeDesfazer,
        podeRefazer,
        contornos,
        montar,
        definirAtributo,
        utilitarioAtivo,
        aplicarUtilitario,
        desfazer,
        refazer,
        limpar,
        removerSelecionado,
        alternarContornos,
        iniciarArraste,
        terminarArraste,
        adicionar,
        exportar,
    };
}
