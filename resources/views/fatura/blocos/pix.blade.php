{{--
    Recorte de `invoice-file/templates/default.blade.php` (bloco Pix).

    O original só renderiza com `@if ($bank_billet && $bank_billet->pix)`.
    Essa condição continua aqui: as opções do editor decidem o que
    MOSTRAR, nunca inventam dado que não existe.
--}}
@if ($bank_billet && $bank_billet->pix)
    <div class="flex justify-between gap-2">
        @if ($op['mostrar_qrcode'])
            <div class="boleto-border w-1/3 p-2 text-center">
                {{-- No sistema real é <img src="{{ $qrcode }}">, gerado pelo
                     backend. Gerar imagem é exatamente o tipo de coisa que
                     nunca vira responsabilidade do editor. --}}
                <div class="mx-auto flex h-32 max-w-32 items-center justify-center border border-gray-300">
                    <span class="text-xs text-gray-400">QR CODE</span>
                </div>
                <p class="mt-1">Pix QR Code</p>
            </div>
        @endif

        @if ($op['mostrar_copia_cola'])
            <div class="boleto-border flex-1 p-2">
                <p><strong>Pix Copia e Cola:</strong></p>
                <div class="truncate">
                    <span>{{ $bank_billet->pix }}</span>
                </div>
            </div>
        @endif
    </div>
@endif
