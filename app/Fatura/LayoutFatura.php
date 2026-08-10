<?php

declare(strict_types=1);

namespace App\Fatura;

/**
 * O LAYOUT SALVO
 *
 * É isto que vai pro banco no lugar de HTML: ordem dos blocos, quais
 * estão ligados, as opções de cada um e um punhado de escolhas de
 * aparência. Trocar HTML por configuração é o que mantém o backend dono
 * da marcação — o editor nunca consegue emitir markup arbitrário.
 *
 * `daArray()` é a fronteira de confiança: tudo que vem do cliente é
 * reconstruído contra o catálogo. Bloco desconhecido é descartado,
 * opção desconhecida é ignorada, opção conhecida é convertida pro tipo
 * declarado. Um layout salvo antes de o catálogo mudar continua abrindo,
 * porque o que falta é preenchido com o padrão.
 */
final class LayoutFatura
{
    public const ARREDONDAMENTOS = [
        'none' => 'Reto',
        'sm' => 'Sutil',
        'md' => 'Médio',
        'lg' => 'Arredondado',
    ];

    public const DESTAQUES = [
        'sky' => 'Azul',
        'emerald' => 'Verde',
        'violet' => 'Roxo',
        'slate' => 'Grafite',
    ];

    public const DENSIDADES = [
        'compacta' => 'Compacta',
        'confortavel' => 'Confortável',
    ];

    /**
     * @param  list<list<array{id: string, ativo: bool, opcoes: array<string, mixed>}>>  $paginas
     * @param  array{arredondamento: string, destaque: string, densidade: string}  $tema
     */
    public function __construct(
        public readonly array $paginas,
        public readonly array $tema,
    ) {}

    public static function padrao(): self
    {
        $paginas = [
            ['cabecalho', 'dados_cliente', 'resumo_consumo', 'pix', 'codigo_barras', 'boleto', 'grafico'],
            ['detalhamento_uc'],
            ['historico_consumo'],
            ['anexos_concessionaria'],
        ];

        return new self(
            array_values(array_map(
                static fn (array $ids) => array_map(static fn (string $id) => [
                    'id' => $id,
                    'ativo' => (bool) CatalogoBlocos::bloco($id)['disponivel'],
                    'opcoes' => CatalogoBlocos::opcoesPadrao($id),
                ], $ids),
                $paginas,
            )),
            [
                'arredondamento' => 'md',
                'destaque' => 'sky',
                'densidade' => 'compacta',
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public static function daArray(array $dados): self
    {
        $paginas = [];

        foreach ($dados['paginas'] ?? [] as $blocos) {
            $pagina = [];

            foreach (is_array($blocos) ? $blocos : [] as $bloco) {
                $id = is_array($bloco) ? ($bloco['id'] ?? null) : null;

                if (! is_string($id) || ! CatalogoBlocos::existe($id)) {
                    continue;
                }

                $definicao = CatalogoBlocos::bloco($id);

                $pagina[] = [
                    'id' => $id,
                    // um bloco indisponível nunca pode ser ligado pelo cliente
                    'ativo' => $definicao['disponivel'] && (bool) ($bloco['ativo'] ?? false),
                    'opcoes' => self::opcoesSaneadas($id, $bloco['opcoes'] ?? []),
                ];
            }

            if ($pagina !== []) {
                $paginas[] = $pagina;
            }
        }

        if ($paginas === []) {
            return self::padrao();
        }

        return new self($paginas, self::temaSaneado($dados['tema'] ?? []));
    }

    /**
     * @return array{paginas: mixed, tema: mixed}
     */
    public function paraArray(): array
    {
        return [
            'paginas' => $this->paginas,
            'tema' => $this->tema,
        ];
    }

    /**
     * Classes Tailwind derivadas do tema. Ficam aqui, e não no Blade,
     * porque o nome completo precisa aparecer literal em algum arquivo
     * pro Tailwind detectar — classe montada por interpolação não é.
     *
     * @return array<string, string>
     */
    public function classes(): array
    {
        $arredondamento = match ($this->tema['arredondamento']) {
            'none' => 'rounded-none',
            'sm' => 'rounded-sm',
            'lg' => 'rounded-lg',
            default => 'rounded-md',
        };

        [$destaqueFundo, $destaqueBorda, $destaqueTexto] = match ($this->tema['destaque']) {
            'emerald' => ['bg-emerald-50', 'border-emerald-500', 'text-emerald-700'],
            'violet' => ['bg-violet-50', 'border-violet-500', 'text-violet-700'],
            'slate' => ['bg-slate-100', 'border-slate-500', 'text-slate-700'],
            default => ['bg-sky-50', 'border-sky-500', 'text-sky-700'],
        };

        $densidade = $this->tema['densidade'] === 'confortavel'
            ? 'p-4 text-sm'
            : 'p-2 text-xs';

        return [
            'arredondamento' => $arredondamento,
            'destaque_fundo' => $destaqueFundo,
            'destaque_borda' => $destaqueBorda,
            'destaque_texto' => $destaqueTexto,
            'densidade' => $densidade,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function opcoesSaneadas(string $id, mixed $recebidas): array
    {
        $definicoes = CatalogoBlocos::bloco($id)['opcoes'];
        $recebidas = is_array($recebidas) ? $recebidas : [];
        $saneadas = [];

        foreach ($definicoes as $chave => $definicao) {
            $valor = $recebidas[$chave] ?? null;

            $saneadas[$chave] = match ($definicao['tipo']) {
                'bool' => is_null($valor) ? $definicao['padrao'] : (bool) $valor,
                'select' => array_key_exists((string) $valor, $definicao['valores'])
                    ? (string) $valor
                    : $definicao['padrao'],
                default => $definicao['padrao'],
            };
        }

        return $saneadas;
    }

    /**
     * @return array{arredondamento: string, destaque: string, densidade: string}
     */
    private static function temaSaneado(mixed $recebido): array
    {
        $recebido = is_array($recebido) ? $recebido : [];

        $escolher = static fn (string $chave, array $validos, string $padrao): string => array_key_exists(
            (string) ($recebido[$chave] ?? ''),
            $validos,
        ) ? (string) $recebido[$chave] : $padrao;

        return [
            'arredondamento' => $escolher('arredondamento', self::ARREDONDAMENTOS, 'md'),
            'destaque' => $escolher('destaque', self::DESTAQUES, 'sky'),
            'densidade' => $escolher('densidade', self::DENSIDADES, 'compacta'),
        ];
    }
}
