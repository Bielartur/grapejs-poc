import { useDebounceFn } from '@vueuse/core';
import { computed, ref, watch } from 'vue';
import { preview, salvar } from '@/routes/fatura/layout';

/**
 * O estado do editor de layout.
 *
 * Note o que NÃO tem aqui: nada que saiba desenhar uma fatura. O
 * documento é renderizado pelo Blade — o mesmo que geraria o PDF — e
 * chega como string de HTML. Este composable só manipula a configuração
 * e pede o preview de volta.
 */

export type DefinicaoOpcao =
    | { tipo: 'bool'; rotulo: string; padrao: boolean }
    | {
          tipo: 'select';
          rotulo: string;
          padrao: string;
          valores: Record<string, string>;
      };

export type BlocoCatalogo = {
    rotulo: string;
    descricao: string;
    icone: string;
    disponivel: boolean;
    opcoes: Record<string, DefinicaoOpcao>;
};

export type ValorOpcao = boolean | string;

export type BlocoLayout = {
    id: string;
    ativo: boolean;
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

    /** Uma string de HTML por página do layout, na ordem. */
    const paginas = ref<string[]>([]);
    const carregando = ref(false);
    const erro = ref<string | null>(null);

    async function requisitar(url: string): Promise<Response> {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                Accept: 'application/json',
            },
            body: JSON.stringify(layout.value),
        });
    }

    /**
     * Debounce porque cada toggle dispara um render. 250ms é curto o
     * bastante pra parecer imediato e longo o bastante pra arrastar um
     * bloco não virar uma rajada de requisições.
     */
    const atualizarPreview = useDebounceFn(async () => {
        carregando.value = true;
        erro.value = null;

        try {
            const resposta = await requisitar(preview.url());

            if (!resposta.ok) {
                throw new Error(`HTTP ${resposta.status}`);
            }

            paginas.value = (await resposta.json()).paginas;
        } catch (e) {
            erro.value = e instanceof Error ? e.message : 'Falha ao renderizar';
        } finally {
            carregando.value = false;
        }
    }, 250);

    watch(layout, () => void atualizarPreview(), {
        deep: true,
        immediate: true,
    });

    async function persistir() {
        const resposta = await requisitar(salvar.url());

        if (resposta.ok) {
            salvo.value = JSON.stringify(layout.value);
        }

        return resposta.ok;
    }

    function descartar() {
        layout.value = clonar(JSON.parse(salvo.value) as Layout);
    }

    // --- manipulação da configuração ---

    function alternarAtivo(pos: Posicao) {
        const bloco = layout.value.paginas[pos.pagina][pos.indice];
        bloco.ativo = !bloco.ativo;
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
        paginas,
        carregando,
        erro,
        atualizarPreview,
        persistir,
        descartar,
        alternarAtivo,
        definirOpcao,
        mover,
        novaPagina,
    };
}
