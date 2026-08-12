<?php

declare(strict_types=1);

namespace App\Fatura;

/**
 * ESTILO POR ELEMENTO
 *
 * O usuário clica num pedaço do documento — o nome da empresa, o total a
 * pagar — e o painel da direita mostra o que AQUELE pedaço aceita. Quem
 * decide o que ele aceita é o catálogo, não o usuário: cada elemento
 * declara seus eixos, e eixo não declarado não tem controle na tela.
 *
 * DOIS TIPOS DE EIXO, e a diferença é técnica, não estética:
 *
 *   discreto  -> vira classe Tailwind. Escala curta, botões na interface.
 *                Tamanho, peso, alinhamento, espaçamento, borda,
 *                arredondamento.
 *
 *   cor       -> vira `style` inline. O Tailwind não gera classe
 *                arbitrária em runtime, então no instante em que a cor
 *                deixou de ser uma escala fechada ela deixou de poder ser
 *                classe. Guardamos o hex e o componente aplica inline —
 *                que o Chromium imprime igual no PDF.
 *
 * Isso NÃO afrouxa a fronteira: um hex num campo de JSON continua sendo
 * configuração, não marcação. O editor segue sem conseguir emitir HTML.
 */
final class EstilosBloco
{
    /** Eixos discretos: escala fechada => rótulo do botão. */
    public const TAMANHOS = [
        'xs' => 'Mínimo',
        'sm' => 'Pequeno',
        'base' => 'Médio',
        'lg' => 'Grande',
        'xl' => 'Enorme',
    ];

    public const PESOS = [
        'normal' => 'Normal',
        'medio' => 'Médio',
        'forte' => 'Forte',
    ];

    public const ALINHAMENTOS = [
        'esquerda' => 'Esquerda',
        'centro' => 'Centro',
        'direita' => 'Direita',
    ];

    public const ESPACAMENTOS = [
        'nenhum' => 'Nenhum',
        'compacto' => 'Compacto',
        'normal' => 'Normal',
        'solto' => 'Solto',
    ];

    public const BORDAS = [
        'nenhuma' => 'Sem borda',
        'fina' => 'Fina',
        'grossa' => 'Grossa',
    ];

    public const ARREDONDAMENTOS = [
        'reto' => 'Reto',
        'sutil' => 'Sutil',
        'medio' => 'Médio',
        'redondo' => 'Redondo',
    ];

    /**
     * A paleta curada — o caminho de um clique.
     *
     * Existe pra que a escolha COMUM seja rápida e dentro da marca; o
     * hex livre continua permitido, mas é o caminho longo, atrás de um
     * botão. É a diferença entre guiar e travar.
     */
    public const PALETA = [
        '#111827' => 'Grafite',
        '#6B7280' => 'Cinza',
        '#9CA3AF' => 'Cinza claro',
        '#FFFFFF' => 'Branco',
        '#0369A1' => 'Azul',
        '#047857' => 'Verde',
        '#B45309' => 'Âmbar',
        '#B91C1C' => 'Vermelho',
        '#6D28D9' => 'Roxo',
        '#DB2777' => 'Rosa',
        '#F3F4F6' => 'Fundo claro',
        '#FEF3C7' => 'Fundo âmbar',
    ];

    /** Eixos que guardam cor livre em vez de token de escala. */
    public const EIXOS_DE_COR = ['cor', 'fundo', 'cor_borda'];

    /**
     * Todos os eixos que existem, com a escala de cada um.
     *
     * Eixo de cor entra com escala vazia: a interface dele é a paleta
     * mais o seletor livre, não uma lista de tokens.
     *
     * @return array<string, array<string, string>>
     */
    public static function escalas(): array
    {
        return [
            'tamanho' => self::TAMANHOS,
            'peso' => self::PESOS,
            'alinhamento' => self::ALINHAMENTOS,
            'espacamento' => self::ESPACAMENTOS,
            'borda' => self::BORDAS,
            'arredondamento' => self::ARREDONDAMENTOS,
            'cor' => [],
            'fundo' => [],
            'cor_borda' => [],
        ];
    }

    public static function ehEixoDeCor(string $eixo): bool
    {
        return in_array($eixo, self::EIXOS_DE_COR, true);
    }

    public static function existe(string $eixo): bool
    {
        return array_key_exists($eixo, self::escalas());
    }

    /**
     * Reconstrói os estilos de um elemento contra os eixos declarados.
     *
     * Eixo não declarado some; token fora da escala vira `null`; cor que
     * não é hex de 6 dígitos vira `null`. `null` é o neutro — o eixo não
     * foi mexido e o componente manda na aparência, o que é o que
     * permite customização parcial sem apagar a hierarquia original.
     *
     * @param  list<string>  $eixos
     * @return array<string, string|null>
     */
    public static function saneados(array $eixos, mixed $recebidos): array
    {
        $escalas = self::escalas();
        $recebidos = is_array($recebidos) ? $recebidos : [];
        $saneados = [];

        foreach ($eixos as $eixo) {
            if (! self::existe($eixo)) {
                continue;
            }

            $valor = $recebidos[$eixo] ?? null;

            if (! is_string($valor)) {
                $saneados[$eixo] = null;

                continue;
            }

            $saneados[$eixo] = self::ehEixoDeCor($eixo)
                ? self::corSaneada($valor)
                : (array_key_exists($valor, $escalas[$eixo]) ? $valor : null);
        }

        return $saneados;
    }

    /**
     * Só `#RRGGBB`.
     *
     * A forma curta e os nomes de cor do CSS ficam de fora de propósito:
     * um formato só é mais fácil de comparar com a paleta na interface, e
     * fecha a porta pra qualquer coisa que não seja literalmente uma cor
     * entrar no atributo `style` do documento.
     */
    private static function corSaneada(string $valor): ?string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $valor) === 1
            ? strtoupper($valor)
            : null;
    }
}
