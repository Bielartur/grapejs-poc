import { computed, inject, provide } from 'vue';
import type { ComputedRef, InjectionKey } from 'vue';
import type { Tema } from '@/composables/useLayoutFatura';

/**
 * AS CLASSES DE APARÊNCIA DO DOCUMENTO
 *
 * Porte do `LayoutFatura::classes()` do PHP. Ele continua existindo lá
 * enquanto o Blade renderiza; quando o documento for só Vue (Fase 3),
 * este arquivo passa a ser a única fonte.
 *
 * Mora no front porque o preview precisa reagir na hora: pedir as
 * classes ao servidor a cada troca de cor devolveria o round-trip que a
 * migração existe pra eliminar.
 *
 * As classes são escritas LITERAIS de propósito. O Tailwind varre o
 * código atrás de strings completas — `bg-${cor}-50` não é detectado e a
 * cor simplesmente não existiria no CSS gerado.
 */

export type ClassesTema = {
    arredondamento: string;
    destaque_fundo: string;
    destaque_borda: string;
    destaque_texto: string;
    densidade: string;
};

export function classesDoTema(tema: Tema): ClassesTema {
    const arredondamento =
        {
            none: 'rounded-none',
            sm: 'rounded-sm',
            lg: 'rounded-lg',
            md: 'rounded-md',
        }[tema.arredondamento] ?? 'rounded-md';

    const destaque = {
        emerald: {
            fundo: 'bg-emerald-50',
            borda: 'border-emerald-500',
            texto: 'text-emerald-700',
        },
        violet: {
            fundo: 'bg-violet-50',
            borda: 'border-violet-500',
            texto: 'text-violet-700',
        },
        slate: {
            fundo: 'bg-slate-100',
            borda: 'border-slate-500',
            texto: 'text-slate-700',
        },
        sky: {
            fundo: 'bg-sky-50',
            borda: 'border-sky-500',
            texto: 'text-sky-700',
        },
    }[tema.destaque] ?? {
        fundo: 'bg-sky-50',
        borda: 'border-sky-500',
        texto: 'text-sky-700',
    };

    return {
        arredondamento,
        destaque_fundo: destaque.fundo,
        destaque_borda: destaque.borda,
        destaque_texto: destaque.texto,
        densidade:
            tema.densidade === 'confortavel' ? 'p-4 text-sm' : 'p-2 text-xs',
    };
}

const CHAVE: InjectionKey<ComputedRef<ClassesTema>> = Symbol('tema-fatura');

/** Chamado uma vez pelo `DocumentoFatura`. */
export function proverTemaFatura(obterTema: () => Tema): void {
    provide(
        CHAVE,
        computed(() => classesDoTema(obterTema())),
    );
}

/** Usado pelos blocos que têm alguma escolha de aparência. */
export function usarTemaFatura(): ComputedRef<ClassesTema> {
    const classes = inject(CHAVE, null);

    if (!classes) {
        throw new Error(
            'usarTemaFatura() exige um <DocumentoFatura> acima na árvore.',
        );
    }

    return classes;
}
