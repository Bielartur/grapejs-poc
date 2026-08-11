<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Fatura\CatalogoBlocos;
use App\Fatura\DadosExemplo;
use App\Fatura\LayoutFatura;
use App\Fatura\RepositorioLayout;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

final class LayoutFaturaController extends Controller
{
    /**
     * A tela do editor.
     *
     * Manda a configuração E os dados da fatura de exemplo. Não existe
     * mais rota de preview: o documento é desenhado pelos componentes em
     * `resources/js/components/fatura/documento/`, que reagem ao estado
     * do editor sem passar pelo servidor.
     */
    public function index(): InertiaResponse
    {
        return Inertia::render('LayoutFatura', [
            'catalogo' => CatalogoBlocos::todos(),
            'layout' => RepositorioLayout::carregar()->paraArray(),
            /*
             * `CarbonImmutable` serializa pra ISO-8601 sozinho e
             * `stdClass` vira objeto, então não há DTO no caminho. O
             * contrato do outro lado é `resources/js/types/fatura.ts`.
             */
            'dados' => DadosExemplo::fatura(),
            'temaOpcoes' => [
                'arredondamento' => LayoutFatura::ARREDONDAMENTOS,
                'destaque' => LayoutFatura::DESTAQUES,
                'densidade' => LayoutFatura::DENSIDADES,
            ],
        ]);
    }

    public function salvar(Request $request): Response
    {
        RepositorioLayout::salvar(LayoutFatura::daArray($request->all()));

        return response()->noContent();
    }
}
