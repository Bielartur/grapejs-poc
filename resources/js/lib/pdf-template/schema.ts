/**
 * SCHEMA FIXO DE DADOS
 *
 * Este é o contrato que, no sistema real, viria do backend PHP.
 * O usuário do editor NUNCA digita o dado real — ele apenas escolhe
 * QUAL campo deste schema um elemento visual representa.
 *
 * - `sample`  -> valor exibido dentro do editor (placeholder visual)
 * - `real`    -> valor de um registro real, usado só para simular
 *                a substituição que o PHP faria na geração do PDF
 */

export type CampoSchema = {
    key: string;
    label: string;
    sample: string;
    real: string;
};

export const CAMPOS: CampoSchema[] = [
    {
        key: 'nome_cliente',
        label: 'Nome do cliente',
        sample: 'NOME DO CLIENTE',
        real: 'Maria Aparecida da Silva',
    },
    {
        key: 'documento_cliente',
        label: 'CPF/CNPJ do cliente',
        sample: '000.000.000-00',
        real: '472.918.330-15',
    },
    {
        key: 'curso_nome',
        label: 'Nome do curso',
        sample: 'NOME DO CURSO',
        real: 'Formação em Segurança da Informação',
    },
    {
        key: 'carga_horaria',
        label: 'Carga horária',
        sample: '00 horas',
        real: '40 horas',
    },
    {
        key: 'numero_documento',
        label: 'Número do documento',
        sample: 'DOC-0000/0000',
        real: 'DOC-2026/000123',
    },
    {
        key: 'data_emissao',
        label: 'Data de emissão',
        sample: '00/00/0000',
        real: '07/08/2026',
    },
    {
        key: 'valor_total',
        label: 'Valor total',
        sample: 'R$ 0,00',
        real: 'R$ 1.450,00',
    },
    {
        key: 'nome_empresa',
        label: 'Nome da empresa',
        sample: 'NOME DA EMPRESA',
        real: 'Leet Tecnologia LTDA',
    },
    {
        key: 'responsavel_nome',
        label: 'Nome do responsável',
        sample: 'NOME DO RESPONSÁVEL',
        real: 'Carlos Eduardo Ramos',
    },
];

export const CAMPO_PADRAO = CAMPOS[0].key;

const porChave = new Map(CAMPOS.map((campo) => [campo.key, campo]));

export function campoPorChave(key: string): CampoSchema | undefined {
    return porChave.get(key);
}

/** Opções no formato que o Trait Manager do GrapesJS espera. */
export const OPCOES_TRAIT_CAMPO = CAMPOS.map((campo) => ({
    id: campo.key,
    value: campo.key,
    name: campo.label,
    label: campo.label,
}));

/**
 * Simula o que o PHP faria: varrer o HTML procurando [data-bind]
 * e trocar o conteúdo pelo dado real do registro.
 * No backend isso seria DOMDocument + DOMXPath.
 */
export function aplicarDados(html: string): string {
    const doc = new DOMParser().parseFromString(html, 'text/html');

    doc.querySelectorAll('[data-bind]').forEach((el) => {
        const campo = campoPorChave(el.getAttribute('data-bind') ?? '');
        if (campo) {
            el.textContent = campo.real;
        }
    });

    return doc.body.innerHTML;
}
