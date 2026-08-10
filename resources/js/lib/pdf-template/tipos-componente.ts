import type { Component, Editor } from 'grapesjs';
import { createApp, h, ref, type App } from 'vue';
import CampoDinamico from '@/components/pdf-template/canvas/CampoDinamico.vue';
import { CAMPO_PADRAO, OPCOES_TRAIT_CAMPO } from '@/lib/pdf-template/schema';

export const TIPO_CAMPO = 'campo-dinamico';
export const TIPO_LINHA = 'linha-colunas';
export const TIPO_COLUNA = 'coluna';

/**
 * COMPONENT TYPE CUSTOMIZADO — a peça central do binding de dados.
 *
 * Um "type" no GrapesJS tem duas metades:
 *
 *   model -> o que o componente É: tag, atributos, o que pode/não pode
 *            fazer, e quais Traits o editam. É o model que é serializado
 *            no `editor.getHtml()`.
 *
 *   view  -> como ele APARECE no canvas durante a edição. Aqui a gente
 *            sequestra essa metade e monta um componente Vue no lugar.
 *
 * Essa separação é o que faz a coisa funcionar pro nosso caso: a
 * aparência pode ser rica (Vue), enquanto o HTML exportado continua
 * sendo um `<span data-bind="..."></span>` limpo pro PHP preencher.
 */
export function registrarTipos(editor: Editor) {
    registrarColunas(editor);

    editor.Components.addType(TIPO_CAMPO, {
        /**
         * O reconhecedor. Quando o GrapesJS faz parse de um HTML, ele
         * pergunta a cada type registrado "esse elemento é você?".
         * É assim que um template salvo no banco volta a ser um
         * campo-dinamico ao ser reaberto.
         */
        isComponent: (el) =>
            el.tagName === 'SPAN' && el.hasAttribute?.('data-bind')
                ? { type: TIPO_CAMPO }
                : undefined,

        model: {
            defaults: {
                tagName: 'span',
                name: 'Campo dinâmico',

                // não é texto livre — o usuário não digita o dado
                editable: false,
                droppable: false,

                // `textable` permite arrastá-lo pra dentro de um parágrafo
                textable: true,

                // sem filhos: o placeholder que aparece no canvas é
                // desenhado pelo Vue e não faz parte do model
                components: [],

                attributes: { 'data-bind': CAMPO_PADRAO },

                /**
                 * Traits editam ATRIBUTOS (≠ Style Manager, que edita CSS).
                 * Mesmo montando nosso próprio painel em Vue, vale declarar
                 * o trait aqui: ele é a fonte da verdade sobre o que é
                 * editável neste componente, e o painel Vue só lê daqui.
                 */
                traits: [
                    {
                        type: 'select',
                        name: 'data-bind',
                        label: 'Campo de dados',
                        options: OPCOES_TRAIT_CAMPO,
                    },
                ],
            },
        },

        view: {
            /**
             * `onRender` roda toda vez que o GrapesJS (re)desenha o
             * elemento no canvas — e ao redesenhar ele limpa os filhos,
             * levando junto o DOM do Vue. Por isso montamos num <span>
             * próprio e, se ele sumir, remontamos.
             *
             * `this.el` é o <span> real, dentro do documento do iframe.
             */
            onRender(this: any) {
                const el: HTMLElement = this.el;
                if (this.pontoVue && el.contains(this.pontoVue)) return;

                this.appVue?.unmount();

                const model = this.model;

                // um ref só por view, senão cada remontagem acumularia
                // um listener novo no model
                if (!this.campoRef) {
                    this.campoRef = ref<string>(
                        model.getAttributes()['data-bind'] ?? CAMPO_PADRAO,
                    );

                    // o model é Backbone: dá pra ouvir a mudança de um
                    // atributo específico e refletir no ref do Vue
                    model.on('change:attributes:data-bind', () => {
                        this.campoRef.value =
                            model.getAttributes()['data-bind'];
                    });
                }

                // criado pelo document do iframe, não pelo da página
                const ponto = el.ownerDocument.createElement('span');
                el.appendChild(ponto);

                const app: App = createApp({
                    render: () =>
                        h(CampoDinamico, { campo: this.campoRef.value }),
                });
                app.mount(ponto);

                this.appVue = app;
                this.pontoVue = ponto;
            },

            /** Sem isso, cada componente removido vaza um app Vue montado. */
            removed(this: any) {
                this.appVue?.unmount();
                this.appVue = null;
                this.pontoVue = null;
            },
        },
    });
}

