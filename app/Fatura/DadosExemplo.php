<?php

declare(strict_types=1);

namespace App\Fatura;

use Carbon\CarbonImmutable;
use stdClass;

/**
 * Fatura de exemplo para o preview do editor.
 *
 * Os nomes de chave (`invoice`, `customer`, `invoice_items`) seguem a
 * convenção de um backend Laravel típico, com datas em Carbon e valores
 * em float.
 *
 * A forma importa: o array vira JSON sem DTO no caminho — Carbon
 * serializa pra ISO-8601 e `stdClass` vira objeto —, e o contrato do
 * outro lado é `resources/js/types/fatura.ts`. Mudar os nomes aqui
 * criaria uma segunda fonte de verdade pela porta dos fundos.
 *
 * Todos os dados são fictícios.
 */
final class DadosExemplo
{
    /**
     * @return array<string, mixed>
     */
    public static function fatura(): array
    {
        $emissao = CarbonImmutable::parse('2026-08-01');

        return [
            'company' => self::obj([
                'fantasy_name' => 'Aurora Energia',
                'name' => 'Aurora Energia Ltda.',
                'document' => '12.345.678/0001-90',
                'phone' => '(11) 5555-0100',
                'email' => 'contato@example.com',
            ]),
            'company_logo_url' => null,

            'customer' => self::obj([
                'name' => 'Maria Silva Andrade',
                'document' => '123.456.789-00',
                'phone' => '(19) 99999-0100',
                'email' => 'maria.andrade@example.com',
                'address' => self::obj([
                    'full_address' => 'Rua das Acácias, 480 — Campinas/SP',
                ]),
            ]),

            'invoice' => self::obj([
                'code' => '#2026-08-0142',
                'issue_date' => $emissao,
                'due_date' => $emissao->addDays(9),
                'amount' => 489.92,
            ]),

            'reference_month_label' => 'Julho / 2026',

            /*
             * A distribuidora é de quem é a REDE, não de quem emite a
             * fatura — é ela que atende falta de energia. Vem como campo
             * próprio, e não em texto fixo no componente, porque muda por
             * região do cliente.
             */
            'distributor' => self::obj([
                'name' => 'NeoEnergia',
                'phone' => '116',
                'service_hours' => 'atendimento 24h',
            ]),

            // resumo de consumo
            'credits_used' => 742,
            'active_kwh_used' => 918,
            'original_amount' => 612.40,
            'total_saved' => 122.48,
            'total_saved_percentage' => '20,0%',

            // cobrança
            'bank' => self::obj(['code' => '341', 'name' => 'Banco Itaú S.A.']),
            'bank_billet' => self::obj([
                'pix' => '00020126580014BR.GOV.BCB.PIX0136a1f3c8e2-77b4-4c1e-9a2d-5f0e6b8c1d4a5204000053039865802BR',
            ]),
            'boleto_serial_number' => '000142-9',
            'barcode_line' => '34191.79001 01043.510047 91020.150008 1 98770000048992',

            'invoice_items' => self::itens(),
            'histories_by_item' => self::historicos(),
            'bills_to_include' => self::anexos(),
        ];
    }

    /**
     * @return list<stdClass>
     */
    private static function itens(): array
    {
        $modelo = [
            [
                'id' => 1,
                'code' => 'UC 7001234',
                'alias' => 'Matriz',
                'title' => 'UC 7001234 — Matriz',
                'subtitle' => 'Residencial · Grupo B',
                'amount' => 271.10,
                'period_days' => 30,
                'subitens' => [
                    ['Energia compensada', '412', 0.72, '20%', 248.30, true],
                    ['Iluminação pública', '—', 0.0, '—', 22.80, true],
                ],
            ],
            [
                'id' => 2,
                'code' => 'UC 7005588',
                'alias' => 'Filial Norte',
                'title' => 'UC 7005588 — Filial Norte',
                'subtitle' => 'Comercial · Grupo B',
                'amount' => 143.60,
                'period_days' => 31,
                'subitens' => [
                    ['Energia compensada', '218', 0.61, '20%', 132.98, true],
                    ['Taxa de disponibilidade', '—', 0.0, '—', 10.62, true],
                ],
            ],
            [
                'id' => 3,
                'code' => 'UC 7009911',
                'alias' => 'Depósito',
                'title' => 'UC 7009911 — Depósito',
                'subtitle' => 'Comercial · Grupo B',
                'amount' => 75.22,
                'period_days' => 30,
                'subitens' => [
                    ['Energia compensada', '112', 0.58, '15%', 65.10, true],
                    ['Desconto promocional', '—', 0.0, '—', 0.0, false],
                    ['Iluminação pública', '—', 0.0, '—', 10.12, true],
                ],
            ],
        ];

        return array_map(static fn (array $i) => self::obj([
            'id' => $i['id'],
            'title' => $i['title'],
            'subtitle' => $i['subtitle'],
            'amount' => $i['amount'],
            'consumerUnit' => self::obj(['code' => $i['code'], 'alias' => $i['alias']]),
            'consumerUnitBill' => self::obj(['period_days' => $i['period_days']]),
            'invoiceSubitems' => array_map(static fn (array $s) => self::obj([
                'description' => $s[0],
                'quantity' => $s[1],
                'price' => $s[2],
                'discount_label' => $s[3],
                'amount' => $s[4],
                'chargeable' => $s[5],
            ]), $i['subitens']),
        ]), $modelo);
    }

    /**
     * Histórico chaveado pelo id do item, com 12 meses pra o bloco
     * poder cortar em 6 conforme a opção escolhida no editor.
     *
     * @return array<int, list<array<string, mixed>>>
     */
    private static function historicos(): array
    {
        $meses = [
            ['Ago / 2025', 655, 98.12], ['Set / 2025', 661, 99.80],
            ['Out / 2025', 673, 101.44], ['Nov / 2025', 688, 105.30],
            ['Dez / 2025', 694, 107.02], ['Jan / 2026', 676, 102.55],
            ['Fev / 2026', 681, 104.20], ['Mar / 2026', 702, 111.75],
            ['Abr / 2026', 698, 109.40], ['Mai / 2026', 714, 115.88],
            ['Jun / 2026', 729, 118.63], ['Jul / 2026', 742, 122.48],
        ];

        $porItem = [];

        foreach ([1 => 1.0, 2 => 0.58, 3 => 0.30] as $itemId => $fator) {
            $porItem[$itemId] = array_values(array_map(static fn (array $m) => [
                'reference_month' => $m[0],
                'credits_used' => (int) round($m[1] * $fator),
                'credits_charged' => (int) round($m[1] * $fator * 0.97),
                'credits_balance' => (int) round($m[1] * $fator * 0.08),
                'total_saved' => round($m[2] * $fator, 2),
                'amount' => round($m[2] * $fator * 2.7, 2),
            ], $meses));
        }

        return $porItem;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private static function anexos(): array
    {
        return [
            ['consumer_unit' => self::obj(['code' => 'UC 7001234']), 'bill' => self::obj(['reference_month' => 'Julho / 2026', 'bill_number' => '8871294450'])],
            ['consumer_unit' => self::obj(['code' => 'UC 7005588']), 'bill' => self::obj(['reference_month' => 'Julho / 2026', 'bill_number' => '8871294612'])],
            ['consumer_unit' => self::obj(['code' => 'UC 7009911']), 'bill' => self::obj(['reference_month' => 'Julho / 2026', 'bill_number' => '8871294788'])],
        ];
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    private static function obj(array $dados): stdClass
    {
        return (object) $dados;
    }
}
