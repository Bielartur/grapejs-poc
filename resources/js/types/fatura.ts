/**
 * O CONTRATO DE DADOS DA FATURA
 *
 * Enquanto o Blade renderizava, os dados eram objetos PHP e ninguém
 * precisava declarar a forma deles. Com o documento em Vue, tudo
 * atravessa como JSON — e é este arquivo que diz o que atravessa.
 *
 * Os nomes seguem em snake_case (`invoice`, `customer`,
 * `invoice_items`), espelhando as chaves que o backend já usa — é o que
 * mantém os componentes portáveis sem renomear nada no caminho.
 *
 * Datas vêm como string ISO (Carbon serializa assim sozinho) e valores
 * como número cru — quem formata é `@/lib/formato`, nunca o backend.
 */

/** Uma opção de bloco já saneada pelo `CatalogoBlocos` no PHP. */
export type OpcoesBloco = Record<string, boolean | string>;

export type Empresa = {
    fantasy_name: string;
    name: string;
    document: string;
    phone: string;
    email: string;
};

export type Cliente = {
    name: string;
    document: string;
    phone: string;
    email: string;
    address: { full_address: string } | null;
};

export type Invoice = {
    code: string;
    /** ISO-8601. `null` quando o registro real não tem a data. */
    issue_date: string | null;
    due_date: string | null;
    amount: number;
};

export type Subitem = {
    description: string;
    quantity: string;
    price: number;
    discount_label: string;
    amount: number;
    /** `false` risca a linha — item informativo, não cobrado. */
    chargeable: boolean;
};

export type ItemFatura = {
    id: number;
    title: string;
    subtitle: string;
    amount: number;
    consumerUnit: { code: string; alias: string | null };
    consumerUnitBill: { period_days: number } | null;
    invoiceSubitems: Subitem[];
};

export type MesHistorico = {
    reference_month: string;
    credits_used: number;
    credits_charged: number;
    credits_balance: number;
    total_saved: number;
    amount: number;
};

export type AnexoConcessionaria = {
    consumer_unit: { code: string };
    bill: { reference_month: string; bill_number: string };
};

export type DadosFatura = {
    company: Empresa;
    company_logo_url: string | null;

    customer: Cliente;
    invoice: Invoice;
    reference_month_label: string;

    credits_used: number;
    active_kwh_used: number;
    original_amount: number;
    total_saved: number;
    total_saved_percentage: string;

    bank: { code: string | null; name: string | null };
    bank_billet: { pix: string | null } | null;
    boleto_serial_number: string;
    barcode_line: string;

    invoice_items: ItemFatura[];
    /**
     * Chaveado pelo id do item. Vira string na travessia JSON — um
     * objeto JS não tem chave numérica.
     */
    histories_by_item: Record<string, MesHistorico[]>;
    bills_to_include: AnexoConcessionaria[];
};
