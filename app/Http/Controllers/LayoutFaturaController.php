<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Fatura\CatalogoBlocos;
use App\Fatura\DadosExemplo;
use App\Fatura\LayoutFatura;
use App\Fatura\RepositorioLayout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class LayoutFaturaController extends Controller
{
    /** A tela do editor. */
    public function index(): InertiaResponse
    {
        return Inertia::render('LayoutFatura', [
            'catalogo' => CatalogoBlocos::todos(),
            'layout' => RepositorioLayout::carregar()->paraArray(),
            'temaOpcoes' => [
                'arredondamento' => LayoutFatura::ARREDONDAMENTOS,
                'destaque' => LayoutFatura::DESTAQUES,
                'densidade' => LayoutFatura::DENSIDADES,
            ],
        ]);
    }

    /**
     * Renderiza o documento com a configuração que está na tela, sem
     * salvar. É o mesmo Blade que geraria o PDF — daí não existir
     * nenhuma versão em Vue da fatura pra sair de sincronia.
     *
     * Devolve UMA STRING POR PÁGINA, não o documento inteiro. Assim o
     * editor monta um iframe por página e consegue dar espaço real entre
     * elas: separação dentro de um iframe único seria impossível de
     * desenhar de fora sem injetar estilo no documento.
     *
     * O HTML vem dentro de JSON porque os iframes são alimentados por
     * `srcdoc` — o preview não depende de sessão nem de rota com estado,
     * e cada requisição carrega a config inteira.
     */
    public function preview(Request $request): JsonResponse
    {
        $layout = LayoutFatura::daArray($request->all());
        $dados = DadosExemplo::fatura();

        $paginas = array_map(
            static fn (array $blocos) => view('fatura.preview', [
                'layout' => new LayoutFatura([$blocos], $layout->tema),
                ...$dados,
            ])->render(),
            $layout->paginas,
        );

        return response()->json(['paginas' => $paginas]);
    }

    public function salvar(Request $request): Response
    {
        RepositorioLayout::salvar(LayoutFatura::daArray($request->all()));

        return response()->noContent();
    }
}
