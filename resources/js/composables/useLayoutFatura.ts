import { computed, ref } from 'vue';
import { salvar } from '@/routes/fatura/layout';

/**
 * O estado do editor de layout.
 *
 * Note o que NÃO tem mais aqui: o `fetch` de preview e o debounce de
 * 250ms que o acompanhava. Enquanto o documento era Blade, cada toggle
 * precisava de uma ida ao servidor pra devolver HTML; agora os
 * componentes de `documento/` reagem direto a este estado e o preview
 * é síncrono.
 *
 * Sobrou só o que sempre foi o miolo: manipular a configuração e
 * persistir. É por isso que o editor nunca conseguiu emitir markup —
 * ele só mexe em estrutura de dados.
 */

export type DefinicaoOpcao =
    | { tipo: 'bool'; rotulo: string; padrao: boolean }
    | {
          tipo: 'select';
          rotulo: string;
          padrao: string;
          valores: Record<string, string>;
      };

export type VarianteCatalogo = {
    rotulo: string;
    descricao: string;
};

export type BlocoCatalogo = {
    rotulo: string;
    descricao: string;
    icone: string;
    disponivel: boolean;
    /** Obrigatório não oferece a escolha "sem nenhum". */
    obrigatorio: boolean;
    /** Rótulo do cartão vazio; só existe em bloco opcional. */
    rotulo_vazio?: string;
    variantes: Record<string, VarianteCatalogo>;
    opcoes: Record<string, DefinicaoOpcao>;
};

export type ValorOpcao = boolean | string;

export type BlocoLayout = {
    id: string;
    /** Chave da variante escolhida; `null` é "sem nenhum". */
    variante: string | null;
    opcoes: Record<string, ValorOpcao>;
};

export type Tema = {
    arredondamento: string;
    destaque: string;
    densidade: string;
};

export type Layout = {
    paginas: BlocoLayout[][];
    tema: Tema;
};

/** Posição de um bloco: em que página e em que índice dentro dela. */
export type Posicao = { pagina: number; indice: number };

const clonar = <T>(valor: T): T => JSON.parse(JSON.stringify(valor)) as T;

function csrf(): string {
    return (
        document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute('content') ?? ''
    );
}

export function useLayoutFatura(inicial: Layout) {
    const layout = ref<Layout>(clonar(inicial));

    /** Referência do que está salvo, pra saber se há mudança pendente. */
    const salvo = ref(JSON.stringify(inicial));
    const sujo = computed(() => JSON.stringify(layout.value) !== salvo.value);

    async function persistir() {
        const resposta = await fetch(salvar.url(), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                Accept: 'application/json',
            },
            body: JSON.stringify(layout.value),
        });

        if (resposta.ok) {
            salvo.value = JSON.stringify(layout.value);
        }

        return resposta.ok;
    }

    function descartar() {
        layout.value = clonar(JSON.parse(salvo.value) as Layout);
    }

    // --- manipulação da configuração ---

    /** `null` esconde o bloco — é o que sobrou do antigo interruptor. */
    function definirVariante(pos: Posicao, variante: string | null) {
        layout.value.paginas[pos.pagina][pos.indice].variante = variante;
    }

    function definirOpcao(pos: Posicao, chave: string, valor: ValorOpcao) {
        layout.value.paginas[pos.pagina][pos.indice].opcoes[chave] = valor;
    }

    /**
     * Move um bloco pra outra posição, possivelmente em outra página.
     *
     * Remover antes de inserir mexe nos índices, então o destino é
     * corrigido quando a origem estava antes dele na mesma página.
     * Páginas que ficam vazias somem — é o que faz "arrastar o último
     * bloco pra fora" ser a forma de apagar uma página.
     */
    function mover(de: Posicao, para: Posicao) {
        const paginas = layout.value.paginas;
        const [bloco] = paginas[de.pagina].splice(de.indice, 1);

        const destino =
            de.pagina === para.pagina && de.indice < para.indice
                ? para.indice - 1
                : para.indice;

        paginas[para.pagina].splice(destino, 0, bloco);

        layout.value.paginas = paginas.filter((p) => p.length > 0);
    }

    function novaPagina(de: Posicao) {
        const paginas = layout.value.paginas;
        const [bloco] = paginas[de.pagina].splice(de.indice, 1);

        paginas.push([bloco]);
        layout.value.paginas = paginas.filter((p) => p.length > 0);
    }

    return {
        layout,
        sujo,
        persistir,
        descartar,
        definirVariante,
        definirOpcao,
        mover,
        novaPagina,
    };
}
