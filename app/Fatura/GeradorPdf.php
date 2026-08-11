<?php

declare(strict_types=1);

namespace App\Fatura;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Spatie\Browsershot\Browsershot;
use Spatie\Browsershot\Enums\Polling;

/**
 * BLADE → BROWSERSHOT → PDF virou VUE → BROWSERSHOT → PDF.
 *
 * O Chromium abre uma URL do próprio app, deixa o Vue montar o
 * documento e imprime. É o mesmo código que desenha o preview: não
 * existe um "template de PDF" separado pra sair de sincronia.
 *
 * A configuração NÃO viaja na query string. Ela fica no cache sob uma
 * chave aleatória e de vida curta, e a URL assinada carrega só a chave.
 * Assim o payload não tem limite de tamanho, não vaza em log de acesso e
 * a rota não vira um jeito de pedir qualquer documento.
 */
final class GeradorPdf
{
    /** Tempo de vida da carga e da assinatura. */
    private const VALIDADE_MINUTOS = 5;

    public static function gerar(LayoutFatura $layout): string
    {
        $browsershot = Browsershot::url(self::urlDoDocumento($layout));

        /*
         * Caminho explícito do Chromium quando configurado.
         *
         * Sem isto o Puppeteer procura o binário no cache do usuário — o
         * que depende do HOME de quem roda o PHP e falha silenciosamente
         * quando o worker da fila roda com outro usuário. Em container o
         * caminho é sempre conhecido, então declará-lo é o normal.
         */
        if (is_string($chrome = config('services.chrome.path')) && $chrome !== '') {
            $browsershot->setChromePath($chrome);
        }

        return $browsershot
            /*
             * O Chromium roda como o mesmo usuário do PHP, sem
             * namespaces de sandbox disponíveis em container/WSL.
             */
            ->noSandbox()
            ->format('A4')
            // as margens já estão no layout das páginas
            ->margins(0, 0, 0, 0)
            // sem isto todo `bg-gray-100` sai branco no PDF
            ->showBackground()
            /*
             * A navegação termina no `domcontentloaded`, não no `load`
             * nem em rede ociosa.
             *
             * Quem diz que o documento está pronto é o
             * `window.documentoPronto` logo abaixo — deixar o navegador
             * também tentar adivinhar só acrescenta um jeito de travar.
             * Em desenvolvimento trava mesmo: o websocket de HMR do Vite
             * fica aberto e a página nunca parece ociosa, e o
             * `Page.navigate` estoura o protocolTimeout.
             */
            ->setOption('waitUntil', 'domcontentloaded')
            /*
             * Espera o sinal explícito que `documento.ts` levanta depois
             * de montar, carregar as fontes e as imagens.
             *
             * `waitUntilNetworkIdle` seria pior: rede parada não quer
             * dizer layout estável, e um PDF impresso cedo demais sai
             * com a paginação errada sem acusar erro nenhum.
             *
             * Sondagem por `requestAnimationFrame`: a flag sobe uma vez
             * só, e o custo de checá-la a cada quadro é irrelevante
             * perto de esperar o Chromium subir.
             */
            ->waitForFunction(
                'window.documentoPronto === true',
                Polling::RequestAnimationFrame,
                30000,
            )
            ->timeout(60)
            ->pdf();
    }

    private static function urlDoDocumento(LayoutFatura $layout): string
    {
        return URL::temporarySignedRoute(
            'fatura.pdf.documento',
            now()->addMinutes(self::VALIDADE_MINUTOS),
            ['chave' => self::prepararCarga($layout)],
        );
    }

    /**
     * Guarda a carga do documento e devolve a chave que a recupera.
     *
     * Separado da geração porque preparar e imprimir são etapas
     * distintas — é o encaixe natural pra jogar o Browsershot numa fila
     * depois, e é o que permite testar a travessia do cache sem subir
     * um Chromium.
     */
    public static function prepararCarga(LayoutFatura $layout): string
    {
        $chave = (string) Str::uuid();

        Cache::put(
            self::chaveCache($chave),
            self::cargaJson($layout),
            now()->addMinutes(self::VALIDADE_MINUTOS),
        );

        return $chave;
    }

    /**
     * A carga JÁ CODIFICADA em JSON.
     *
     * Guardar o array cru aqui não funciona: o cache serializa com
     * `serialize()` e devolve `__PHP_Incomplete_Class` no lugar dos
     * objetos, então todo `CarbonImmutable` voltava como um mapa sem
     * classe. O `dataBr()` do front recebia isso e devolvia string
     * vazia — o PDF saía sem NENHUMA data, e sem erro nenhum.
     *
     * Codificar antes de cachear resolve na raiz e ainda garante o que
     * mais importa: é o mesmo `json_encode` que o Inertia faz pra
     * alimentar o preview, então PDF e preview recebem byte a byte a
     * mesma carga.
     *
     * `JSON_HEX_TAG` é o que impede um dado com `</script>` dentro de
     * fechar a tag da casca mais cedo.
     */
    private static function cargaJson(LayoutFatura $layout): string
    {
        return json_encode(
            [
                'paginas' => $layout->paginas,
                'tema' => $layout->tema,
                'dados' => DadosExemplo::fatura(),
            ],
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
    }

    /** O JSON pronto pra casca, ou `null` se a carga expirou. */
    public static function carga(string $chave): ?string
    {
        $carga = Cache::get(self::chaveCache($chave));

        return is_string($carga) ? $carga : null;
    }

    private static function chaveCache(string $chave): string
    {
        return "fatura:pdf:{$chave}";
    }
}
