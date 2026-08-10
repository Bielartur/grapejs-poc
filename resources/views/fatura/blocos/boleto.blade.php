{{--
    Recorte de `invoice-file/templates/default.blade.php` (faixa do banco
    e as grades de células do boleto). Marcação preservada, inclusive as
    grades `grid-cols-4` / `grid-cols-5` — não há motivo pra traduzi-las
    em outra primitiva quando o Blade continua sendo quem renderiza.
--}}
<div class="boleto-border flex items-center justify-between p-2 py-0.5">
    <div>
        <p class="text-lg font-bold">
            {{ $bank->code ?? '000' }} | {{ $bank->name ?? 'Banco não informado' }}
        </p>
    </div>
    <div class="text-right">
        <p class="text-xl font-bold">
            @if ($bank_billet)
                {{ $boleto_serial_number }}
            @endif
        </p>
    </div>
</div>

<div class="mt-1 grid grid-cols-4 gap-1">
    <div class="boleto-border col-span-3 p-2 py-0.5">
        <p><strong>Local de Pagamento:</strong> Pagável em qualquer banco até o vencimento</p>
    </div>
    <div class="boleto-border p-2 py-0.5">
        <p><strong>Vencimento:</strong> {{ $invoice->due_date?->format('d/m/Y') }}</p>
    </div>
</div>

<div class="mt-1 grid grid-cols-4 gap-1">
    <div class="boleto-border col-span-3 p-2 py-0.5">
        <p><strong>Cedente:</strong> {{ $company->name }} - CNPJ: {{ $company->document }}</p>
    </div>
</div>

<div class="mt-1 grid grid-cols-5 gap-1">
    <div class="boleto-border p-2 py-0.5">
        <p><strong>Data do Documento:</strong> {{ $invoice->issue_date?->format('d/m/Y') }}</p>
    </div>
    <div class="boleto-border p-2 py-0.5">
        <p><strong>Nº do Documento:</strong> {{ $invoice->code }}</p>
    </div>
    <div class="boleto-border p-2 py-0.5">
        <p><strong>Espécie Doc.:</strong> </p>
    </div>
    <div class="boleto-border p-2 py-0.5">
        <p><strong>Aceite:</strong> </p>
    </div>
    <div class="boleto-border p-2 py-0.5">
        <p><strong>Data Processamento:</strong> {{ $invoice->issue_date?->format('d/m/Y') }}</p>
    </div>
</div>

@if ($op['mostrar_instrucoes'])
    <div class="mt-1 grid grid-cols-1">
        <div class="boleto-border p-2 py-0.5">
            <p><strong>Instruções:</strong></p>
            <p>- Pagamento via Pix ou boleto até o vencimento.</p>
        </div>
    </div>
@endif

<div class="mt-1 grid grid-cols-4 gap-1">
    <div class="boleto-border col-span-3 p-2 py-0.5">
        <p><strong>Sacado:</strong> {{ $customer->name }} - CPF: {{ $customer->document }}</p>
        <p>WhatsApp: {{ $customer->phone }} | Email: {{ $customer->email }}</p>
    </div>
    <div class="boleto-border p-2 py-0.5">
        <p><strong>Valor Cobrado:</strong> @money($invoice->amount)</p>
    </div>
</div>
