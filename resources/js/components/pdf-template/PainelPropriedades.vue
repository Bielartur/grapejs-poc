<script setup lang="ts">
import { MousePointerClick, Trash2 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { TraitLeve } from '@/composables/useEditorPdf';
import {
    CLASSES_GERENCIADAS,
    SECOES,
    type GrupoUtilitario,
} from '@/lib/pdf-template/utilitarios-tailwind';

/**
 * Painel de propriedades — substitui inteiramente o Style Manager e o
 * Trait Manager do GrapesJS (ambos desligados com `custom: true`).
 *
 * Duas metades:
 *  - Vínculo de dados: renderiza os Traits declarados no Component Type.
 *  - Aparência: aplica utilitários Tailwind curados.
 */

const props = defineProps<{
    nome: string | null;
    tipo: string | null;
    traits: TraitLeve[];
    atributos: Record<string, string>;
    classes: string[];
}>();

const emit = defineEmits<{
    definirAtributo: [nome: string, valor: string];
    aplicarUtilitario: [grupo: GrupoUtilitario, classe: string];
    remover: [];
}>();

const secaoAberta = ref<string | null>('texto');

function alternarSecao(id: string) {
    secaoAberta.value = secaoAberta.value === id ? null : id;
}

/** Seções gerais + as específicas do tipo selecionado. */
const secoesVisiveis = computed(() =>
    SECOES.filter((s) => !s.tipos || (props.tipo && s.tipos.includes(props.tipo))),
);

/**
 * Uma seção específica de tipo é o motivo de o usuário ter clicado
 * naquele elemento — abrir sozinha poupa um clique e evita que ela passe
 * despercebida no meio das seções gerais, todas fechadas.
 */
watch(
    () => props.tipo,
    () => {
        const especifica = secoesVisiveis.value.find((s) => s.tipos);
        if (especifica) secaoAberta.value = especifica.id;
    },
);

/** Qual opção de cada grupo está aplicada no componente selecionado. */
function ativa(grupo: GrupoUtilitario): string | null {
    return (
        grupo.opcoes.find((o) => props.classes.includes(o.classe))?.classe ??
        null
    );
}

/** Classes que o usuário/bloco trouxe e que o painel não gerencia. */
const classesExtras = computed(() =>
    props.classes.filter((c) => !CLASSES_GERENCIADAS.has(c)),
);

const ehImagem = computed(() => props.tipo === 'image');
</script>

<template>
    <div v-if="!nome" class="flex flex-col items-center gap-2 px-2 py-10 text-center">
        <MousePointerClick class="size-5 text-muted-foreground" />
        <p class="text-sm text-muted-foreground">
            Selecione um elemento no documento para editar.
        </p>
    </div>

    <div v-else class="flex flex-col gap-4">
        <div class="flex items-center justify-between gap-2">
            <span
                class="truncate rounded-md bg-primary/10 px-2 py-1 text-xs font-medium text-primary"
            >
                {{ nome }}
            </span>
            <Button
                variant="ghost"
                size="icon-sm"
                class="text-destructive"
                title="Remover elemento"
                @click="emit('remover')"
            >
                <Trash2 />
            </Button>
        </div>

        <!-- TRAITS: editam atributos. É por aqui que sai o data-bind. -->
        <section v-if="traits.length" class="flex flex-col gap-2">
            <h3
                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
            >
                Vínculo de dados
            </h3>

            <div v-for="trait in traits" :key="trait.name" class="flex flex-col gap-1.5">
                <Label class="text-xs">{{ trait.label }}</Label>

                <Select
                    v-if="trait.type === 'select'"
                    :model-value="atributos[trait.name]"
                    @update:model-value="
                        emit('definirAtributo', trait.name, String($event))
                    "
                >
                    <SelectTrigger class="w-full">
                        <SelectValue placeholder="Escolha um campo" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="opcao in trait.options"
                            :key="opcao.value"
                            :value="opcao.value"
                        >
                            {{ opcao.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>

                <Input
                    v-else
                    :model-value="atributos[trait.name] ?? ''"
                    @update:model-value="
                        emit('definirAtributo', trait.name, String($event))
                    "
                />
            </div>
        </section>

        <!-- Imagem: o Asset Manager do GrapesJS está desligado, então a
             origem da imagem é um campo Vue comum. Data URI funciona
             direto no PDF, sem precisar de upload. -->
        <section v-if="ehImagem" class="flex flex-col gap-1.5">
            <h3
                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
            >
                Imagem
            </h3>
            <Label class="text-xs">URL</Label>
            <Input
                :model-value="atributos.src ?? ''"
                placeholder="https://…"
                @update:model-value="emit('definirAtributo', 'src', String($event))"
            />
        </section>

        <!-- APARÊNCIA: tokens Tailwind, um grupo por propriedade -->
        <section class="flex flex-col gap-1.5">
            <h3
                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
            >
                Aparência
            </h3>

            <div
                v-for="secao in secoesVisiveis"
                :key="secao.id"
                class="overflow-hidden rounded-lg border"
            >
                <button
                    type="button"
                    class="flex w-full items-center justify-between bg-muted/50 px-3 py-2 text-xs font-medium hover:bg-muted"
                    @click="alternarSecao(secao.id)"
                >
                    {{ secao.rotulo }}
                    <span class="text-muted-foreground">
                        {{ secaoAberta === secao.id ? '−' : '+' }}
                    </span>
                </button>

                <div
                    v-show="secaoAberta === secao.id"
                    class="flex flex-col gap-3 p-3"
                >
                    <div
                        v-for="grupo in secao.grupos"
                        :key="grupo.id"
                        class="flex flex-col gap-1.5"
                    >
                        <Label class="text-xs text-muted-foreground">
                            {{ grupo.rotulo }}
                        </Label>

                        <!-- amostras de cor -->
                        <div v-if="grupo.formato === 'cor'" class="flex flex-wrap gap-1.5">
                            <button
                                v-for="opcao in grupo.opcoes"
                                :key="opcao.classe"
                                type="button"
                                :title="opcao.rotulo"
                                class="size-6 rounded-md border transition-transform hover:scale-110"
                                :class="
                                    ativa(grupo) === opcao.classe
                                        ? 'ring-2 ring-primary ring-offset-1 ring-offset-background'
                                        : ''
                                "
                                :style="{ backgroundColor: opcao.amostra }"
                                @click="emit('aplicarUtilitario', grupo, opcao.classe)"
                            />
                        </div>

                        <!-- opções em texto -->
                        <div v-else class="flex flex-wrap gap-1">
                            <button
                                v-for="opcao in grupo.opcoes"
                                :key="opcao.classe"
                                type="button"
                                :title="opcao.classe"
                                class="rounded-md border px-2 py-1 text-xs transition-colors"
                                :class="
                                    ativa(grupo) === opcao.classe
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : 'hover:bg-accent'
                                "
                                @click="emit('aplicarUtilitario', grupo, opcao.classe)"
                            >
                                {{ opcao.rotulo }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section v-if="classesExtras.length" class="flex flex-col gap-1.5">
            <h3
                class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
            >
                Outras classes
            </h3>
            <div class="flex flex-wrap gap-1">
                <code
                    v-for="classe in classesExtras"
                    :key="classe"
                    class="rounded bg-muted px-1.5 py-0.5 text-[11px] text-muted-foreground"
                >
                    {{ classe }}
                </code>
            </div>
        </section>
    </div>
</template>
