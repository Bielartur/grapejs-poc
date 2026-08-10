{{-- Recorte de `invoice-file/templates/default.blade.php` (cabeçalho da página 1). --}}
<div class="flex items-center justify-between">
    <div class="flex items-center gap-4">
        @if ($op['mostrar_logo'])
            @if ($company_logo_url)
                <img src="{{ $company_logo_url }}" alt="Company Logo" class="h-14 w-auto max-w-40 object-contain" />
            @else
                {{-- Sem logo cadastrada o original simplesmente some com o
                     bloco; no editor um vazio silencioso confunde, então
                     mostramos o espaço reservado. --}}
                <div class="flex h-14 w-40 shrink-0 items-center justify-center border border-dashed border-gray-300 text-xs text-gray-400">
                    LOGO
                </div>
            @endif
        @endif

        <div>
            <p class="text-lg font-bold">{{ $company->fantasy_name }}</p>
            <p>{{ $company->name }}</p>
            <p>CNPJ: &nbsp;<span>{{ $company->document }}</span></p>

            @if ($op['mostrar_contato'])
                <p>{{ $company->phone }} | {{ $company->email }}</p>
            @endif
        </div>
    </div>

    <div class="text-right">
        <p class="text-sm">Fatura: {{ $invoice->code }}</p>
        <p class="text-sm">Emissão: {{ $invoice->issue_date?->format('d/m/Y') }}</p>
    </div>
</div>
