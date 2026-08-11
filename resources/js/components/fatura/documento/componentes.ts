import type { Component } from 'vue';
import AnexosConcessionariaPadrao from '@/components/fatura/documento/blocos/anexos_concessionaria/Padrao.vue';
import BoletoPadrao from '@/components/fatura/documento/blocos/boleto/Padrao.vue';
import CabecalhoCentralizado from '@/components/fatura/documento/blocos/cabecalho/Centralizado.vue';
import CabecalhoCompacto from '@/components/fatura/documento/blocos/cabecalho/Compacto.vue';
import CabecalhoPadrao from '@/components/fatura/documento/blocos/cabecalho/Padrao.vue';
import CodigoBarrasPadrao from '@/components/fatura/documento/blocos/codigo_barras/Padrao.vue';
import DadosClientePadrao from '@/components/fatura/documento/blocos/dados_cliente/Padrao.vue';
import DetalhamentoUcCartoes from '@/components/fatura/documento/blocos/detalhamento_uc/Cartoes.vue';
import DetalhamentoUcPadrao from '@/components/fatura/documento/blocos/detalhamento_uc/Padrao.vue';
import DetalhamentoUcResumida from '@/components/fatura/documento/blocos/detalhamento_uc/Resumida.vue';
import HistoricoConsumoPadrao from '@/components/fatura/documento/blocos/historico_consumo/Padrao.vue';
import NotificacaoProblemaPadrao from '@/components/fatura/documento/blocos/notificacao_problema/Padrao.vue';
import PixPadrao from '@/components/fatura/documento/blocos/pix/Padrao.vue';
import ResumoConsumoFaixa from '@/components/fatura/documento/blocos/resumo_consumo/Faixa.vue';
import ResumoConsumoLista from '@/components/fatura/documento/blocos/resumo_consumo/Lista.vue';
import ResumoConsumoPadrao from '@/components/fatura/documento/blocos/resumo_consumo/Padrao.vue';

/**
 * O PAREAMENTO CATÁLOGO ↔ COMPONENTE
 *
 * Bloco → variante → componente. Toda chave dos dois níveis precisa
 * existir no `CatalogoBlocos` do PHP, e toda variante declarada lá
 * precisa existir aqui — `grafico` é a única ausência legítima, porque o
 * catálogo não lhe dá variante nenhuma. O teste de pareamento
 * (`LayoutFaturaTest`) trava isso nos DOIS níveis, lendo este arquivo.
 *
 * Mora fora do `DocumentoFatura` porque não é só ele que precisa: os
 * cartões de preview da sidebar desenham O MESMO componente, e é isso
 * que faz a miniatura ser fiel ao que o Browsershot vai imprimir em vez
 * de uma ilustração que envelhece sozinha.
 */
export const COMPONENTES: Record<string, Record<string, Component>> = {
    cabecalho: {
        padrao: CabecalhoPadrao,
        compacto: CabecalhoCompacto,
        centralizado: CabecalhoCentralizado,
    },
    dados_cliente: {
        padrao: DadosClientePadrao,
    },
    resumo_consumo: {
        padrao: ResumoConsumoPadrao,
        faixa: ResumoConsumoFaixa,
        lista: ResumoConsumoLista,
    },
    pix: {
        padrao: PixPadrao,
    },
    codigo_barras: {
        padrao: CodigoBarrasPadrao,
    },
    boleto: {
        padrao: BoletoPadrao,
    },
    detalhamento_uc: {
        padrao: DetalhamentoUcPadrao,
        resumida: DetalhamentoUcResumida,
        cartoes: DetalhamentoUcCartoes,
    },
    historico_consumo: {
        padrao: HistoricoConsumoPadrao,
    },
    anexos_concessionaria: {
        padrao: AnexosConcessionariaPadrao,
    },
    notificacao_problema: {
        padrao: NotificacaoProblemaPadrao,
    },
};

/** `null` quando não há variante escolhida ou o mapa não a conhece. */
export function componenteDe(
    idBloco: string,
    variante: string | null,
): Component | null {
    return variante ? (COMPONENTES[idBloco]?.[variante] ?? null) : null;
}
