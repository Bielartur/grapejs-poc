{{--
    Recorte de `invoice-file/templates/default.blade.php` (bloco do código
    de barras). Lá o conteúdo vem de `@php echo $barcode @endphp` — SVG
    gerado no backend. Aqui usamos a `.barcode-placeholder` que o próprio
    template já define no CSS.
--}}
<div class="boleto-border p-2 py-0.5">
    <div class="mx-auto h-[75px] w-[600px]">
        <div class="barcode-placeholder w-full"></div>
    </div>

    @if ($op['mostrar_linha_digitavel'] && $barcode_line)
        <p class="mt-1 text-center">
            <strong>{{ $barcode_line }}</strong>
        </p>
    @endif
</div>
