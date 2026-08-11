<?php

declare(strict_types=1);

namespace App\Fatura;

/**
 * CATÁLOGO DE BLOCOS DA FATURA
 *
 * O contrato entre o editor e o renderizador. Cada VARIANTE declarada
 * aqui tem um componente Vue correspondente em
 * `resources/js/components/fatura/documento/blocos/<bloco>/`, registrado
 * no mapa do `DocumentoFatura`.
 *
 * O pareamento é afirmado por teste. O `@include` do Blade estourava
 * sozinho quando o partial não existia; o `<component :is>` do Vue
 * renderiza vazio e cala, então uma variante do catálogo sem componente
 * sumiria do documento sem erro nenhum.
 *
 * O editor NÃO monta layout: ele escolhe QUAL variante de cada bloco
 * aparece, em que ordem e em qual página. É o que permite os componentes
 * continuarem usando `v-for` aninhado e geração de código de barras —
 * coisas que um editor visual de HTML não conseguiria representar.
 *
 * VARIANTES substituíram o antigo par "ligado/desligado". Um bloco não
 * tem mais um interruptor: tem uma lista de formas possíveis, e nos
 * blocos opcionais a primeira delas é "sem nenhum" (`variante = null`).
 * Blocos `obrigatorio` não têm essa escolha — uma fatura sem cabeçalho
 * ou sem resumo de consumo não é uma fatura.
 *
 * As opções seguem deliberadamente pobres: booleano e seleção entre
 * valores fixos. Elas valem para o bloco, não para a variante — cada
 * componente honra as que fazem sentido nele e ignora o resto.
 */
