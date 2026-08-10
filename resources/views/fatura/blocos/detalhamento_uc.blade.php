{{--
    Recorte de `invoice-file/templates/default.blade.php` (página 2).

    Repare no `apelido_unidade`: no sistema real essa decisão já existe,
    como `$lease_contract->include_alias_in_invoice` — uma flag no banco.
    Ela vira uma opção do bloco sem mudar nada de estrutura. É um bom
    sinal de que o modelo de configuração é o encaixe certo: as escolhas
    que já estavam espalhadas em flags ad-hoc passam a morar num lugar só.

    O `@foreach` aninhado é justamente o que não teria representação num
    editor visual de HTML — e aqui continua intacto, porque o editor
    nunca chega a tocar nesta marcação.
--}}
<h2 class="border-b pb-4 text-xl font-semibold text-gray-900">Detalhamento por Unidade Consumidora</h2>

<div class="mt-6">
    {{-- A tabela é uma unidade de quebra: ou cabe inteira na folha, ou
         desce inteira. Só parte se for mais alta que uma folha, e aí
         não há para onde empurrar. --}}
    <table data-unidade class="min-w-full table-fixed font-mono text-sm">
        <colgroup>
            <col style="width:42%">
            <col style="width:8%">
            <col style="width:22%">
            @if ($op['coluna_desconto'])
                <col style="width:8%">
            @endif
            <col style="width:20%">
        </colgroup>

        @php
            // o cabeçalho da unidade ocupa todas as colunas menos a de valor
            $colunasAntesDoValor = $op['coluna_desconto'] ? 4 : 3;
        @endphp

        <thead class="bg-gray-100">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-900">Descrição</th>
                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-900">Qtd.</th>
                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-900">Preço</th>
                @if ($op['coluna_desconto'])
                    <th class="px-4 py-2 text-right text-xs font-semibold text-gray-900">Desconto</th>
                @endif
                <th class="px-4 py-2 text-right text-xs font-semibold text-gray-900">Valor</th>
            </tr>
        </thead>

        <tbody class="bg-white">
            @foreach ($invoice_items as $item)
                <tr class="border-t border-black">
                    <th colspan="{{ $colunasAntesDoValor }}" class="px-4 py-2 text-left">
                        <div class="flex flex-col">
                            <span>{{ $item->title }}</span>

                            @if ($op['apelido_unidade'] && $item->consumerUnit->alias)
                                <span class="text-xs font-light text-gray-400">{{ $item->consumerUnit->alias }}</span>
                            @endif

                            <span class="text-sm font-light text-gray-600">{{ $item->subtitle }}</span>
                            <span>Nº de Dias: {{ $item->consumerUnitBill?->period_days ?? '-' }}</span>
                        </div>
                    </th>
                    <th class="px-4 py-2 text-right text-sm font-semibold text-gray-900">
                        <span>@money($item->amount)</span>
                    </th>
                </tr>

                @foreach ($item->invoiceSubitems as $subitemIndex => $subitem)
                    <tr class="{{ $subitemIndex === 0 ? 'border-gray-300' : 'border-gray-200' }} border-t">
                        <td class="px-4 py-2 pl-8 text-sm text-gray-500 {{ $subitem->chargeable ? '' : 'line-through' }}">
                            {{ $subitem->description }}
                        </td>
                        <td class="px-4 py-2 text-right text-sm text-gray-500 {{ $subitem->chargeable ? '' : 'line-through' }}">
                            {{ $subitem->quantity }}
                        </td>
                        <td class="px-4 py-2 text-right text-sm text-gray-500 {{ $subitem->chargeable ? '' : 'line-through' }}">
                            <span>@money($subitem->price)</span>
                        </td>
                        @if ($op['coluna_desconto'])
                            <td class="px-4 py-2 text-right text-sm text-gray-500 {{ $subitem->chargeable ? '' : 'line-through' }}">
                                {{ $subitem->discount_label }}
                            </td>
                        @endif
                        <td class="px-4 py-2 text-right text-sm text-gray-500 {{ $subitem->chargeable ? '' : 'line-through' }}">
                            <span>@money($subitem->amount)</span>
                        </td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>

        @if ($op['linha_total'])
            <tfoot class="bg-white">
                <tr>
                    {{-- linha vazia só pra respiro, como no original --}}
                    <td style="border:0" colspan="{{ $colunasAntesDoValor }}"></td>
                </tr>
                <tr>
                    <td style="border:0" colspan="{{ $colunasAntesDoValor - 1 }}"></td>
                    <td class="px-2 py-2 text-left font-bold text-gray-900">Total</td>
                    <td class="px-2 py-2 text-right font-bold text-gray-900">
                        <span>@money($invoice->amount)</span>
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>
</div>
