import type { Editor } from 'grapesjs';
import { createApp, h, ref, type App } from 'vue';
import CampoDinamico from '@/components/pdf-template/canvas/CampoDinamico.vue';
import { CAMPO_PADRAO, OPCOES_TRAIT_CAMPO } from '@/lib/pdf-template/schema';

export const TIPO_CAMPO = 'campo-dinamico';

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
