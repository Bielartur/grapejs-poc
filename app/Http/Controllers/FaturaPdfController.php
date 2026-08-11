<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Fatura\GeradorPdf;
use App\Fatura\LayoutFatura;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

final class FaturaPdfController extends Controller
{
    /**
     * Gera o PDF da configuração que está na tela, sem salvar.
     *
     * O request passa por `LayoutFatura::daArray()` como qualquer outro:
     * a fronteira de confiança vale igual pra geração de PDF, senão ela
     * viraria o caminho de contorno do catálogo.
     */
    public function baixar(Request $request): Response
    {
        $pdf = GeradorPdf::gerar(LayoutFatura::daArray($request->all()));

        return response($pdf, HttpResponse::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="fatura.pdf"',
        ]);
    }

    /**
     * A página que o Chromium abre. NÃO exige sessão — o Browsershot é
     * outro cliente e não tem cookie nenhum. Quem autoriza é a
     * assinatura da URL, com validade de minutos, e a carga vem do cache
     * pela chave assinada.
     *
     * Sem a assinatura isto seria um jeito aberto de pedir o documento
     * de qualquer fatura.
     */
    public function documento(string $chave): Response
    {
        $cargaJson = GeradorPdf::carga($chave);

        abort_if($cargaJson === null, HttpResponse::HTTP_NOT_FOUND);

        return response()->view('fatura.pdf', ['cargaJson' => $cargaJson]);
    }
}
