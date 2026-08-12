import { computed, inject, provide } from 'vue';
import type { ComputedRef, InjectionKey } from 'vue';
import { cn } from '@/lib/utils';

/**
 * TOKEN → APARÊNCIA
 *
 * A outra metade do `EstilosBloco` do PHP. Lá ficam os eixos e a
 * fronteira de confiança; aqui fica a tradução, no front pelo mesmo
 * motivo que `classesDoTema`: o preview precisa reagir na hora, e pedir
 * classe ao servidor a cada clique devolveria o round-trip que a
 * migração pro Vue existe pra eliminar.
 *
 * TUDO SAI EM UTILITÁRIO TAILWIND — nenhuma regra CSS escrita à mão,
 * nenhuma propriedade de aparência solta no atributo `style`. O que muda
 * entre os dois tipos de eixo é só de onde vem o VALOR:
 *
 *   eixo discreto -> classe da escala (`text-lg`, `font-bold`).
 *
 *   eixo de cor   -> utilitário apontando pra variável
 *                    (`text-[color:var(--fe-cor)]`), com a variável
 *                    definida inline. Ver `CLASSES_DE_COR` abaixo.
 *
 * Em ambos os casos a classe é escrita LITERAL neste arquivo, porque o
 * Tailwind varre o código atrás de strings completas: `text-${t}` não é
 * detectado e simplesmente não existiria no CSS gerado.
 *
 * `null` é o neutro: o eixo não foi mexido, então nada é emitido e a
 * marcação original do bloco prevalece. É o que permite mexer só no peso
 * do título sem herdar um tamanho genérico que apagaria a hierarquia que
 * o componente já tinha.
 */

export type EstilosElemento = Record<string, string | null>;
export type EstilosBloco = Record<string, EstilosElemento>;

const TAMANHOS: Record<string, string> = {
    xs: 'text-xs',
    sm: 'text-sm',
    base: 'text-base',
    lg: 'text-lg',
    xl: 'text-xl',
};

const PESOS: Record<string, string> = {
    normal: 'font-normal',
    medio: 'font-medium',
    forte: 'font-bold',
};

const ALINHAMENTOS: Record<string, string> = {
    esquerda: 'text-left',
    centro: 'text-center',
    direita: 'text-right',
};

const ESPACAMENTOS: Record<string, string> = {
    nenhum: 'p-0',
    compacto: 'p-2',
    normal: 'p-4',
    solto: 'p-6',
};

/**
 * A largura da borda sai como classe, mas a COR dela sai no style — daí
 * `border-solid` explícito: o preflight do Tailwind zera a largura de
 * toda borda, então sem a classe de largura a cor existiria com 0px e o
 * controle pareceria não fazer nada.
 */
const BORDAS: Record<string, string> = {
    nenhuma: 'border-0',
    fina: 'border border-solid',
    grossa: 'border-2 border-solid',
};

const ARREDONDAMENTOS: Record<string, string> = {
    reto: 'rounded-none',
    sutil: 'rounded-sm',
    medio: 'rounded-md',
    redondo: 'rounded-lg',
};

const CLASSES: Record<string, Record<string, string>> = {
    tamanho: TAMANHOS,
    peso: PESOS,
    alinhamento: ALINHAMENTOS,
    espacamento: ESPACAMENTOS,
    borda: BORDAS,
    arredondamento: ARREDONDAMENTOS,
};

/**
 * COR LIVRE, AINDA ASSIM EM TAILWIND
 *
 * O Tailwind não gera classe em runtime: `#7C3AED` escolhido pelo
 * usuário jamais existiria como `text-[#7C3AED]` no CSS compilado,
 * porque essa string não está em lugar nenhum do código-fonte.
 *
 * A saída idiomática é utilitário de valor arbitrário apontando pra uma
 * VARIÁVEL: `text-[color:var(--fe-cor)]` é literal aqui embaixo, então o
 * Tailwind a compila normalmente, e só o valor da variável é dinâmico.
 * Nenhuma regra CSS escrita à mão, nenhuma propriedade de aparência solta
 * no atributo `style` — o que vai inline é só a definição da variável.
 *
 * Se um dia a cor livre sair de escopo e sobrar só a paleta, estas três
 * linhas viram classes fixas (`text-sky-700` e afins) e o `style` some de
 * vez.
 */
