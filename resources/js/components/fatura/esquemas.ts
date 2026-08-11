/**
 * O DESENHO ESQUEMÁTICO DE CADA VARIANTE
 *
 * O cartão da sidebar não mostra o componente de verdade: mostra a
 * FORMA dele — barras e caixas cinzas onde há texto e quadros. É o
 * suficiente pra decidir entre "logo à esquerda" e "logo em cima", que é
 * a única pergunta que o cartão precisa responder.
 *
 * Renderizar o componente real encolhido dava fidelidade demais: numa
 * tira de 300px o texto vira ruído, os três cartões de resumo ficam
 * parecidos entre si e a escolha fica MAIS difícil, não menos. Aqui o
 * ruído sai e sobra o contorno.
 *
 * O preço é acoplamento manual: mudar a marcação de um componente não
 * atualiza o desenho dele. É uma troca consciente — o esquema descreve a
 * silhueta, que muda muito menos que o conteúdo, e o teste de pareamento
 * garante ao menos que nenhuma variante fique sem desenho.
 *
 * As chaves são o par `bloco/variante`, a mesma notação do teste de
 * pareamento em `LayoutFaturaTest`.
 */

/** Peso visual do traço. `destaque` acompanha a cor primária do editor. */
export type Tom = 'forte' | 'medio' | 'fraco' | 'destaque';

export type Forma =
    /** Um traço: o lugar de uma linha de texto. */
    | { t: 'barra'; l?: string; a?: number; tom?: Tom }
    /** Um contêiner: quadro, cartão, ou só um agrupamento. */
    | {
          t: 'caixa';
          l?: string;
          a?: number;
          tom?: Tom;
          borda?: boolean | 'tracejada';
          direcao?: 'linha' | 'coluna';
          gap?: number;
          alinhar?: 'inicio' | 'centro' | 'fim' | 'entre';
          recheio?: number;
          filhos?: Forma[];
      }
    /** Grade de barras. `colunas` são larguras relativas. */
    | { t: 'tabela'; linhas: number; colunas: number[] };

