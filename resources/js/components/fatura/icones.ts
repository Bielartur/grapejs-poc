import {
    Barcode,
    Building2,
    FileText,
    History,
    LineChart,
    Paperclip,
    QrCode,
    Receipt,
    Table,
    TriangleAlert,
    User,
} from '@lucide/vue';
// o tipo vem do Vue, não do lucide: lá `Component` é o componente em si,
// um valor, e usá-lo como tipo não compila
import type { Component } from 'vue';

/**
 * O `icone` que o `CatalogoBlocos` declara, resolvido em componente.
 *
 * Fica fora dos componentes porque os dois níveis da sidebar usam o
 * mesmo mapa: a lista de blocos e o cabeçalho da subpágina de variantes.
 */
const ICONES: Record<string, Component> = {
    building: Building2,
    user: User,
    receipt: Receipt,
    'qr-code': QrCode,
    barcode: Barcode,
    'file-text': FileText,
    'line-chart': LineChart,
    table: Table,
    history: History,
    paperclip: Paperclip,
    'triangle-alert': TriangleAlert,
};

export function iconeDoBloco(nome: string | undefined): Component {
    return ICONES[nome ?? ''] ?? FileText;
}
