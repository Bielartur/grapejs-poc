{{--
    O DOCUMENTO

    Fonte única da marcação da fatura: este arquivo e os partials de
    `blocos/` são o que o preview do editor mostra E o que o Browsershot
    transformaria em PDF. O Vue nunca desenha nada disto — ele só produz
    a configuração que chega aqui em `$layout`.

    `@include` com nome dinâmico é seguro porque `$bloco['id']` já passou
    por `LayoutFatura::daArray()`, que descarta qualquer id fora do
    catálogo. Sem essa garantia isto seria path traversal.

    O `data-bloco` existe pro editor localizar a seção dentro do iframe e
    desenhar o destaque por cima dela. É o que dispensa replicar a
    marcação em componente Vue só pra conseguir apontar pro bloco.
--}}
@php
    $c = $layout->classes();
@endphp

@foreach ($layout->paginas as $indice => $blocos)
    <div
        data-pagina="{{ $indice + 1 }}"
        @class([
            'max-w-4xl mx-auto px-6 py-8 bg-white boleto-text',
            'page-break' => $indice > 0,
        ])
    >
        @foreach ($blocos as $bloco)
            @continue(! $bloco['ativo'])

            {{-- `data-unidade` aqui deixa o bloco inteiro sem poder ser
                 cortado ao meio. Blocos que crescem além de uma folha
                 marcam unidades menores por dentro (ver histórico e
                 detalhamento), e são essas que valem no corte. --}}
            <section data-bloco="{{ $bloco['id'] }}" data-unidade class="mb-4">
                @include("fatura.blocos.{$bloco['id']}", ['op' => $bloco['opcoes']])
            </section>
        @endforeach
    </div>
@endforeach
