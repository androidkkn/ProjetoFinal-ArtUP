# Revisão da integração visual — 21/10/2026

Foram comparadas as alterações desde `6250dd8` até `2e5c950`, incluindo a integração de CSS global, imagens, página inicial, autenticação e telas administrativas. Os commits de manutenção e documentação presentes nesse intervalo foram preservados.

## Achados e ajustes

| Prioridade | Evidência | Ajuste aplicado |
| --- | --- | --- |
| Alta | `login/cadastrar.php` passou a chamar `url_app()` no `<head>` sem carregar `include/navegacao.php`. A resposta ficava incompleta por erro fatal antes de renderizar o formulário. | Incluído o mesmo helper de navegação usado pelo login, antes de sua primeira chamada. O cadastro voltou a renderizar os quatro campos. |
| Média | O campo nativo de upload nos quatro serviços excedia a tela de 320 px; a largura da página chegava a 327 px. | Dimensionamento explícito do input de arquivo e proteção de largura nos campos e grids. Nenhuma alteração nos formatos ou limites aceitos. |
| Média | Faltava um atalho de teclado para o conteúdo nas páginas novas; os menus dos clientes não indicavam a localização atual. | Link compartilhado para `#conteudo`, destino focalizável e `aria-current` apropriado nas páginas e seções. |
| Baixa | Cabeçalho, rodapé e regras globais estavam duplicados no CSS das estatísticas. | O painel passou a carregar os estilos compartilhados e o mesmo rodapé; seu CSS mantém os componentes específicos. Filtros e impressão foram preservados. |
| Baixa | O logo era mostrado com `object-fit: contain` em uma caixa muito baixa, tornando a marca pequena devido às margens internas da imagem. | Reenquadramento por CSS, preservando o PNG original. |
| Baixa | Estados de hover tinham transições e deslocamento sem respeitar preferência de movimento reduzido; botões longos podiam pressionar layouts estreitos. | Tratamento de `prefers-reduced-motion`, quebra de texto e tamanhos mínimos para controles. |
| Baixa | Status desconhecidos recebiam a aparência visual de pendente nas listagens. | Fallback visual neutro, mantendo o texto escapado e as regras de status do servidor. |

O restante da integração foi mantido: classes de botões, mensagens, telas vazias, tabelas com rolagem e imagens em `/img`. Alguns estilos inline simples foram substituídos por classes compartilhadas onde já havia alteração no template.

## Refinamento com Superdesign

O projeto remoto existente foi reutilizado, com autorização específica para enviar sete arquivos de contexto visual. O logotipo e as cinco imagens genéricas foram reaproveitados. Foram criados componentes de cabeçalho/rodapé e uma reprodução inicial, seguida de refinamento com Gemini 3.1 Pro. Uma correção direta ajustou o enquadramento da marca e a estrutura semântica do rascunho.

- [Canvas Artup](https://superdesign.dev/teams/9cc38fdf-6824-4983-8c03-5352ff4eb4d8/projects/c2298b83-777c-4eaa-ae8d-b2c34875ffa1).
- [Prévia da página inicial](https://p.superdesign.dev/draft/e16c4e40-2e05-4156-aa3a-49dfdbdc7232).
- Rascunho `e16c4e40-2e05-4156-aa3a-49dfdbdc7232`, versão 3.

No PHP, a página inicial ganhou hierarquia mais direta: apresentação, serviços e depois o passo a passo. O catálogo tem ação explícita nos cartões, alturas coerentes e imagens com dimensões declaradas e carregamento adiado. Os quatro serviços usam uma apresentação lateral no desktop e formulário agrupado em especificações e arte opcional. Os componentes empilham em telas pequenas.

As fotos continuam identificadas como ilustrativas. Não foram acrescentados preços fictícios, depoimentos, contatos, pagamentos ou promessas de entrega. A aplicação continua com PHP/HTML/CSS, sem Tailwind/CDN ou JavaScript de design nos templates de produção. O HTML remoto do Superdesign é uma referência visual; não foi copiado como dependência de execução.

## Verificação

As verificações usaram servidor PHP local temporário, navegador Chrome via Playwright e objetos PDO simulados. Nenhuma conexão com o banco da aplicação foi aberta. Fixtures com um pedido, nome longo de arte, valores e dados vazios exercitaram a renderização. As ferramentas ficaram fora do repositório, sem recriar a suíte removida anteriormente.

- **52 verificações de página aprovadas:** 13 páginas em 320, 390, 768 e 1440 px. Todas responderam com um `main`, sem erros PHP detectados no HTML, imagens ausentes, controles sem rótulo ou transbordamento horizontal da página. Todas passaram a ter o atalho ao conteúdo. As tabelas conservam rolagem própria.
- **32 verificações de contratos/acessibilidade aprovadas:** comparação de campos, métodos, enctype, ações, opções e limites de dez formulários antes/depois; reprodução da falha original do cadastro e verificação da correção; foco do atalho ao conteúdo; recusa de CSRF inválido em cinco fluxos; visitante/cliente em administração; filtro inválido; logout por GET; prévia e botão de inclusão com preços de fixture; rolagem de tabela por teclado; cinco estados vazios; movimento reduzido e impressão.
- **30 arquivos PHP aprovados no lint final.**
- Capturas de tela de página inicial, serviço, edição de produtos e estatísticas foram produzidas em desktop e celular; as capturas da página inicial, serviço e estatísticas foram inspecionadas visualmente.

A comparação de formulários ignora o valor aleatório dos tokens, mas mantém sua presença. O cadastro foi verificado separadamente porque a versão anterior não renderizava os campos. Os valores da prévia usados no roteiro são simulados: isso verifica a apresentação e a ação, não a fórmula SQL.

Essas verificações não certificam WCAG nem substituem testes de integração reais de gravação, concorrência, upload HTTP, autorização de arquivos ou cálculo PostgreSQL. Não foram alterados os módulos de negócio, consultas, migrações ou dados persistentes. Os achados históricos de segurança permanecem no backlog.