const ESQUEMAS: Record<string, Forma[]> = {
    'cabecalho/padrao': [
        {
            t: 'caixa',
            direcao: 'linha',
            alinhar: 'entre',
            gap: 8,
            filhos: [
                { t: 'caixa', l: '20%', a: 26, borda: 'tracejada' },
                {
                    t: 'caixa',
                    l: '42%',
                    gap: 4,
                    filhos: [
                        { t: 'barra', l: '70%', tom: 'forte' },
                        { t: 'barra', l: '90%' },
                        { t: 'barra', l: '60%' },
                    ],
                },
                {
                    t: 'caixa',
                    l: '22%',
                    gap: 4,
                    alinhar: 'fim',
                    filhos: [
                        { t: 'barra', l: '100%', tom: 'medio' },
                        { t: 'barra', l: '75%' },
                    ],
                },
            ],
        },
    ],

    'cabecalho/compacto': [
        {
            t: 'caixa',
            tom: 'destaque',
            borda: true,
            recheio: 7,
            direcao: 'linha',
            alinhar: 'entre',
            gap: 8,
            filhos: [
                { t: 'caixa', l: '14%', a: 14, borda: 'tracejada' },
                {
                    t: 'caixa',
                    l: '48%',
                    gap: 4,
                    filhos: [
                        { t: 'barra', l: '60%', tom: 'medio' },
                        { t: 'barra', l: '100%' },
                    ],
                },
                {
                    t: 'caixa',
                    l: '22%',
                    gap: 4,
                    alinhar: 'fim',
                    filhos: [
                        { t: 'barra', l: '100%', tom: 'medio' },
                        { t: 'barra', l: '70%' },
                    ],
                },
            ],
        },
    ],

    'cabecalho/centralizado': [
        {
            t: 'caixa',
            alinhar: 'centro',
            gap: 5,
            filhos: [
                { t: 'caixa', l: '26%', a: 24, borda: 'tracejada' },
                { t: 'barra', l: '42%', tom: 'forte' },
                { t: 'barra', l: '56%' },
                { t: 'barra', l: '46%' },
                { t: 'barra', l: '100%', a: 3, tom: 'destaque' },
            ],
        },
    ],

    'dados_cliente/padrao': [
        { t: 'barra', l: '38%', tom: 'forte' },
        {
            t: 'caixa',
            direcao: 'linha',
            gap: 12,
            filhos: [
                {
                    t: 'caixa',
                    l: '52%',
                    gap: 4,
                    filhos: [
                        { t: 'barra', l: '90%' },
                        { t: 'barra', l: '70%' },
                        { t: 'barra', l: '80%' },
                    ],
                },
                {
                    t: 'caixa',
                    l: '38%',
                    gap: 4,
                    filhos: [
                        { t: 'barra', l: '60%' },
                        { t: 'barra', l: '85%', tom: 'destaque' },
                    ],
                },
            ],
        },
    ],

    'resumo_consumo/padrao': [
        { t: 'barra', l: '40%', tom: 'forte' },
        {
            t: 'caixa',
            direcao: 'linha',
            gap: 6,
            filhos: [
                { t: 'caixa', l: '32%', a: 32, borda: true, tom: 'fraco' },
                { t: 'caixa', l: '32%', a: 32, borda: true, tom: 'fraco' },
                { t: 'caixa', l: '32%', a: 32, borda: true, tom: 'destaque' },
            ],
        },
        {
            t: 'caixa',
            direcao: 'linha',
            alinhar: 'entre',
            gap: 8,
            filhos: [
                {
                    t: 'caixa',
                    l: '50%',
                    gap: 3,
                    filhos: [
                        { t: 'barra', l: '85%' },
                        { t: 'barra', l: '70%' },
                    ],
                },
                { t: 'barra', l: '32%', a: 11, tom: 'forte' },
            ],
        },
    ],

    'resumo_consumo/faixa': [
        { t: 'barra', l: '40%', tom: 'forte' },
        {
            t: 'caixa',
            borda: true,
            tom: 'destaque',
            recheio: 8,
            direcao: 'linha',
            alinhar: 'entre',
            gap: 8,
            filhos: [
                {
                    t: 'caixa',
                    l: '42%',
                    gap: 4,
                    filhos: [
                        { t: 'barra', l: '70%', tom: 'medio' },
                        { t: 'barra', l: '50%' },
                    ],
                },
                { t: 'barra', l: '34%', a: 14, tom: 'forte' },
            ],
        },
        {
            t: 'caixa',
            direcao: 'linha',
            gap: 10,
            filhos: [
                { t: 'barra', l: '45%' },
                { t: 'barra', l: '35%' },
            ],
        },
    ],

    'resumo_consumo/lista': [
        { t: 'barra', l: '40%', tom: 'forte' },
        {
            t: 'caixa',
            gap: 6,
            filhos: [
                {
                    t: 'caixa',
                    direcao: 'linha',
                    alinhar: 'entre',
                    filhos: [
                        { t: 'barra', l: '40%' },
                        { t: 'barra', l: '18%', tom: 'medio' },
                    ],
                },
                {
                    t: 'caixa',
                    direcao: 'linha',
                    alinhar: 'entre',
                    filhos: [
                        { t: 'barra', l: '34%' },
                        { t: 'barra', l: '16%', tom: 'medio' },
                    ],
                },
                {
                    t: 'caixa',
                    direcao: 'linha',
                    alinhar: 'entre',
                    filhos: [
                        { t: 'barra', l: '38%' },
                        { t: 'barra', l: '20%', tom: 'medio' },
                    ],
                },
                {
                    t: 'caixa',
                    direcao: 'linha',
                    alinhar: 'entre',
                    filhos: [
                        { t: 'barra', l: '28%', tom: 'forte' },
                        { t: 'barra', l: '24%', a: 10, tom: 'forte' },
                    ],
                },
            ],
        },
    ],

    'pix/padrao': [
        {
            t: 'caixa',
            direcao: 'linha',
            gap: 10,
            filhos: [
                { t: 'caixa', l: '26%', a: 38, borda: true, tom: 'medio' },
                {
                    t: 'caixa',
                    l: '68%',
                    gap: 5,
                    filhos: [
                        { t: 'barra', l: '55%', tom: 'medio' },
                        { t: 'barra', l: '100%' },
                        { t: 'barra', l: '88%' },
                    ],
                },
            ],
        },
    ],

    'codigo_barras/padrao': [
        { t: 'barra', l: '55%' },
        { t: 'caixa', a: 24, tom: 'forte' },
    ],

    'boleto/padrao': [
        {
            t: 'caixa',
            borda: true,
            recheio: 7,
            gap: 6,
            filhos: [
                {
                    t: 'caixa',
                    direcao: 'linha',
                    alinhar: 'entre',
                    filhos: [
                        { t: 'barra', l: '40%', tom: 'medio' },
                        { t: 'barra', l: '24%' },
                    ],
                },
                {
                    t: 'caixa',
                    direcao: 'linha',
                    alinhar: 'entre',
                    filhos: [
                        { t: 'barra', l: '50%' },
                        { t: 'barra', l: '20%' },
                    ],
                },
                {
                    t: 'caixa',
                    direcao: 'linha',
                    alinhar: 'entre',
                    filhos: [
                        { t: 'barra', l: '44%' },
                        { t: 'barra', l: '22%', tom: 'forte' },
                    ],
                },
            ],
        },
    ],

    'detalhamento_uc/padrao': [
        { t: 'barra', l: '55%', tom: 'forte' },
        { t: 'tabela', linhas: 6, colunas: [44, 14, 20, 22] },
    ],

    'detalhamento_uc/resumida': [
        { t: 'barra', l: '55%', tom: 'forte' },
        { t: 'tabela', linhas: 3, colunas: [50, 16, 16, 18] },
    ],

    'detalhamento_uc/cartoes': [
        { t: 'barra', l: '55%', tom: 'forte' },
        {
            t: 'caixa',
            borda: true,
            recheio: 6,
            gap: 4,
            filhos: [
                {
                    t: 'caixa',
                    direcao: 'linha',
                    alinhar: 'entre',
                    filhos: [
                        { t: 'barra', l: '45%', tom: 'medio' },
                        { t: 'barra', l: '18%', tom: 'medio' },
                    ],
                },
                {
                    t: 'caixa',
                    direcao: 'linha',
                    alinhar: 'entre',
                    filhos: [
                        { t: 'barra', l: '55%' },
                        { t: 'barra', l: '15%' },
                    ],
                },
            ],
        },
        {
            t: 'caixa',
            borda: true,
            recheio: 6,
            gap: 4,
            filhos: [
                {
                    t: 'caixa',
                    direcao: 'linha',
                    alinhar: 'entre',
                    filhos: [
                        { t: 'barra', l: '40%', tom: 'medio' },
                        { t: 'barra', l: '18%', tom: 'medio' },
                    ],
                },
                {
                    t: 'caixa',
                    direcao: 'linha',
                    alinhar: 'entre',
                    filhos: [
                        { t: 'barra', l: '50%' },
                        { t: 'barra', l: '15%' },
                    ],
                },
            ],
        },
    ],

    'historico_consumo/padrao': [
        { t: 'barra', l: '45%', tom: 'forte' },
        { t: 'tabela', linhas: 5, colunas: [30, 18, 18, 17, 17] },
    ],

    'anexos_concessionaria/padrao': [
        { t: 'barra', l: '42%', tom: 'forte' },
        {
            t: 'caixa',
            gap: 6,
            filhos: [
                {
                    t: 'caixa',
                    direcao: 'linha',
                    alinhar: 'inicio',
                    gap: 6,
                    filhos: [
                        { t: 'caixa', l: '7%', a: 8, tom: 'destaque' },
                        { t: 'barra', l: '70%' },
                    ],
                },
                {
                    t: 'caixa',
                    direcao: 'linha',
                    alinhar: 'inicio',
                    gap: 6,
                    filhos: [
                        { t: 'caixa', l: '7%', a: 8, tom: 'destaque' },
                        { t: 'barra', l: '58%' },
                    ],
                },
            ],
        },
    ],

    'notificacao_problema/padrao': [
        {
            t: 'caixa',
            borda: true,
            recheio: 7,
            direcao: 'linha',
            alinhar: 'entre',
            gap: 8,
            filhos: [
                {
                    t: 'caixa',
                    l: '62%',
                    gap: 4,
                    filhos: [
                        { t: 'barra', l: '80%', tom: 'forte' },
                        {
                            t: 'caixa',
                            direcao: 'linha',
                            alinhar: 'inicio',
                            gap: 4,
                            filhos: [
                                { t: 'barra', l: '18%' },
                                { t: 'caixa', l: '26%', a: 8, borda: true },
                                { t: 'caixa', l: '26%', a: 8, borda: true },
                            ],
                        },
                    ],
                },
                {
                    t: 'caixa',
                    l: '24%',
                    gap: 4,
                    alinhar: 'fim',
                    filhos: [
                        { t: 'barra', l: '70%', a: 10, tom: 'forte' },
                        { t: 'barra', l: '90%' },
                    ],
                },
            ],
        },
    ],
};

/** `null` quando a variante ainda não tem desenho. */
export function esquemaDe(idBloco: string, variante: string): Forma[] | null {
    return ESQUEMAS[`${idBloco}/${variante}`] ?? null;
}
