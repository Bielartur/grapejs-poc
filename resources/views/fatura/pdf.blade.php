<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">

    {{--
        A CASCA QUE O CHROMIUM ABRE

        Não há marcação de fatura aqui — só o ponto de montagem e os
        dados. Quem desenha o documento são os componentes Vue de
        `resources/js/components/fatura/documento/`, os mesmos do
        preview. Qualquer HTML de fatura escrito neste arquivo voltaria
        a criar duas fontes de verdade.
    --}}
    {{--
        `@fonts` antes do `@vite`, igual ao `app.blade.php`.

        Sem esta linha o documento declara `font-family: Figtree` sem
        nenhum `@font-face` por trás e o Chromium cai num fallback. Não
        é detalhe estético: fonte com outra métrica muda a altura do
        texto, e altura muda onde a folha quebra — o PDF sairia com
        paginação diferente da que o editor mostrou.
    --}}
    @fonts
    @vite(['resources/css/app.css', 'resources/js/documento.ts'])

    <style>
        /* As margens do documento já estão no próprio layout das páginas
           (`px-6 py-8`); margem de @page somaria por cima e empurraria o
           conteúdo pra fora da folha. */
        @page {
            size: A4;
            margin: 0;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
        }
    </style>
</head>

<body>
    {{--
        Os dados vão embutidos, não por requisição: o Chromium roda sem
        sessão, e uma segunda ida ao servidor seria mais uma coisa pra
        falhar no meio de uma geração de PDF.

        O JSON já chega codificado do `GeradorPdf` — inclusive com o
        `JSON_HEX_TAG` que impede um dado com `</script>` dentro de
        fechar esta tag mais cedo. Codificar aqui de novo faria a carga
        do PDF divergir da que o preview recebe.
    --}}
    <script type="application/json" id="dados-documento">{!! $cargaJson !!}</script>

    <div id="documento"></div>
</body>

</html>
