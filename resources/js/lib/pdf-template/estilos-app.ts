/**
 * O canvas do GrapesJS (e o iframe de pré-visualização) têm `document`
 * próprio — o CSS do app não existe lá dentro por padrão. Clonar as
 * folhas de estilo da página é o que faz o Tailwind valer no documento.
 *
 * Em dev o Vite injeta o CSS como <style>; em produção vira <link>.
 * Clonar os dois cobre os dois casos. O clone não acompanha HMR, então
 * em dev uma mudança no CSS pede refresh da página.
 */
export function espelharEstilosDoApp(destino: Document) {
    document
        .querySelectorAll('style, link[rel="stylesheet"]')
        .forEach((node) => destino.head.appendChild(node.cloneNode(true)));
}
