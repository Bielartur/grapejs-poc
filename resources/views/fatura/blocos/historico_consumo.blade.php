{{--
    Recorte de `components/invoice/consumption-history.blade.php`.

    Duas diferenças em relação ao original, ambas por causa do editor:
    o wrapper de página saiu (quem decide página agora é o layout) e o
    corte de meses virou opção em vez de vir tudo que existir.
--}}
@php
    $meses = (int) $op['meses'];
@endphp

@if (count($invoice_items) > 0)
    <h2 class="border-b pb-4 text-xl font-semibold text-gray-900">
        Histórico de Consumo por Unidade Consumidora
    </h2>

    <div class="mt-6">
        @foreach ($invoice_items as $item)
            @php
                // os mais recentes ficam no fim da lista, então o corte é pelo fim
                $historico = array_slice($histories_by_item[$item->id] ?? [], -$meses);
            @endphp

            @continue(count($historico) === 0)

            {{-- Cada unidade consumidora é uma unidade de quebra: o
                 título e a tabela dela descem juntos pra próxima folha
                 em vez de o corte cair no meio dos meses. O bloco todo é
                 alto demais pra caber numa folha, então é aqui que a
                 regra precisa morar. --}}
            <div data-unidade class="mb-6">
                <h3 class="border-b pb-4 text-lg font-semibold text-gray-900">
                    {{ $item->consumerUnit->code }} - {{ $item->title }}
                </h3>

                <table class="min-w-full font-mono text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-900">Mês</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-900">Créditos Consumidos (kWh)</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-900">Créditos Faturados (kWh)</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-900">Saldo de Créditos (kWh)</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-900">Valor Economizado (R$)</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-900">Valor (R$)</th>
                    </tr>
                </thead>
                <tbody class="bg-white">
                    @foreach ($historico as $month)
                        <tr class="border-t border-black">
                            <td class="px-4 py-2 text-sm font-semibold text-gray-900">{{ $month['reference_month'] }}</td>
                            <td class="px-4 py-2 text-right text-sm font-semibold text-gray-900">{{ $month['credits_used'] }}</td>
                            <td class="px-4 py-2 text-right text-sm font-semibold text-gray-900">{{ $month['credits_charged'] }}</td>
                            <td class="px-4 py-2 text-right text-sm font-semibold text-gray-900">{{ $month['credits_balance'] }}</td>
                            <td class="px-4 py-2 text-right text-sm font-semibold text-gray-900">@money($month['total_saved'])</td>
                            <td class="px-4 py-2 text-right text-sm font-semibold text-gray-900">@money($month['amount'])</td>
                        </tr>
                    @endforeach
                </tbody>
                </table>
            </div>
        @endforeach
    </div>
@endif
