import {
    Columns2,
    Heading1,
    Image,
    Minus,
    Square,
    Tag,
    Type,
} from '@lucide/vue';
import type { Component as ComponenteVue } from 'vue';
import {
    TIPO_CAMPO,
    TIPO_COLUNA,
    TIPO_LINHA,
} from '@/lib/pdf-template/tipos-componente';

/**
 * BLOCOS
 *
 * No GrapesJS um "bloco" é só um atalho: um pedaço de conteúdo que vira
 * componente ao ser solto no canvas. O conteúdo pode ser string de HTML
 * *ou* uma **definição de componente** — um objeto. Usamos objetos: é
 * tipado, dá pra compor, e não vira HTML solto no meio do código.
 *
 * O visual vem de classes Tailwind (`classes: [...]`), as mesmas do
 * resto do produto. Nada de CSS gerado à parte.
 *
 * Propositalmente genéricos por enquanto. Quando forem virar
 * "componentes base", cada um pode ganhar um Component Type próprio com
 * view em Vue, igual ao `campo-dinamico`.
 */

export type DefinicaoBloco = {
    id: string;
    rotulo: string;
    icone: ComponenteVue;
    /** Definição de componente do GrapesJS. */
    conteudo: Record<string, unknown>;
};

export const BLOCOS: DefinicaoBloco[] = [
    {
        id: 'titulo',
        rotulo: 'Título',
        icone: Heading1,
        conteudo: {
            type: 'text',
            name: 'Título',
            tagName: 'h1',
            components: 'Título do documento',
            classes: ['text-3xl', 'font-bold', 'text-center', 'mb-4'],
        },
    },
    {
        id: 'paragrafo',
        rotulo: 'Parágrafo',
        icone: Type,
        conteudo: {
            type: 'text',
            name: 'Parágrafo',
            tagName: 'p',
            components: 'Clique duas vezes para editar este texto.',
            classes: ['text-base', 'text-zinc-900', 'mb-4'],
        },
    },
    {
        id: 'campo',
        rotulo: 'Campo dinâmico',
        icone: Tag,
        conteudo: { type: TIPO_CAMPO },
    },
    {
        id: 'imagem',
        rotulo: 'Imagem',
        icone: Image,
        conteudo: {
            type: 'image',
            name: 'Imagem',
            classes: ['w-1/3', 'mb-4'],
        },
    },
    {
        id: 'caixa',
        rotulo: 'Caixa',
        icone: Square,
        conteudo: {
            tagName: 'div',
            name: 'Caixa',
            classes: [
                'bg-zinc-100',
                'border',
                'border-zinc-200',
                'rounded-lg',
                'p-4',
                'mb-4',
            ],
            components: [
                {
                    type: 'text',
                    name: 'Rótulo',
                    tagName: 'div',
                    components: 'Rótulo',
                    classes: ['text-xs', 'uppercase', 'text-zinc-600'],
                },
                {
                    tagName: 'div',
                    name: 'Valor',
                    classes: ['text-lg', 'font-bold'],
                    components: [{ type: TIPO_CAMPO }],
                },
            ],
        },
    },
    {
        id: 'colunas',
        rotulo: 'Colunas',
        icone: Columns2,
        // Nasce com duas; o trait "Quantidade de colunas" no painel leva
        // a três ou quatro. Toda a estrutura mora no Component Type.
        conteudo: {
            type: TIPO_LINHA,
            components: [{ type: TIPO_COLUNA }, { type: TIPO_COLUNA }],
        },
    },
    {
        id: 'divisoria',
        rotulo: 'Divisória',
        icone: Minus,
        conteudo: {
            tagName: 'hr',
            name: 'Divisória',
            classes: ['border-0', 'border-t', 'border-zinc-200', 'mb-4'],
        },
    },
];

/** Conteúdo com que o editor abre, pra já ter algo pra mexer. */
export const TEMPLATE_INICIAL = `
    <h1 class="text-3xl font-bold text-center mb-2">CERTIFICADO</h1>
    <p class="text-sm text-zinc-600 text-center mb-8">
        Emitido em <span data-bind="data_emissao"></span>
    </p>
    <p class="text-base text-zinc-900 mb-6">
        Certificamos que <span data-bind="nome_cliente"></span>, portador do documento
        <span data-bind="documento_cliente"></span>, concluiu o curso
        <span data-bind="curso_nome"></span>, com carga horária de
        <span data-bind="carga_horaria"></span>.
    </p>
    <div data-linha data-colunas="2" class="flex gap-4 mb-4">
        <div data-coluna class="w-1/2 min-w-0">
            <div class="bg-zinc-100 border border-zinc-200 rounded-lg p-4">
                <div class="text-xs uppercase text-zinc-600">Valor total</div>
                <div class="text-lg font-bold"><span data-bind="valor_total"></span></div>
            </div>
        </div>
        <div data-coluna class="w-1/2 min-w-0">
            <div class="bg-zinc-100 border border-zinc-200 rounded-lg p-4">
                <div class="text-xs uppercase text-zinc-600">Emitido em</div>
                <div class="text-lg font-bold"><span data-bind="data_emissao"></span></div>
            </div>
        </div>
    </div>
`;
