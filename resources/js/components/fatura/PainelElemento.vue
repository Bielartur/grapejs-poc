<script setup lang="ts">
import { Check, Pipette, RotateCcw, X } from '@lucide/vue';
import { computed } from 'vue';
import type { BlocoLayout } from '@/composables/useLayoutFatura';

/**
 * O PAINEL DO ELEMENTO — a sidebar da direita
 *
 * Abre quando o usuário clica num pedaço do documento e mostra o que
 * AQUELE pedaço aceita. Quem decide o que ele aceita é o catálogo: cada
 * elemento declara seus eixos, e eixo não declarado não tem controle
 * aqui. É o que mantém a liberdade dentro de trilhos sem precisar travar
 * o componente.
 *
 * NENHUM SELECT. Escala curta atrás de dropdown esconde as opções e
 * cobra dois cliques por uma escolha que devia custar um — cor então,
 * que é comparação visual, num select é quase inútil. Tudo aqui é botão
 * ou amostra, visível de uma vez.
 *
 * "Padrão" é a ausência de valor, não um valor: quando um eixo está
 * neutro o componente manda na aparência dele. Daí o ↺ aparecer só
 * quando há o que desfazer — sem customização não existe "voltar".
 */

const props = defineProps<{
    bloco: BlocoLayout;
    /** Rótulo do bloco, só pra situar o usuário no cabeçalho. */
    rotuloBloco: string;
    elemento: string;
    rotuloElemento: string;
    eixos: string[];
    /** Escala de cada eixo discreto: `{ tamanho: { sm: 'Pequeno' } }`. */
    escalas: Record<string, Record<string, string>>;
    /** Hex => nome, a paleta de um clique. */
    paleta: Record<string, string>;
    eixosDeCor: string[];
}>();

const emit = defineEmits<{
    definirEstilo: [eixo: string, valor: string | null];
    fechar: [];
}>();

const ROTULOS: Record<string, string> = {
    tamanho: 'Tamanho',
    peso: 'Peso',
    alinhamento: 'Alinhamento',
    espacamento: 'Espaçamento',
    borda: 'Borda',
    arredondamento: 'Cantos',
    cor: 'Cor do texto',
    fundo: 'Cor de fundo',
    cor_borda: 'Cor da borda',
};

const ehCor = (eixo: string) => props.eixosDeCor.includes(eixo);

const valorDe = (eixo: string) => props.bloco.estilos[props.elemento]?.[eixo];

const customizado = computed(() => props.eixos.some((eixo) => valorDe(eixo)));

/** Clicar no valor já ativo desliga — é o caminho curto pro neutro. */
function alternar(eixo: string, valor: string) {
    emit('definirEstilo', eixo, valorDe(eixo) === valor ? null : valor);
}

function limparTudo() {
    props.eixos.forEach((eixo) => emit('definirEstilo', eixo, null));
}

const paletaEntradas = computed(() => Object.entries(props.paleta));
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <p class="truncate text-[11px] text-muted-foreground">
                    {{ rotuloBloco }}
                </p>
                <h2 class="text-sm font-semibold">{{ rotuloElemento }}</h2>
            </div>

            <button
                type="button"
                class="rounded p-1 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                aria-label="Fechar painel"
                @click="emit('fechar')"
            >
                <X class="size-4" />
            </button>
        </div>

        <button
            v-if="customizado"
            type="button"
            class="flex w-fit items-center gap-1.5 rounded-md border px-2 py-1 text-[11px] text-muted-foreground transition-colors hover:border-primary hover:text-foreground"
            @click="limparTudo"
        >
            <RotateCcw class="size-3" />
            Voltar tudo ao padrão
        </button>

        <div v-for="eixo in eixos" :key="eixo" class="flex flex-col gap-1.5">
            <div class="flex items-center justify-between gap-2">
                <span
                    class="text-[10px] font-semibold tracking-wider text-muted-foreground uppercase"
                >
                    {{ ROTULOS[eixo] ?? eixo }}
                </span>

                <button
                    v-if="valorDe(eixo)"
                    type="button"
                    class="text-muted-foreground transition-colors hover:text-foreground"
                    :title="`Voltar ${ROTULOS[eixo]} ao padrão`"
                    @click="emit('definirEstilo', eixo, null)"
                >
                    <RotateCcw class="size-3" />
                </button>
            </div>

            <!-- COR: amostras da paleta, mais o seletor livre -->
            <div v-if="ehCor(eixo)" class="flex flex-col gap-2">
                <div class="grid grid-cols-6 gap-1.5">
                    <button
                        v-for="[hex, nome] in paletaEntradas"
                        :key="hex"
                        type="button"
                        class="relative flex aspect-square items-center justify-center rounded-md border transition-transform hover:scale-110"
                        :class="
                            valorDe(eixo) === hex
                                ? 'border-primary ring-2 ring-primary'
                                : 'border-black/10'
                        "
                        :style="{ backgroundColor: hex }"
                        :title="nome"
                        @click="alternar(eixo, hex)"
                    >
                        <!--
                            O tique precisa contrastar com a amostra, e a
                            amostra é justamente uma cor qualquer — daí
                            `mix-blend-difference`, que resolve claro e
                            escuro sem a gente calcular luminância.
                        -->
                        <Check
                            v-if="valorDe(eixo) === hex"
                            class="size-3.5 text-white mix-blend-difference"
                        />
                    </button>
                </div>

                <label
                    class="flex cursor-pointer items-center gap-2 rounded-md border px-2 py-1.5 text-xs transition-colors hover:border-primary"
                >
                    <Pipette class="size-3.5 shrink-0 text-muted-foreground" />
                    <span class="flex-1">Personalizada</span>

                    <span
                        class="size-4 rounded border border-black/10"
                        :style="{ backgroundColor: valorDe(eixo) ?? undefined }"
                    />

                    <!--
                        O input nativo fica invisível dentro do <label>:
                        o clique em qualquer parte da linha abre o seletor
                        do sistema, e não precisamos de dependência nova
                        só pra escolher cor.
                    -->
                    <input
                        type="color"
                        class="sr-only"
                        :value="valorDe(eixo) ?? '#000000'"
                        @input="
                            emit(
                                'definirEstilo',
                                eixo,
                                (
                                    $event.target as HTMLInputElement
                                ).value.toUpperCase(),
                            )
                        "
                    />
                </label>
            </div>

            <!-- EIXO DISCRETO: a escala inteira visível, um clique cada -->
            <div v-else class="flex flex-wrap gap-1">
                <button
                    v-for="(nome, token) in escalas[eixo] ?? {}"
                    :key="token"
                    type="button"
                    class="rounded-md border px-2 py-1 text-xs transition-colors"
                    :class="
                        valorDe(eixo) === token
                            ? 'border-primary bg-primary text-primary-foreground'
                            : 'border-border hover:border-muted-foreground/50 hover:bg-muted'
                    "
                    @click="alternar(eixo, String(token))"
                >
                    {{ nome }}
                </button>
            </div>
        </div>
    </div>
</template>