const CLASSES_DE_COR: Record<string, string> = {
    cor: 'text-[color:var(--fe-cor)]',
    fundo: 'bg-[var(--fe-fundo)]',
    cor_borda: 'border-[color:var(--fe-borda)]',
};

const VARIAVEIS: Record<string, string> = {
    cor: '--fe-cor',
    fundo: '--fe-fundo',
    cor_borda: '--fe-borda',
};

/** O que um elemento recebe: classes, style e o marcador de seleção. */
export type BindElemento = {
    class: string;
    style: Record<string, string>;
    'data-elemento': string;
};

export type Resolvedor = (elemento: string, base?: string) => BindElemento;

const CHAVE: InjectionKey<ComputedRef<Resolvedor>> = Symbol('estilos-bloco');

function resolver(estilos: EstilosElemento, base: string): BindElemento {
    const classes: string[] = [];
    const style: Record<string, string> = {};

    for (const [eixo, valor] of Object.entries(estilos)) {
        if (!valor) {
            continue;
        }

        if (CLASSES_DE_COR[eixo]) {
            /*
             * A classe só entra quando há cor escolhida. Deixá-la fixa
             * com a variável indefinida tornaria a declaração inválida —
             * e `color` inválido não cai no valor do componente, cai na
             * HERANÇA do pai, mudando a aparência sem ninguém ter pedido.
             */
            classes.push(CLASSES_DE_COR[eixo]);
            style[VARIAVEIS[eixo]] = valor;

            continue;
        }

        const classe = CLASSES[eixo]?.[valor];

        if (classe) {
            classes.push(classe);
        }
    }

    return {
        /*
         * `cn()` (twMerge) e não concatenação: `class="text-lg"` mais um
         * token `text-xs` deixaria AS DUAS no elemento, e quem venceria
         * seria a ordem do CSS gerado pelo Tailwind — não a do atributo.
         * O controle pareceria quebrado de forma intermitente.
         */
        class: cn(base, classes.join(' ')),
        style,
        'data-elemento': '',
    };
}

/**
 * Chamado uma vez por bloco, pelo `BlocoDocumento`.
 *
 * Por bloco e não por documento porque o mesmo nome de elemento
 * (`titulo`) existe em vários blocos com valores diferentes: o
 * componente pede "meu título" e recebe o do bloco dele, sem precisar
 * saber qual é.
 */
export function proverEstilosBloco(obter: () => EstilosBloco): void {
    provide(
        CHAVE,
        computed(() => {
            const estilos = obter();

            return (elemento: string, base = '') => ({
                ...resolver(estilos[elemento] ?? {}, base),
                'data-elemento': elemento,
            });
        }),
    );
}

/**
 * Usado pelos componentes de bloco, com `v-bind`:
 *
 *     <p v-bind="e('titulo', 'text-lg font-bold')">
 *
 * Um bind só entrega as três coisas — classe, style e `data-elemento`.
 * Separá-los deixaria possível estilizar um elemento sem marcá-lo, e um
 * elemento sem marcador é um elemento que o usuário não consegue clicar
 * pra abrir o painel: controle declarado e inalcançável.
 *
 * Devolve resolvedor inerte sem provedor acima, em vez de estourar como
 * `usarTemaFatura`: sem tema o bloco fica visualmente quebrado e é
 * melhor falhar alto, enquanto sem estilo ele só perde a customização e
 * segue correto — que é o que o cartão de preview da sidebar precisa.
 */
export function usarEstilosBloco(): ComputedRef<Resolvedor> {
    return inject(
        CHAVE,
        computed(() => (elemento: string, base = '') => ({
            class: base,
            style: {},
            'data-elemento': elemento,
        })),
    );
}
