{{--
    Recorte de `components/invoice/consumption-summary.blade.php`.

    Único lugar onde os nomes mudam: lá era um componente e recebia props
    em camelCase (`$creditsUsed`); aqui são as variáveis do call site, em
    snake_case (`$credits_used`). Ao levar de volta, renomear.

    O "Total a Pagar" não tem opção pra esconder — uma fatura sem valor
    não é uma fatura. É esse tipo de invariante que o catálogo protege e
    que um editor livre deixaria o usuário apagar sem perceber.
--}}
<div class="mt-1">
    <h3 class="text-lg font-medium text-gray-900">Resumo de Consumo</h3>

    <div class="mt-1 flex flex-wrap gap-4">
        @if ($op['mostrar_creditos'])
            <div class="{{ $c['arredondamento'] }} min-w-40 flex-1 border bg-gray-50 p-4">
                <span class="text-sm font-medium text-gray-700">Créditos Compensados</span>
                <div class="mt-2 text-2xl font-bold text-gray-900">
                    {{ $credits_used }} <span class="text-lg">kWh</span>
                </div>
                @if ($active_kwh_used > 0)
                    <span class="text-[14px] text-gray-500">de {{ $active_kwh_used }} kWh consumidos</span>
                @endif
            </div>
        @endif

        @if ($op['mostrar_valor_original'])
            <div class="{{ $c['arredondamento'] }} min-w-40 flex-1 border bg-gray-50 p-4">
                <span class="text-sm font-medium text-gray-600">Valor Original</span>
                <div class="mt-2 text-lg font-medium text-gray-600 line-through">@money($original_amount)</div>
            </div>
        @endif

        @if ($op['mostrar_economia'])
            <div class="{{ $c['arredondamento'] }} {{ $c['destaque_fundo'] }} {{ $c['destaque_borda'] }} min-w-40 flex-1 border p-4">
                <span class="text-sm font-medium text-gray-600">Economia</span>
                <div class="{{ $c['destaque_texto'] }} mt-2 text-xl font-bold">
                    @money($total_saved) <span class="text-2xl">({{ $total_saved_percentage }})</span>
                </div>
            </div>
        @endif
    </div>

    <div class="mt-1 flex items-start justify-between py-2">
        <div class="space-y-0.5 text-xs whitespace-nowrap text-gray-600">
            <p><strong>*Créditos Compensados:</strong> KW/h oriundos da Energia Solar</p>
            <p><strong>*Consumidos:</strong> Consumo Total da(s) sua(s) unidade(s)</p>
        </div>
        <div class="text-right">
            <p class="text-3xl font-extrabold whitespace-nowrap text-gray-900">
                Total a Pagar:
                <span class="{{ $c['arredondamento'] }} bg-gray-200 px-1 py-1">@money($invoice->amount)</span>
            </p>
        </div>
    </div>
</div>