/** Largura de cada coluna quando a linha é dividida em partes iguais. */
const LARGURAS_IGUAIS: Record<number, string> = {
    2: 'w-1/2',
    3: 'w-1/3',
    4: 'w-1/4',
};

/**
 * COLUNAS — o que permite dois elementos lado a lado.
 *
 * São dois types que só existem em par: a LINHA é o container flex, e a
 * COLUNA é a única coisa que pode viver dentro dela. As restrições de
 * `droppable`/`draggable` são o que impede o usuário não técnico de
 * desmontar a estrutura soltando um bloco no lugar errado.
 *
 * Por que flex e não tabela: o pipeline de PDF é Browsershot, ou seja,
 * Chromium de verdade — o que o canvas mostra é o que o PDF renderiza.
 *
 * Por que `w-1/2` funciona junto com `gap-4`: item flex encolhe por
 * padrão, e o encolhimento é proporcional à base. Duas colunas de 50%
 * com 16px de gap viram 50% - 8px cada; 1/3 e 2/3 continuam exatamente
 * 1:2 depois do desconto. Não precisa de `calc()` nem de basis.
 */
function registrarColunas(editor: Editor) {
    editor.Components.addType(TIPO_LINHA, {
        isComponent: (el) =>
            el.hasAttribute?.('data-linha') ? { type: TIPO_LINHA } : undefined,

        model: {
            defaults: {
                tagName: 'div',
                name: 'Linha',
                attributes: { 'data-linha': '', 'data-colunas': '2' },
                classes: ['flex', 'gap-4', 'mb-4'],

                /**
                 * Só colunas entram numa linha. Precisa ser FUNÇÃO, e não
                 * seletor em string: a forma em string é resolvida com
                 * `source.getEl()`, e um bloco arrastado da paleta ainda
                 * não tem elemento no DOM — a checagem falharia sempre.
                 */
                droppable: (source: Component) =>
                    source?.get('type') === TIPO_COLUNA,

                traits: [
                    {
                        type: 'select',
                        name: 'data-colunas',
                        label: 'Quantidade de colunas',
                        options: [
                            { id: '2', value: '2', label: '2 colunas' },
                            { id: '3', value: '3', label: '3 colunas' },
                            { id: '4', value: '4', label: '4 colunas' },
                        ],
                    },
                ],
            },

            init(this: any) {
                this.on('change:attributes:data-colunas', this.ajustarColunas);
            },

            /**
             * Mantém a quantidade de filhos igual ao trait. Trocar a
             * quantidade é uma ação explícita do usuário, então também
             * volta as larguras pra partes iguais — senão sobrariam
             * colunas antigas de `w-1/2` ao lado de uma nova de `w-1/3`.
             */
            ajustarColunas(this: any) {
                const alvo = Number(this.getAttributes()['data-colunas']) || 2;
                const colunas = this.components();

                while (colunas.length > alvo) {
                    colunas.at(colunas.length - 1).remove();
                }
                while (colunas.length < alvo) {
                    colunas.add({ type: TIPO_COLUNA });
                }

                colunas.each((coluna: Component) => {
                    coluna
                        .getClasses()
                        .filter((c: string) => c.startsWith('w-'))
                        .forEach((c: string) => coluna.removeClass(c));
                    coluna.addClass(LARGURAS_IGUAIS[alvo]);
                });
            },
        },
    });

    editor.Components.addType(TIPO_COLUNA, {
        isComponent: (el) =>
            el.hasAttribute?.('data-coluna')
                ? { type: TIPO_COLUNA }
                : undefined,

        model: {
            defaults: {
                tagName: 'div',
                name: 'Coluna',
                attributes: { 'data-coluna': '' },

                // `min-w-0` impede que uma palavra longa ou uma imagem
                // estoure a fração e empurre a coluna vizinha
                classes: ['w-1/2', 'min-w-0'],

                // uma coluna só faz sentido dentro de uma linha; soltá-la
                // no documento é rejeitado em vez de gerar um órfão
                draggable: (_source: Component, target: Component) =>
                    target?.get('type') === TIPO_LINHA,
            },
        },
    });
}