final class CatalogoBlocos
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public static function todos(): array
    {
        return [
            'cabecalho' => [
                'rotulo' => 'Cabeçalho',
                'descricao' => 'Logo, CNPJ, contato, nº e data da fatura',
                'icone' => 'building',
                'disponivel' => true,
                'obrigatorio' => true,
                'variantes' => [
                    'padrao' => [
                        'rotulo' => 'Padrão',
                        'descricao' => 'Logo e dados à esquerda, fatura à direita',
                    ],
                    'compacto' => [
                        'rotulo' => 'Compacto',
                        'descricao' => 'Uma linha só, com faixa de destaque',
                    ],
                    'centralizado' => [
                        'rotulo' => 'Centralizado',
                        'descricao' => 'Logo acima, tudo centralizado',
                    ],
                ],
                'opcoes' => [
                    'mostrar_logo' => [
                        'tipo' => 'bool',
                        'rotulo' => 'Mostrar logo da empresa',
                        'padrao' => true,
                    ],
                    'mostrar_contato' => [
                        'tipo' => 'bool',
                        'rotulo' => 'Mostrar telefone e e-mail',
                        'padrao' => true,
                    ],
                ],
            ],

            'dados_cliente' => [
                'rotulo' => 'Dados do cliente',
                'descricao' => 'Nome, documento, endereço, mês de referência',
                'icone' => 'user',
                'disponivel' => true,
                'obrigatorio' => true,
                'variantes' => [
                    'padrao' => [
                        'rotulo' => 'Padrão',
                        'descricao' => 'Bloco em duas colunas com o mês em destaque',
                    ],
                ],
                'opcoes' => [
                    'mostrar_endereco' => [
                        'tipo' => 'bool',
                        'rotulo' => 'Mostrar endereço',
                        'padrao' => true,
                    ],
                ],
            ],

            'resumo_consumo' => [
                'rotulo' => 'Resumo de consumo',
                'descricao' => 'Créditos, valor original, economia, total a pagar',
                'icone' => 'receipt',
                'disponivel' => true,
                'obrigatorio' => true,
                'variantes' => [
                    'padrao' => [
                        'rotulo' => 'Cartões',
                        'descricao' => 'Um cartão por indicador, total no rodapé',
                    ],
                    'faixa' => [
                        'rotulo' => 'Faixa de destaque',
                        'descricao' => 'Total a pagar em faixa larga, indicadores abaixo',
                    ],
                    'lista' => [
                        'rotulo' => 'Lista compacta',
                        'descricao' => 'Linhas rótulo/valor, ocupa pouca altura',
                    ],
                ],
                'opcoes' => [
                    'mostrar_creditos' => [
                        'tipo' => 'bool',
                        'rotulo' => 'Mostrar créditos compensados',
                        'padrao' => true,
                    ],
                    'mostrar_valor_original' => [
                        'tipo' => 'bool',
                        'rotulo' => 'Mostrar valor sem desconto',
                        'padrao' => true,
                    ],
                    'mostrar_economia' => [
                        'tipo' => 'bool',
                        'rotulo' => 'Mostrar economia do mês',
                        'padrao' => true,
                    ],
                ],
            ],

            'pix' => [
                'rotulo' => 'Pix',
                'descricao' => 'QR Code e código copia-e-cola',
                'icone' => 'qr-code',
                'disponivel' => true,
                'obrigatorio' => false,
                'rotulo_vazio' => 'Sem nenhum Pix',
                'variantes' => [
                    'padrao' => [
                        'rotulo' => 'Padrão',
                        'descricao' => 'QR Code à esquerda, copia-e-cola ao lado',
                    ],
                ],
                'opcoes' => [
                    'mostrar_qrcode' => [
                        'tipo' => 'bool',
                        'rotulo' => 'Mostrar QR Code',
                        'padrao' => true,
                    ],
                    'mostrar_copia_cola' => [
                        'tipo' => 'bool',
                        'rotulo' => 'Mostrar código copia-e-cola',
                        'padrao' => true,
                    ],
                ],
            ],

            'codigo_barras' => [
                'rotulo' => 'Código de barras',
                'descricao' => 'Código de barras e linha digitável',
                'icone' => 'barcode',
                'disponivel' => true,
                'obrigatorio' => false,
                'rotulo_vazio' => 'Sem nenhum código de barras',
                'variantes' => [
                    'padrao' => [
                        'rotulo' => 'Padrão',
                        'descricao' => 'Barras em largura total, linha digitável acima',
                    ],
                ],
                'opcoes' => [
                    'mostrar_linha_digitavel' => [
                        'tipo' => 'bool',
                        'rotulo' => 'Mostrar linha digitável',
                        'padrao' => true,
                    ],
                ],
            ],

            'boleto' => [
                'rotulo' => 'Boleto',
                'descricao' => 'Cedente, sacado, vencimento e valor',
                'icone' => 'file-text',
                'disponivel' => true,
                'obrigatorio' => false,
                'rotulo_vazio' => 'Sem nenhum boleto',
                'variantes' => [
                    'padrao' => [
                        'rotulo' => 'Padrão',
                        'descricao' => 'Quadro bancário com bordas pretas',
                    ],
                ],
                'opcoes' => [
                    'mostrar_instrucoes' => [
                        'tipo' => 'bool',
                        'rotulo' => 'Mostrar instruções de pagamento',
                        'padrao' => true,
                    ],
                ],
            ],

            'grafico' => [
                'rotulo' => 'Gráfico',
                'descricao' => 'Consumo e economia ao longo do tempo',
                'icone' => 'line-chart',
                // Sem componente ainda. Aparece na lista sem nenhuma variante
                // pra escolher — um bloco que não existe é uma linha de
                // catálogo, não um problema de representação como seria num
                // editor de HTML.
                'disponivel' => false,
                'obrigatorio' => false,
                'rotulo_vazio' => 'Sem nenhum gráfico',
                'variantes' => [],
                'opcoes' => [],
            ],

            'detalhamento_uc' => [
                'rotulo' => 'Detalhamento por unidade consumidora',
                'descricao' => 'Tabela de itens e subitens com valores',
                'icone' => 'table',
                'disponivel' => true,
                'obrigatorio' => false,
                'rotulo_vazio' => 'Sem nenhum detalhamento',
                'variantes' => [
                    'padrao' => [
                        'rotulo' => 'Tabela completa',
                        'descricao' => 'Todos os subitens, uma linha cada',
                    ],
                    'resumida' => [
                        'rotulo' => 'Tabela resumida',
                        'descricao' => 'Uma linha por unidade, sem os subitens',
                    ],
                    'cartoes' => [
                        'rotulo' => 'Cartões por unidade',
                        'descricao' => 'Um quadro por UC, subitens em duas colunas',
                    ],
                ],
                'opcoes' => [
                    'coluna_desconto' => [
                        'tipo' => 'bool',
                        'rotulo' => 'Mostrar coluna Desconto',
                        'padrao' => true,
                    ],
                    'apelido_unidade' => [
                        'tipo' => 'bool',
                        'rotulo' => 'Mostrar apelido da unidade',
                        'padrao' => true,
                    ],
                    'linha_total' => [
                        'tipo' => 'bool',
                        'rotulo' => 'Mostrar linha de Total',
                        'padrao' => true,
                    ],
                ],
            ],

            'historico_consumo' => [
                'rotulo' => 'Histórico de consumo',
                'descricao' => 'Tabela mês a mês de créditos e economia',
                'icone' => 'history',
                'disponivel' => true,
                'obrigatorio' => false,
                'rotulo_vazio' => 'Sem nenhum histórico',
                'variantes' => [
                    'padrao' => [
                        'rotulo' => 'Padrão',
                        'descricao' => 'Uma tabela por unidade consumidora',
                    ],
                ],
                'opcoes' => [
                    'meses' => [
                        'tipo' => 'select',
                        'rotulo' => 'Período',
                        'padrao' => '6',
                        'valores' => [
                            '6' => 'Últimos 6 meses',
                            '12' => 'Últimos 12 meses',
                        ],
                    ],
                ],
            ],

            'anexos_concessionaria' => [
                'rotulo' => 'Anexos da concessionária',
                'descricao' => 'Aviso de quais contas de luz vão anexadas',
                'icone' => 'paperclip',
                'disponivel' => true,
                'obrigatorio' => false,
                'rotulo_vazio' => 'Sem nenhum anexo',
                'variantes' => [
                    'padrao' => [
                        'rotulo' => 'Padrão',
                        'descricao' => 'Aviso e lista das contas anexadas',
                    ],
                ],
                'opcoes' => [],
            ],

            'notificacao_problema' => [
                'rotulo' => 'Notificação de problema',
                'descricao' => 'A quem ligar quando falta energia, e o que informar',
                'icone' => 'triangle-alert',
                'disponivel' => true,
                'obrigatorio' => false,
                'rotulo_vazio' => 'Sem nenhuma notificação',
                'variantes' => [
                    'padrao' => [
                        'rotulo' => 'Padrão',
                        'descricao' => 'Faixa de alerta com o telefone à direita',
                    ],
                ],
                'opcoes' => [
                    'mostrar_identificacao' => [
                        'tipo' => 'bool',
                        'rotulo' => 'Mostrar o que informar na ligação',
                        'padrao' => true,
                    ],
                    'mostrar_telefone' => [
                        'tipo' => 'bool',
                        'rotulo' => 'Mostrar telefone da distribuidora',
                        'padrao' => true,
                    ],
                ],
            ],
        ];
    }

    public static function existe(string $id): bool
    {
        return array_key_exists($id, self::todos());
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function bloco(string $id): ?array
    {
        return self::todos()[$id] ?? null;
    }

    /**
     * @return array<string, array{rotulo: string, descricao: string}>
     */
    public static function variantes(string $id): array
    {
        return self::bloco($id)['variantes'] ?? [];
    }

    /**
     * A primeira variante declarada. É o que um bloco obrigatório recebe
     * quando o cliente manda algo que não dá pra aproveitar — e `null`
     * quando o bloco não tem nenhuma (caso do `grafico`).
     */
    public static function variantePadrao(string $id): ?string
    {
        return array_key_first(self::variantes($id));
    }

    /**
     * Valores padrão das opções de um bloco, usados quando o layout
     * salvo é mais antigo que o catálogo e não conhece uma opção nova.
     *
     * @return array<string, mixed>
     */
    public static function opcoesPadrao(string $id): array
    {
        $opcoes = self::bloco($id)['opcoes'] ?? [];

        return array_map(static fn (array $o) => $o['padrao'], $opcoes);
    }
}
