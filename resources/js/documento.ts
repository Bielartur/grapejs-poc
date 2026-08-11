import { createApp, nextTick } from 'vue';
import DocumentoFatura from '@/components/fatura/documento/DocumentoFatura.vue';
import type { BlocoLayout, Tema } from '@/composables/useLayoutFatura';
import type { DadosFatura } from '@/types/fatura';

/**
 * O DOCUMENTO SOZINHO, PARA O BROWSERSHOT
 *
 * Entry point separado do `app.ts`: a página que o Chromium abre monta
 * só a fatura — sem Inertia, sem editor, sem tema claro/escuro. São os
 * MESMOS componentes que o preview usa, e é isso que faz o preview não
 * ser uma aproximação do PDF.
 *
 * Os dados chegam embutidos num <script type="application/json">, não
 * por requisição: o Chromium não deve depender de sessão nem de uma
 * segunda ida ao servidor pra montar o documento.
 */

declare global {
    interface Window {
        /** Lido pelo `waitForFunction` do Browsershot. */
        documentoPronto?: boolean;
    }
}

type Carga = {
    paginas: BlocoLayout[][];
    tema: Tema;
    dados: DadosFatura;
};

const alvo = document.getElementById('documento');
const json = document.getElementById('dados-documento')?.textContent;

if (!alvo || !json) {
    throw new Error('Casca do documento incompleta.');
}

createApp(DocumentoFatura, JSON.parse(json) as Carga).mount(alvo);

/**
 * O sinal de "pode imprimir".
 *
 * Deliberadamente NÃO usamos `waitUntilNetworkIdle`: rede parada não
 * quer dizer layout estável. Fonte que chega depois muda a altura do
 * texto, e altura muda onde a página quebra — um PDF gerado cedo demais
 * sai com a paginação errada, e isso não aparece como erro.
 */
async function sinalizarPronto(): Promise<void> {
    await nextTick();
    await document.fonts.ready;

    await Promise.all(
        [...document.images]
            .filter((img) => !img.complete)
            .map(
                (img) =>
                    new Promise((resolve) => {
                        img.onload = resolve;
                        img.onerror = resolve;
                    }),
            ),
    );

    window.documentoPronto = true;
}

void sinalizarPronto();
