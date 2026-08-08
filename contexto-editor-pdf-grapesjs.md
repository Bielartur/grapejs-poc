# Contexto: Editor Visual de Templates de PDF

## Objetivo do projeto

Construir, dentro do sistema da empresa, uma funcionalidade que permita ao usuário
**montar visualmente o layout de um documento PDF** (ex: certificado, relatório, documento
com dados fixos) e depois gerar o PDF final substituindo os dados de exemplo pelos dados
reais de cada registro.

O usuário final da funcionalidade **não é técnico** — ele deve conseguir editar cor,
posicionamento, bordas arredondadas e imagens visualmente, sem escrever HTML/CSS.

## Stack atual

- **Frontend**: Vue (Composition API)
- **Backend**: PHP
- **Geração de PDF hoje**: biblioteca PHP que converte HTML → PDF (tipo Dompdf/mPDF —
  a decidir/confirmar qual exatamente)

## Requisitos funcionais

1. Editor visual (drag-and-drop) onde o usuário monta o layout do documento.
2. Deve permitir customizar: cor de fundo/texto, posicionamento dos elementos,
   bordas arredondadas, inserção de imagens (ex: logo da empresa).
3. Preview do resultado antes de salvar.
4. **Dados são fixos e obrigatórios** — o conteúdo real (nome do cliente, data, valor etc.)
   não é digitado livremente pelo usuário no editor. O usuário **vincula** um elemento
   visual a um campo de dado pré-definido (schema fixo, definido pelo backend), e o editor
   mostra um valor de exemplo/placeholder no lugar do dado real.
5. Ao salvar, o sistema armazena o **template** (HTML/CSS com marcações de binding).
   Ao gerar o documento de um registro específico, o backend substitui os campos vinculados
   pelos dados reais e gera o PDF.
6. A interface do editor deve ser customizável visualmente (cores, layout dos painéis)
   para casar com a identidade visual do produto da empresa.

## Decisão arquitetural já tomada (e por quê)

Avaliamos três caminhos possíveis:

| Ferramenta | Por que foi descartada / escolhida |
|---|---|
| **Craft.js** | Framework React-only, sem port pra Vue. Descartado. |
| **Polotno / Konva (canvas)** | Também dependente de React na camada de UI pronta (Polotno). O motor Konva puro tem wrapper oficial pra Vue (`vue-konva`), mas texto em canvas não reflui, não quebra linha automaticamente e não fica selecionável no PDF — ruim para conteúdo com texto/dados variáveis. Descartado por causa disso. |
| **GrapesJS** ✅ | Framework open source (MIT), agnóstico de framework (funciona bem com Vue via composable). Gera HTML/CSS real como saída — compatível direto com o pipeline PHP HTML→PDF já existente. Suporta texto que reflui naturalmente. **Escolhido.** |

**Conclusão**: o caminho é **GrapesJS + Vue**, com a saída HTML/CSS alimentando a lib PHP
de conversão para PDF que já existe no sistema.

## Descobertas técnicas relevantes já levantadas

- O GrapesJS core é instalado via `npm install grapesjs` — 100% MIT, sem custo. Existe
  também um "Studio SDK" pago da mesma equipe, com recursos extras (não é necessário
  para o escopo inicial).
- Não existe wrapper oficial para Vue (como existe para React), mas a API é agnóstica
  de framework — a integração recomendada é um **composable Vue** (`useGrapesJS`) que
  inicializa o editor num container via `onMounted` e destrói via `onUnmounted`.
- Para o binding de dados fixos, o GrapesJS já tem suporte nativo a **Data Binding /
  Data Collections** no core, e existe um plugin open source dedicado,
  `@silexlabs/grapesjs-data-source`, que implementa vínculo de componentes a dados,
  loops e condições visuais — alternativa a construir um componente customizado do zero.
- Uma abordagem mais simples e sob controle total: criar um **tipo de componente
  customizado** (`editor.Components.addType(...)`) chamado algo como "Campo Dinâmico",
  com um **Trait** (`select`) que lista os campos disponíveis do schema fixo. O componente
  grava um atributo `data-bind="nome_do_campo"` no HTML gerado.
- No backend PHP, a substituição do dado real pode ser feita varrendo o HTML com
  `DOMDocument`/`DOMXPath` procurando `[data-bind]`, mais robusto que `str_replace` manual.
- A interface do GrapesJS é customizável em 3 níveis:
  1. **Cores/tema**: sobrescrever classes CSS padrão (`.gjs-one-bg`, `.gjs-two-color`,
     `.gjs-three-bg`, `.gjs-four-color`) depois de importar o CSS do GrapesJS.
  2. **Layout dos painéis**: o GrapesJS não impõe estrutura de UI — você define suas
     próprias `<div>`s/containers no seu componente Vue e aponta os managers do editor
     pra elas via config (`appendTo`).
  3. **Controles customizados**: é possível substituir controles individuais do Style
     Manager por componentes Vue próprios via API (`styleManager.addType(...)`).

## O que eu quero validar agora (escopo do teste no Claude Code)

Quero montar um **protótipo mínimo funcional em Vue** só para confirmar, na prática, que
o GrapesJS resolve o problema antes de investir tempo integrando no sistema real. O
protótipo deve validar:

1. **Setup básico**: GrapesJS rodando dentro de um componente Vue 3 (Composition API),
   usando o padrão de composable (`useGrapesJS`).
2. **Edição visual básica**: conseguir arrastar um bloco (retângulo/card), mudar cor de
   fundo, aplicar `border-radius` e mover a posição via Style Manager padrão.
3. **Upload/inserção de imagem**: confirmar que o Asset Manager padrão permite adicionar
   uma imagem (ex: logo) ao layout.
4. **Componente de campo dinâmico**: implementar o tipo de componente customizado
   (`campo-dinamico`) descrito acima, com um Trait `select` listando 2-3 campos de
   exemplo (`nome_cliente`, `data_emissao`), e confirmar que ele grava
   `data-bind="..."` corretamente no HTML de saída.
5. **Exportar o HTML final**: confirmar que dá pra pegar o HTML/CSS gerado pelo editor
   (via `editor.getHtml()` / `editor.getCss()`) — essa é a saída que vai alimentar o
   pipeline PHP de geração de PDF depois.
6. **(Opcional, se der tempo) Customização visual básica**: sobrescrever as classes CSS
   de tema (`.gjs-one-bg` etc.) pra confirmar que a re-tematização funciona como esperado.

## Critério de sucesso

O protótipo é considerado bem-sucedido se, ao final:
- Eu conseguir editar visualmente um layout simples (cor, posição, borda, imagem).
- O componente de campo dinâmico aparecer como algo diferenciado no canvas (não como
  texto editável comum) e gerar o atributo `data-bind` corretamente.
- O `editor.getHtml()` retornar um HTML limpo, com os `data-bind` presentes, pronto para
  eu simular manualmente a substituição de dados (não precisa integrar com PHP real
  nessa etapa de validação).

## Fora do escopo desta validação

- Integração real com o backend PHP (isso vem depois, se o protótipo validar).
- Persistência do template em banco de dados.
- O plugin `@silexlabs/grapesjs-data-source` (fica como alternativa a avaliar depois,
  não é necessário pra confirmar se o GrapesJS resolve o problema).
- Customização profunda de UI (Nível 3 — controles customizados via Vuetify/API).
