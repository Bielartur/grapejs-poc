{{-- Recorte de `invoice-file/templates/default.blade.php` (faixa cinza do cliente). --}}
<div class="{{ $c['arredondamento'] }} bg-gray-100 p-3">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-base font-bold">{{ $customer->name }}</p>
            <p class="text-xs text-gray-700">
                Doc: {{ $customer->document }}
                @if ($op['mostrar_endereco'])
                    · {{ $customer->address?->full_address ?? '-' }}
                @endif
            </p>
        </div>
        <div class="text-right whitespace-nowrap">
            <p class="text-xs text-gray-500 uppercase">Mês de Referência</p>
            <p class="font-bold">{{ $reference_month_label }}</p>
        </div>
    </div>
</div>
