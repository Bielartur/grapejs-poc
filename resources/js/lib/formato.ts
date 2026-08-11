/**
 * FORMATAÇÃO DO DOCUMENTO
 *
 * Enquanto o Blade renderizava, quem formatava era a diretiva `@money`
 * (um `number_format` no PHP). Com o documento em Vue, a formatação
 * atravessa pro front — e este arquivo é o ÚNICO lugar onde ela mora.
 *
 * É uma dívida consciente: passa a existir um formatador de moeda aqui
 * e outro no PHP, e eles precisam concordar. Daí a paridade com o
 * `@money` estar escrita aqui em cima, em vez de espalhada pelos
 * componentes.
 *
 *   @money  →  'R$ '.number_format($v, 2, ',', '.')   →  "R$ 1.234,56"
 */

const MOEDA = new Intl.NumberFormat('pt-BR', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

/**
 * O `d/m/Y` do Blade. `timeZone: 'UTC'` é obrigatório: a data chega como
 * meia-noite UTC e, sem isso, qualquer fuso a oeste renderiza o dia
 * anterior — a fatura emitida dia 1º viraria dia 31.
 */
const DATA = new Intl.DateTimeFormat('pt-BR', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    timeZone: 'UTC',
});

/** Equivalente ao `@money($v)`. */
export function dinheiro(valor: number | null | undefined): string {
    return `R$ ${MOEDA.format(Number(valor) || 0)}`;
}

/**
 * Equivalente ao `$data?->format('d/m/Y')`. Devolve string vazia quando
 * não há data, igual ao operador `?->` do Blade — que imprime nada.
 */
export function dataBr(iso: string | null | undefined): string {
    if (!iso) {
        return '';
    }

    const d = new Date(iso);

    return Number.isNaN(d.getTime()) ? '' : DATA.format(d);
}
