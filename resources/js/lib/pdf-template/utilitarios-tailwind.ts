/**
 * TOKENS DE ESTILO — utilitários Tailwind curados
 *
 * Em vez de expor o Style Manager do GrapesJS (que deixa escrever
 * qualquer CSS), o painel de propriedades manipula CLASSES do Tailwind.
 * Duas consequências boas:
 *
 *  1. O usuário só escolhe entre tokens da nossa design system —
 *     é o freio contra "viajar" no layout, sem precisar travar componente.
 *  2. O HTML exportado sai em utilitários puros, do mesmo jeito que o
 *     resto do produto, em vez de um punhado de regras CSS geradas.
 *
 * Cada grupo é MUTUAMENTE EXCLUSIVO: aplicar `text-center` remove
 * `text-left`/`text-right`/`text-justify`, mas não mexe em `text-lg`
 * nem em `text-zinc-600`, porque a remoção é feita por nome exato
 * dentro da lista do próprio grupo.
 *
 * Os nomes de classe precisam aparecer literais aqui — é assim que o
 * Tailwind v4 os detecta ao varrer os arquivos e gera o CSS.
 */

export type OpcaoUtilitario = {
    classe: string;
    rotulo: string;
    /** amostra de cor, quando o grupo for de cores */
    amostra?: string;
};

export type GrupoUtilitario = {
    id: string;
    rotulo: string;
    formato: 'lista' | 'cor';
    opcoes: OpcaoUtilitario[];
};

export type SecaoUtilitarios = {
    id: string;
    rotulo: string;
    grupos: GrupoUtilitario[];
};

