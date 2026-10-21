# Imagens do ArtUP

Esta pasta contém as imagens da interface. As cinco imagens de produtos foram geradas com a ferramenta integrada imagegen em 21/10/2026 e são ilustrações genéricas, não fotografias de trabalhos reais da ArtUP. O logotipo existente foi preservado.

## Arquivos e utilização

| Arquivo | Dimensões | Uso atual |
| --- | --- | --- |
| [grafica-colecao.png](grafica-colecao.png) | 1536 × 1024 px | Imagem principal da página inicial (`index.php`). |
| [adesivos.png](adesivos.png) | 1536 × 1024 px | Página inicial, catálogo e formulário de adesivos. |
| [banner.png](banner.png) | 1536 × 1024 px | Página inicial, catálogo e formulário de banners. |
| [placas.png](placas.png) | 1536 × 1024 px | Página inicial, catálogo e formulário de placas. |
| [papelaria.png](papelaria.png) | 1536 × 1024 px | Página inicial, catálogo e formulário de papelaria. |
| [logos/logo.png](logos/logo.png) | 1563 × 1563 px | Logotipo original do cabeçalho compartilhado. |

As imagens de produtos usam a paleta rosa `#a81759`, carvão `#171820` e tons claros. Os templates identificam os exemplos como ilustrativos. As imagens de produtos não são usadas atualmente nas telas de login ou cadastro.

## Manutenção

- Mantenha os nomes e caminhos ao substituir uma imagem para preservar as referências dos templates. Se mudar o caminho, atualize seus usos na página inicial, no catálogo e no formulário compartilhado.
- As cinco imagens de produtos têm proporção 3:2. Ao trocar suas dimensões, confira os atributos `width`/`height` e o enquadramento nas páginas.
- O logotipo em `logos/logo.png` pertence à identidade existente; os prompts abaixo se referem somente às cinco imagens genéricas.
- Artes enviadas pelos clientes ficam no PostgreSQL, não nesta pasta. Consulte o [guia das artes](../docs/funcionalidades.md#artes) para os formatos, limites e regras de acesso.

## Prompts utilizados

### hero

Use case: product-mockup. Asset type: landscape hero photograph for ArtUP, a Brazilian print shop website. Create a refined realistic studio photograph of a small collection of print products: stacked business cards, folded flyers, a sheet of round vinyl stickers and a small rigid printed sign, arranged on a warm pale grey table. Abstract geometric printed artwork in deep raspberry pink, charcoal and cream, matching an existing web palette #a81759 and #171820. Soft daylight, natural paper textures and subtle shadows, editorial product photography, clean composition with generous breathing room, landscape 3:2 framing. Generic illustrative products only; no readable text, no logos, no watermark, no people, no fake user interface.

### banner

Use case: product-mockup. Asset type: generic category card photo for a print shop website. Subject: one freestanding vertical printed vinyl banner hanging from slim horizontal rods, realistic banner material and a simple support, fully visible. Refined realistic studio product photography on a warm light grey background. Abstract geometric artwork in raspberry #a81759, charcoal #171820 and ivory. Soft natural lighting, delicate realistic shadows, high quality materials, centered uncluttered composition, all main products within central 75 percent so it can crop gracefully. Landscape 3:2. No people, no logos, no letters or readable text, no watermark, no UI.

### adesivos

Use case: product-mockup. Asset type: generic category card photo for a print shop website. Subject: a sheet of round die-cut vinyl stickers, several loose round stickers and one subtly peeled sticker showing its backing. Refined realistic studio product photography on a warm light grey background. Abstract geometric artwork in raspberry #a81759, charcoal #171820 and ivory. Soft natural lighting, delicate realistic shadows, high quality materials, centered uncluttered composition, all main products within central 75 percent so it can crop gracefully. Landscape 3:2. No people, no logos, no letters or readable text, no watermark, no UI.

### placas

Use case: product-mockup. Asset type: generic category card photo for a print shop website. Subject: two rigid rectangular PVC printed signs, one standing upright on a tabletop and one lying flat showing a thin solid edge. Refined realistic studio product photography on a warm light grey background. Abstract geometric artwork in raspberry #a81759, charcoal #171820 and ivory. Soft natural lighting, delicate realistic shadows, high quality materials, centered uncluttered composition, all main products within central 75 percent so it can crop gracefully. Landscape 3:2. No people, no logos, no letters or readable text, no watermark, no UI.

### papelaria

Use case: product-mockup. Asset type: generic category card photo for a print shop website. Subject: stacked A4 paper prints, a neat folded leaflet and business cards arranged together. Refined realistic studio product photography on a warm light grey background. Abstract geometric artwork in raspberry #a81759, charcoal #171820 and ivory. Soft natural lighting, delicate realistic shadows, high quality materials, centered uncluttered composition, all main products within central 75 percent so it can crop gracefully. Landscape 3:2. No people, no logos, no letters or readable text, no watermark, no UI.

