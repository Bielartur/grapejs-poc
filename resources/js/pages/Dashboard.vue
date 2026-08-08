<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import EditorTemplatePdf from '@/components/pdf-template/EditorTemplatePdf.vue';
import { dashboard } from '@/routes';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Editor de template de PDF" />

    <div class="flex h-full min-h-0 flex-1 flex-col gap-3 p-4">
        <div>
            <h1 class="text-lg font-semibold">Editor de template de PDF</h1>
            <p class="text-sm text-muted-foreground">
                Protótipo com GrapesJS: monte o layout, vincule os campos ao
                schema fixo e veja o HTML que alimentaria a geração do PDF.
            </p>
        </div>

        <div class="min-h-[70vh] flex-1">
            <EditorTemplatePdf />
        </div>
    </div>
</template>
