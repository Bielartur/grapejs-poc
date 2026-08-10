<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    {{--
        A casca do preview. O documento em si é o `fatura.documento` —
        esta view existe só pra dar <head> a ele dentro do iframe.

        A folha do Tailwind aqui é a compilada pelo Vite, a mesma do app.
        O template de produção usa o CDN do Tailwind; é a única diferença
        entre as duas cascas e não toca no documento.
    --}}
    @vite(['resources/css/app.css', 'resources/css/fatura.css'])
</head>

<body class="font-sans antialiased">
    @include('fatura.documento')
</body>

</html>