export const SECOES: SecaoUtilitarios[] = [
    {
        id: 'texto',
        rotulo: 'Texto',
        grupos: [
            {
                id: 'tamanho',
                rotulo: 'Tamanho',
                formato: 'lista',
                opcoes: [
                    { classe: 'text-xs', rotulo: 'XS' },
                    { classe: 'text-sm', rotulo: 'SM' },
                    { classe: 'text-base', rotulo: 'MD' },
                    { classe: 'text-lg', rotulo: 'LG' },
                    { classe: 'text-xl', rotulo: 'XL' },
                    { classe: 'text-2xl', rotulo: '2XL' },
                    { classe: 'text-3xl', rotulo: '3XL' },
                    { classe: 'text-4xl', rotulo: '4XL' },
                ],
            },
            {
                id: 'peso',
                rotulo: 'Peso',
                formato: 'lista',
                opcoes: [
                    { classe: 'font-normal', rotulo: 'Normal' },
                    { classe: 'font-medium', rotulo: 'Médio' },
                    { classe: 'font-semibold', rotulo: 'Semi' },
                    { classe: 'font-bold', rotulo: 'Bold' },
                ],
            },
            {
                id: 'alinhamento',
                rotulo: 'Alinhamento',
                formato: 'lista',
                opcoes: [
                    { classe: 'text-left', rotulo: 'Esq.' },
                    { classe: 'text-center', rotulo: 'Centro' },
                    { classe: 'text-right', rotulo: 'Dir.' },
                    { classe: 'text-justify', rotulo: 'Just.' },
                ],
            },
            {
                id: 'caixa-alta',
                rotulo: 'Caixa',
                formato: 'lista',
                opcoes: [
                    { classe: 'normal-case', rotulo: 'Normal' },
                    { classe: 'uppercase', rotulo: 'MAIÚSC.' },
                    { classe: 'capitalize', rotulo: 'Título' },
                ],
            },
            {
                id: 'cor-texto',
                rotulo: 'Cor do texto',
                formato: 'cor',
                opcoes: [
                    {
                        classe: 'text-zinc-900',
                        rotulo: 'Preto',
                        amostra: '#18181b',
                    },
                    {
                        classe: 'text-zinc-600',
                        rotulo: 'Cinza',
                        amostra: '#52525b',
                    },
                    {
                        classe: 'text-zinc-400',
                        rotulo: 'Cinza claro',
                        amostra: '#a1a1aa',
                    },
                    {
                        classe: 'text-white',
                        rotulo: 'Branco',
                        amostra: '#ffffff',
                    },
                    {
                        classe: 'text-blue-600',
                        rotulo: 'Azul',
                        amostra: '#2563eb',
                    },
                    {
                        classe: 'text-emerald-600',
                        rotulo: 'Verde',
                        amostra: '#059669',
                    },
                    {
                        classe: 'text-amber-600',
                        rotulo: 'Âmbar',
                        amostra: '#d97706',
                    },
                    {
                        classe: 'text-red-600',
                        rotulo: 'Vermelho',
                        amostra: '#dc2626',
                    },
                ],
            },
        ],
    },
    {
        id: 'fundo',
        rotulo: 'Fundo e borda',
        grupos: [
            {
                id: 'cor-fundo',
                rotulo: 'Cor de fundo',
                formato: 'cor',
                opcoes: [
                    {
                        classe: 'bg-transparent',
                        rotulo: 'Nenhum',
                        amostra: 'transparent',
                    },
                    { classe: 'bg-white', rotulo: 'Branco', amostra: '#ffffff' },
                    {
                        classe: 'bg-zinc-100',
                        rotulo: 'Cinza',
                        amostra: '#f4f4f5',
                    },
                    {
                        classe: 'bg-zinc-800',
                        rotulo: 'Escuro',
                        amostra: '#27272a',
                    },
                    {
                        classe: 'bg-blue-50',
                        rotulo: 'Azul claro',
                        amostra: '#eff6ff',
                    },
                    {
                        classe: 'bg-blue-600',
                        rotulo: 'Azul',
                        amostra: '#2563eb',
                    },
                    {
                        classe: 'bg-emerald-50',
                        rotulo: 'Verde claro',
                        amostra: '#ecfdf5',
                    },
                    {
                        classe: 'bg-amber-50',
                        rotulo: 'Âmbar claro',
                        amostra: '#fffbeb',
                    },
                ],
            },
            {
                id: 'arredondamento',
                rotulo: 'Arredondamento',
                formato: 'lista',
                opcoes: [
                    { classe: 'rounded-none', rotulo: '0' },
                    { classe: 'rounded-sm', rotulo: 'SM' },
                    { classe: 'rounded-md', rotulo: 'MD' },
                    { classe: 'rounded-lg', rotulo: 'LG' },
                    { classe: 'rounded-xl', rotulo: 'XL' },
                    { classe: 'rounded-full', rotulo: 'Full' },
                ],
            },
            {
                id: 'espessura-borda',
                rotulo: 'Espessura da borda',
                formato: 'lista',
                opcoes: [
                    { classe: 'border-0', rotulo: 'Sem' },
                    { classe: 'border', rotulo: '1' },
                    { classe: 'border-2', rotulo: '2' },
                    { classe: 'border-4', rotulo: '4' },
                ],
            },
            {
                id: 'cor-borda',
                rotulo: 'Cor da borda',
                formato: 'cor',
                opcoes: [
                    {
                        classe: 'border-zinc-200',
                        rotulo: 'Cinza claro',
                        amostra: '#e4e4e7',
                    },
                    {
                        classe: 'border-zinc-400',
                        rotulo: 'Cinza',
                        amostra: '#a1a1aa',
                    },
                    {
                        classe: 'border-zinc-900',
                        rotulo: 'Preto',
                        amostra: '#18181b',
                    },
                    {
                        classe: 'border-blue-500',
                        rotulo: 'Azul',
                        amostra: '#3b82f6',
                    },
                    {
                        classe: 'border-emerald-500',
                        rotulo: 'Verde',
                        amostra: '#10b981',
                    },
                    {
                        classe: 'border-amber-500',
                        rotulo: 'Âmbar',
                        amostra: '#f59e0b',
                    },
                ],
            },
        ],
    },
    {
        id: 'espacamento',
        rotulo: 'Espaçamento e tamanho',
        grupos: [
            {
                id: 'padding',
                rotulo: 'Interno',
                formato: 'lista',
                opcoes: [
                    { classe: 'p-0', rotulo: '0' },
                    { classe: 'p-2', rotulo: '2' },
                    { classe: 'p-4', rotulo: '4' },
                    { classe: 'p-6', rotulo: '6' },
                    { classe: 'p-8', rotulo: '8' },
                ],
            },
            {
                id: 'margem-inferior',
                rotulo: 'Margem abaixo',
                formato: 'lista',
                opcoes: [
                    { classe: 'mb-0', rotulo: '0' },
                    { classe: 'mb-2', rotulo: '2' },
                    { classe: 'mb-4', rotulo: '4' },
                    { classe: 'mb-6', rotulo: '6' },
                    { classe: 'mb-8', rotulo: '8' },
                    { classe: 'mb-12', rotulo: '12' },
                ],
            },
            {
                id: 'largura',
                rotulo: 'Largura',
                formato: 'lista',
                opcoes: [
                    { classe: 'w-auto', rotulo: 'Auto' },
                    { classe: 'w-1/3', rotulo: '1/3' },
                    { classe: 'w-1/2', rotulo: '1/2' },
                    { classe: 'w-2/3', rotulo: '2/3' },
                    { classe: 'w-full', rotulo: '100%' },
                ],
            },
        ],
    },
];

/** Todas as classes gerenciadas pelo painel, para diferenciá-las das demais. */
export const CLASSES_GERENCIADAS = new Set(
    SECOES.flatMap((s) => s.grupos).flatMap((g) =>
        g.opcoes.map((o) => o.classe),
    ),
);
