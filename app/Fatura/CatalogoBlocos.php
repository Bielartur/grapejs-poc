<?php

declare(strict_types=1);

namespace App\Fatura;

/**
 * CATÁLOGO DE BLOCOS DA FATURA
 *
 * O contrato entre o editor e o renderizador. Cada entrada aqui tem um
 * componente Vue correspondente em
 * `resources/js/components/fatura/documento/blocos/`, registrado no
 * mapa do `DocumentoFatura`.
 *
 * O pareamento é afirmado por teste. O `@include` do Blade estourava
 * sozinho quando o partial não existia; o `<component :is>` do Vue
 * renderiza vazio e cala, então um bloco disponível sem componente
 * sumiria do documento sem erro nenhum.
 *
 * O editor NÃO monta layout: ele escolhe quais destes blocos aparecem,
 * em que ordem e em qual página. É o que permite o componente continuar
 * usando `v-for` aninhado e geração de código de barras — coisas que um
 * editor visual de HTML não conseguiria representar.
 *
 * As opções são deliberadamente pobres: booleano e seleção entre
 * valores fixos. Qualquer coisa mais expressiva que isso vira liberdade
 * de layout, que é exatamente o que este modelo existe pra evitar.
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
                // Sem partial ainda. Aparece na lista desligado e travado —
                // um bloco que não existe é uma linha de catálogo, não um
                // problema de representação como seria num editor de HTML.
                'disponivel' => false,
                'opcoes' => [],
            ],

            'detalhamento_uc' => [
                'rotulo' => 'Detalhamento por unidade consumidora',
                'descricao' => 'Tabela de itens e subitens com valores',
                'icone' => 'table',
                'disponivel' => true,
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
                'opcoes' => [],
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
