<script setup lang="ts">
import { ArrowLeft, Check, EyeOff } from '@lucide/vue';
import { computed } from 'vue';
import ChaveBloco from '@/components/fatura/ChaveBloco.vue';
import EsquemaVariante from '@/components/fatura/EsquemaVariante.vue';
import { iconeDoBloco } from '@/components/fatura/icones';
import { Badge } from '@/components/ui/badge';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type {
    BlocoCatalogo,
    BlocoLayout,
    ValorOpcao,
} from '@/composables/useLayoutFatura';

/**
 * A SUBPÁGINA DE UM BLOCO
 *
 * O segundo nível da sidebar: em vez de um interruptor liga/desliga na
 * linha da lista, o bloco abre aqui e mostra as FORMAS que ele pode ter,
 * cada uma desenhada em esquema no cartão.
 *
 * Nos blocos opcionais o primeiro cartão é "sem nenhum" — foi pra onde
 * foi o interruptor. Esconder o bloco virou uma escolha entre as outras,
 * feita no mesmo lugar e com a mesma mecânica, o que tira da interface a
 * pergunta "está ligado?" separada de "com que cara?".
 *
 * O cartão desenha a SILHUETA da variante, não o componente encolhido —
 * ver `EsquemaVariante`. O conteúdo de verdade fica no preview grande,
 * que atualiza junto no clique.
 *
 * As opções do bloco descem pro rodapé desta página: elas afinam a
 * variante escolhida, então só fazem sentido depois dela.
 */

const props = defineProps<{
    bloco: BlocoLayout;
    definicao: BlocoCatalogo;
}>();

const emit = defineEmits<{
    voltar: [];
    definirVariante: [variante: string | null];
    definirOpcao: [chave: string, valor: ValorOpcao];
}>();

const variantes = computed(() => Object.entries(props.definicao.variantes));
const opcoes = computed(() => Object.entries(props.definicao.opcoes));

const rotuloVazio = computed(
    () =>
        props.definicao.rotulo_vazio ?? `Sem nenhum ${props.definicao.rotulo}`,
);
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-2">
            <button
                type="button"
                class="flex w-fit items-center gap-1.5 text-xs text-muted-foreground transition-colors hover:text-foreground"
                @click="emit('voltar')"
            >
                <ArrowLeft class="size-3.5" />
                Blocos da fatura
            </button>

            <div class="flex items-start gap-2">
                <component
                    :is="iconeDoBloco(definicao.icone)"
                    class="mt-0.5 size-4 shrink-0 text-muted-foreground"
                />
                <div class="min-w-0">
                    <h2 class="flex items-center gap-1.5 text-sm font-semibold">
                        {{ definicao.rotulo }}
                        <Badge
                            v-if="!definicao.disponivel"
                            variant="secondary"
                            class="px-1.5 py-0 text-[10px]"
                        >
                            Em breve
                        </Badge>
                    </h2>
                    <p class="text-xs leading-relaxed text-muted-foreground">
                        {{ definicao.descricao }}
                    </p>
                </div>
            </div>
        </div>

        <div
            v-if="!variantes.length"
            class="rounded-lg border border-dashed px-4 py-6 text-center text-xs leading-relaxed text-muted-foreground"
        >
            Este bloco ainda não tem nenhum componente pronto. Assim que tiver,
            as formas disponíveis aparecem aqui.
        </div>

        <!-- escolher UMA forma entre N é a semântica de rádio, não a de botão -->
        <div
            v-else
            role="radiogroup"
            :aria-label="`Componente de ${definicao.rotulo}`"
            class="flex flex-col gap-2"
        >
            <p
                class="text-[10px] font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Escolha o componente
            </p>

            <!-- o cartão vazio: é pra onde foi o interruptor -->
            <div
                v-if="!definicao.obrigatorio"
                role="radio"
                tabindex="0"
                :aria-checked="bloco.variante === null"
                class="flex cursor-pointer items-center gap-3 rounded-lg border px-3 py-3 transition-colors outline-none focus-visible:ring-2 focus-visible:ring-ring"
                :class="
                    bloco.variante === null
                        ? 'border-primary ring-1 ring-primary'
                        : 'border-border hover:border-muted-foreground/40'
                "
                @click="emit('definirVariante', null)"
                @keydown.enter.prevent="emit('definirVariante', null)"
                @keydown.space.prevent="emit('definirVariante', null)"
            >
                <span
                    class="flex size-9 shrink-0 items-center justify-center rounded-md border border-dashed text-muted-foreground"
                >
                    <EyeOff class="size-4" />
                </span>

                <span class="min-w-0 flex-1">
                    <span class="block text-sm font-medium">
                        {{ rotuloVazio }}
                    </span>
                    <span class="block text-xs text-muted-foreground">
                        O bloco não entra no documento.
                    </span>
                </span>

                <Check
                    v-if="bloco.variante === null"
                    class="size-4 shrink-0 text-primary"
                />
            </div>

            <div
                v-for="[chave, variante] in variantes"
                :key="chave"
                role="radio"
                tabindex="0"
                :aria-checked="bloco.variante === chave"
                class="cursor-pointer overflow-hidden rounded-lg border transition-colors outline-none focus-visible:ring-2 focus-visible:ring-ring"
                :class="
                    bloco.variante === chave
                        ? 'border-primary ring-1 ring-primary'
                        : 'border-border hover:border-muted-foreground/40'
                "
                @click="emit('definirVariante', chave)"
                @keydown.enter.prevent="emit('definirVariante', chave)"
                @keydown.space.prevent="emit('definirVariante', chave)"
            >
                <EsquemaVariante :id-bloco="bloco.id" :variante="chave" />

                <div
                    class="flex items-center gap-2 border-t bg-muted/30 px-3 py-2"
                >
                    <div class="min-w-0 flex-1">
                        <p class="text-sm font-medium">{{ variante.rotulo }}</p>
                        <p class="text-xs leading-snug text-muted-foreground">
                            {{ variante.descricao }}
                        </p>
                    </div>

                    <Check
                        v-if="bloco.variante === chave"
                        class="size-4 shrink-0 text-primary"
                    />
                </div>
            </div>
        </div>

        <!-- opções afinam a variante escolhida: sem uma, não há o que afinar -->
        <div
            v-if="opcoes.length && bloco.variante"
            class="flex flex-col gap-2.5 border-t pt-4"
        >
            <p
                class="text-[10px] font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Opções do bloco
            </p>

            <div
                v-for="[chave, opcao] in opcoes"
                :key="chave"
                class="flex items-center justify-between gap-3"
            >
                <Label class="text-xs font-normal">{{ opcao.rotulo }}</Label>

                <ChaveBloco
                    v-if="opcao.tipo === 'bool'"
                    :model-value="Boolean(bloco.opcoes[chave])"
                    :rotulo="opcao.rotulo"
                    @update:model-value="emit('definirOpcao', chave, $event)"
                />

                <Select
                    v-else
                    :model-value="String(bloco.opcoes[chave])"
                    @update:model-value="
                        emit('definirOpcao', chave, String($event))
                    "
                >
                    <SelectTrigger class="h-7 w-40 text-xs">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="(rotulo, valor) in opcao.valores"
                            :key="valor"
                            :value="String(valor)"
                        >
                            {{ rotulo }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </div>
    </div>
</template>
