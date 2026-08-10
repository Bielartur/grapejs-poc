<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Fatura\LayoutFatura;
use App\Fatura\RepositorioLayout;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * O que importa testar aqui não é a aparência do documento, e sim a
 * fronteira de confiança: o cliente manda JSON, e o backend só aceita o
 * que o catálogo reconhece. É essa garantia que permite dar `@include`
 * com nome dinâmico e continuar dono da marcação.
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

    public function test_o_preview_devolve_um_documento_por_pagina(): void
    {
        $resposta = $this->postJson(
            route('fatura.layout.preview'),
            LayoutFatura::padrao()->paraArray(),
        );

        $resposta->assertOk();
        $paginas = $resposta->json('paginas');

        // o editor monta um iframe por página, então cada string é um
        // documento HTML completo e independente
        $this->assertCount(4, $paginas);

        foreach ($paginas as $html) {
            $this->assertStringContainsString('<!DOCTYPE html>', $html);
            $this->assertSame(1, substr_count($html, 'data-pagina='));
        }

        $tudo = implode('', $paginas);

        // 10 blocos no catálogo, e o gráfico (indisponível) fica de fora
        $this->assertSame(9, substr_count($tudo, 'data-bloco='));
        $this->assertStringContainsString('Maria Silva Andrade', $tudo);
    }

    public function test_bloco_desconhecido_e_descartado(): void
    {
        $resposta = $this->postJson(route('fatura.layout.preview'), [
            'paginas' => [[
                ['id' => 'cabecalho', 'ativo' => true, 'opcoes' => []],
                ['id' => '../../../etc/passwd', 'ativo' => true],
            ]],
        ]);

        $resposta->assertOk();
        $this->assertSame(
            1,
            substr_count(implode('', $resposta->json('paginas')), 'data-bloco='),
        );
    }

    public function test_bloco_indisponivel_nao_pode_ser_ligado_pelo_cliente(): void
    {
        $layout = LayoutFatura::daArray([
            'paginas' => [[['id' => 'grafico', 'ativo' => true, 'opcoes' => []]]],
        ]);

        $this->assertFalse($layout->paginas[0][0]['ativo']);
    }

    public function test_opcao_desligada_some_do_documento(): void
    {
        $comDesconto = $this->postJson(route('fatura.layout.preview'), [
            'paginas' => [[[
                'id' => 'detalhamento_uc',
                'ativo' => true,
                'opcoes' => ['coluna_desconto' => true],
            ]]],
        ])->json('paginas.0');

        $semDesconto = $this->postJson(route('fatura.layout.preview'), [
            'paginas' => [[[
                'id' => 'detalhamento_uc',
                'ativo' => true,
                'opcoes' => ['coluna_desconto' => false],
            ]]],
        ])->json('paginas.0');

        // pelo cabeçalho da coluna: a palavra solta também aparece na
        // descrição de um subitem dos dados de exemplo
        $this->assertStringContainsString('>Desconto</th>', $comDesconto);
        $this->assertStringNotContainsString('>Desconto</th>', $semDesconto);

        // e a tabela encolhe uma coluna junto
        $this->assertStringContainsString('colspan="4"', $comDesconto);
        $this->assertStringContainsString('colspan="3"', $semDesconto);
    }

    /**
     * O corte de folha é calculado no editor a partir destas marcações,
     * e no PDF vem do `break-inside: avoid` que elas carregam. Se um
     * bloco grande deixar de marcar unidades por dentro, a quebra volta
     * a cair no meio de uma tabela — daí travar isso num teste.
     */
    public function test_blocos_grandes_marcam_unidades_de_quebra_internas(): void
    {
        $html = $this->postJson(route('fatura.layout.preview'), [
            'paginas' => [[
                ['id' => 'historico_consumo', 'ativo' => true, 'opcoes' => ['meses' => '12']],
                ['id' => 'detalhamento_uc', 'ativo' => true, 'opcoes' => []],
            ]],
        ])->json('paginas.0');

        // 2 seções + 3 unidades consumidoras no histórico + 1 tabela
        $this->assertSame(6, substr_count($html, 'data-unidade'));

        // a tabela do detalhamento é indivisível como um todo
        $this->assertStringContainsString('<table data-unidade', $html);
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
}
