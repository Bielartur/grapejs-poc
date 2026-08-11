<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Fatura\CatalogoBlocos;
use App\Fatura\GeradorPdf;
use App\Fatura\LayoutFatura;
use App\Fatura\RepositorioLayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * O que importa testar aqui é a fronteira de confiança: o cliente manda
 * JSON, e o backend só aceita o que o catálogo reconhece.
 *
 * Com o documento migrado pra Vue, os testes que liam o HTML gerado
 * (coluna some, unidade de quebra existe) não têm mais o que ler no
 * PHP — essa verificação passou a ser trabalho de teste de componente.
 * O que ENTROU no lugar é o teste de pareamento: o Blade estourava
 * sozinho quando um partial não existia, o Vue renderiza vazio e cala,
 * então a correspondência catálogo ↔ componente precisa ser afirmada.
 */
class LayoutFaturaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake();
        $this->actingAs(User::factory()->create());
    }

    public function test_visitantes_nao_acessam_o_editor(): void
    {
        $this->app['auth']->forgetGuards();

        $this->get(route('fatura.layout.index'))->assertRedirect(route('login'));
    }

    public function test_o_editor_recebe_catalogo_layout_e_dados(): void
    {
        $resposta = $this->get(route('fatura.layout.index'));

        $resposta->assertOk();

        // sem os dados o documento em Vue não teria o que renderizar
        $resposta->assertInertia(
            fn ($pagina) => $pagina
                ->component('LayoutFatura')
                ->has('catalogo')
                ->has('layout.paginas')
                ->where('dados.customer.name', 'Maria Silva Andrade')
                // Carbon precisa atravessar como string ISO
                ->where('dados.invoice.issue_date', '2026-08-01T00:00:00.000000Z')
                // e o valor precisa chegar CRU: quem formata é o front
                ->where('dados.invoice.amount', 489.92),
        );
    }

    /**
     * O `@include` com nome dinâmico do Blade explodia sozinho com um id
     * fora do catálogo. O `<component :is>` do Vue não explodiria — daí
     * o saneamento ser ainda mais essencial do que era.
     */
    public function test_bloco_desconhecido_e_descartado(): void
    {
        $layout = LayoutFatura::daArray([
            'paginas' => [[
                ['id' => 'cabecalho', 'ativo' => true, 'opcoes' => []],
                ['id' => '../../../etc/passwd', 'ativo' => true],
            ]],
        ]);

        $this->assertCount(1, $layout->paginas[0]);
        $this->assertSame('cabecalho', $layout->paginas[0][0]['id']);
    }

    public function test_bloco_indisponivel_nao_pode_ser_ligado_pelo_cliente(): void
    {
        $layout = LayoutFatura::daArray([
            'paginas' => [[['id' => 'grafico', 'ativo' => true, 'opcoes' => []]]],
        ]);

        $this->assertFalse($layout->paginas[0][0]['ativo']);
    }

    public function test_opcao_invalida_cai_no_padrao_do_catalogo(): void
    {
        $layout = LayoutFatura::daArray([
            'paginas' => [[[
                'id' => 'historico_consumo',
                'ativo' => true,
                'opcoes' => ['meses' => '999', 'inexistente' => 'x'],
            ]]],
        ]);

        $opcoes = $layout->paginas[0][0]['opcoes'];

        $this->assertSame('6', $opcoes['meses']);
        $this->assertArrayNotHasKey('inexistente', $opcoes);
    }

    public function test_salvar_e_recarregar_preserva_o_layout(): void
    {
        $this->postJson(route('fatura.layout.salvar'), [
            'paginas' => [[['id' => 'boleto', 'ativo' => true, 'opcoes' => ['mostrar_instrucoes' => false]]]],
            'tema' => ['arredondamento' => 'lg', 'destaque' => 'emerald', 'densidade' => 'confortavel'],
        ])->assertNoContent();

        $layout = RepositorioLayout::carregar();

        $this->assertSame('boleto', $layout->paginas[0][0]['id']);
        $this->assertFalse($layout->paginas[0][0]['opcoes']['mostrar_instrucoes']);
        $this->assertSame('rounded-lg', $layout->classes()['arredondamento']);
    }

    /**
     * O PAREAMENTO CATÁLOGO ↔ COMPONENTE
     *
     * Um id que o catálogo declara disponível mas que não tem componente
     * registrado no `DocumentoFatura` não quebra nada: o bloco só some
     * do documento, em silêncio, e ninguém percebe até um cliente
     * reclamar de fatura sem cabeçalho. É o risco que a troca de engine
     * introduziu, e é o que este teste trava.
     */
    public function test_todo_bloco_disponivel_tem_componente_vue(): void
    {
        $fonte = (string) file_get_contents(
            resource_path('js/components/fatura/documento/DocumentoFatura.vue'),
        );

        $this->assertSame(
            1,
            preg_match('/const COMPONENTES[^{]*\{(.*?)\n\};/s', $fonte, $bloco),
            'Não achei o mapa COMPONENTES — o formato do arquivo mudou.',
        );

        preg_match_all('/^\s{4}(\w+):/m', $bloco[1], $encontrados);
        $registrados = $encontrados[1];

        $disponiveis = array_keys(array_filter(
            CatalogoBlocos::todos(),
            static fn (array $b): bool => (bool) $b['disponivel'],
        ));

        sort($registrados);
        sort($disponiveis);

        $this->assertSame(
            $disponiveis,
            $registrados,
            'Catálogo e componentes Vue divergiram: um bloco disponível sem '
            .'componente some do documento sem erro nenhum.',
        );
    }

    /**
     * A rota que o Chromium abre roda sem sessão. Se a assinatura não
     * fosse exigida, ela seria um jeito aberto de pedir o documento.
     */
    public function test_a_pagina_do_pdf_exige_assinatura(): void
    {
        $this->get(route('fatura.pdf.documento', ['chave' => 'qualquer']))
            ->assertForbidden();
    }

    /**
     * A CARGA PRECISA ATRAVESSAR O CACHE COMO JSON
     *
     * Este teste existe por causa de um bug que não deu erro nenhum: a
     * carga era guardada como array de objetos PHP, e o cache serializa
     * com `serialize()`. Todo `CarbonImmutable` voltava como
     * `__PHP_Incomplete_Class`, o `dataBr()` do front recebia aquilo e
     * devolvia string vazia — o PDF saía SEM NENHUMA DATA, calado.
     *
     * O driver `array` do ambiente de teste não serializa, então o bug
     * era invisível aqui. Daí forçar o `database`, que é o de produção:
     * sem isso o teste passaria mesmo com o defeito de volta.
     */
    public function test_a_carga_do_pdf_atravessa_o_cache_sem_perder_as_datas(): void
    {
        config(['cache.default' => 'database']);

        $chave = GeradorPdf::prepararCarga(LayoutFatura::padrao());
        $json = GeradorPdf::carga($chave);

        $this->assertIsString($json, 'A carga precisa sair do cache como JSON.');
        $this->assertStringNotContainsString('__PHP_Incomplete_Class', $json);

        $carga = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(
            '2026-08-01T00:00:00.000000Z',
            $carga['dados']['invoice']['issue_date'],
            'A data precisa chegar como string ISO — é o que `dataBr()` sabe ler.',
        );
    }

    public function test_carga_ausente_ou_expirada_devolve_404(): void
    {
        $url = URL::temporarySignedRoute(
            'fatura.pdf.documento',
            now()->addMinute(),
            ['chave' => 'inexistente'],
        );

        $this->get($url)->assertNotFound();
    }
}
