<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, FileText } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { dashboard } from '@/routes';
import { index as layoutFatura } from '@/routes/fatura/layout';

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
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <div
            class="flex flex-col items-start gap-3 rounded-xl border bg-card p-6"
        >
            <FileText class="size-5 text-muted-foreground" />

            <div>
                <h2 class="text-base font-semibold">Layout da fatura</h2>
                <p
                    class="max-w-xl text-sm leading-relaxed text-muted-foreground"
                >
                    Escolha quais blocos entram no PDF, em que ordem e em qual
                    página. Cada bloco é renderizado pelo mesmo Blade que gera o
                    documento final — o preview não é uma aproximação.
                </p>
            </div>

            <Button as-child size="sm">
                <Link :href="layoutFatura()">
                    Abrir editor <ArrowRight />
                </Link>
            </Button>
        </div>
    </div>
</template>
