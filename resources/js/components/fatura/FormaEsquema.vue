<script setup lang="ts">
import { computed } from 'vue';
import type { Forma, Tom } from '@/components/fatura/esquemas';

/**
 * Uma forma do esquema, desenhada com divs.
 *
 * Recursivo: uma `caixa` renderiza este mesmo componente pros filhos —
 * um SFC pode se referenciar pelo próprio nome de arquivo. É o que
 * permite o esquema ser um dado aninhado em vez de marcação repetida em
 * quinze cartões.
 *
 * Nada aqui é do documento: as cores saem dos tokens do EDITOR
 * (`foreground`, `primary`), então o desenho acompanha o tema claro ou
 * escuro da interface. O documento é papel e é sempre claro; o esquema é
 * interface e não deve ser.
 */

const props = defineProps<{ forma: Forma }>();

/** Traço: mais forte onde há título, mais fraco onde há texto corrido. */
const PREENCHIMENTO: Record<Tom, string> = {
    forte: 'bg-foreground/55',
    medio: 'bg-foreground/30',
    fraco: 'bg-foreground/15',
    destaque: 'bg-primary/55',
};

/** A mesma escala, rebaixada: caixa é fundo, não traço. */
const FUNDO: Record<Tom, string> = {
    forte: 'bg-foreground/45',
    medio: 'bg-foreground/20',
    fraco: 'bg-foreground/8',
    destaque: 'bg-primary/15',
};

const ALINHAMENTO = {
    inicio: 'items-start',
    centro: 'items-center',
    fim: 'items-end',
    entre: 'justify-between items-start',
};

const estilo = computed(() => {
    const f = props.forma;

    if (f.t === 'tabela') {
        return {};
    }

    return {
        width: f.l,
        height: f.a ? `${f.a}px` : undefined,
        padding: f.t === 'caixa' && f.recheio ? `${f.recheio}px` : undefined,
        gap: f.t === 'caixa' && f.gap ? `${f.gap}px` : undefined,
    };
});

const classes = computed(() => {
    const f = props.forma;

    if (f.t === 'barra') {
        return [
            'shrink-0 rounded-full',
            PREENCHIMENTO[f.tom ?? 'fraco'],
            f.a ? '' : 'h-1.5',
        ];
    }

    if (f.t === 'caixa') {
        return [
            'flex rounded-sm',
            f.direcao === 'linha' ? 'flex-row' : 'flex-col',
            f.alinhar ? ALINHAMENTO[f.alinhar] : '',
            f.tom ? FUNDO[f.tom] : '',
            f.borda ? 'border border-foreground/20' : '',
            f.borda === 'tracejada' ? 'border-dashed' : '',
            // sem filhos a caixa é um quadro cheio (logo, QR, barras)
            f.filhos?.length ? '' : 'shrink-0',
        ];
    }

    return [];
});

/** A primeira linha da tabela é o cabeçalho: traço mais forte. */
const tomDaLinha = (linha: number): Tom => (linha === 0 ? 'medio' : 'fraco');
</script>

<template>
    <!-- a grade tem estrutura própria e não vale a pena descrever com caixas -->
    <div v-if="forma.t === 'tabela'" class="flex w-full flex-col gap-[3px]">
        <div
            v-for="linha in forma.linhas"
            :key="linha"
            class="flex w-full gap-[4px]"
        >
            <div
                v-for="(largura, coluna) in forma.colunas"
                :key="coluna"
                class="h-1.5 rounded-full"
                :class="PREENCHIMENTO[tomDaLinha(linha - 1)]"
                :style="{ width: `${largura}%` }"
            />
        </div>
    </div>

    <div v-else :class="classes" :style="estilo">
        <template v-if="forma.t === 'caixa'">
            <FormaEsquema
                v-for="(filho, indice) in forma.filhos ?? []"
                :key="indice"
                :forma="filho"
            />
        </template>
    </div>
</template>
