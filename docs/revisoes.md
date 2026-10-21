# Histórico de revisões do ArtUP

Registros de manutenção e revisão visual consolidados por assunto. Cada seção preserva o escopo, a data, as verificações e os limites da revisão original; suas contagens não representam testes executados nesta reorganização. As pendências atuais são acompanhadas no [product backlog](product-backlog.md), e os achados originais permanecem na [auditoria histórica](auditoria.md).

- [Revisão de manutenção — 21/10/2026](#manutencao)
- [Revisão visual — 21/10/2026](#revisao-visual)

<a id="manutencao"></a>

## Revisão de manutenção — 21/10/2026

Objetivo: facilitar leitura e operação do projeto sem modificar comportamento funcional. Foram revisados os 30 arquivos PHP da aplicação, o CSS, as migrações, parâmetros SQL e documentação existente. Os arquivos de configuração visual e as imagens preparados anteriormente foram preservados. O conteúdo secreto de `database/credenciais.php` não foi copiado nem exibido; o formato de configuração foi confirmado por seus consumidores.

### Simplificação aplicada

A remoção de um item do carrinho e a exclusão administrativa de um pedido repetiam a mesma consulta:

```sql
DELETE FROM produto WHERE id = :produto
    AND NOT EXISTS (SELECT 1 FROM pedido WHERE id_produto = produto.id)
    AND NOT EXISTS (SELECT 1 FROM carrinho WHERE id_produto = produto.id)
```

Ela agora tem uma única definição em `SQL_EXCLUIR_PRODUTO_SEM_VINCULOS`, em [include/pedidos.php](../include/pedidos.php). Os chamadores continuam sendo `excluir_produto_carrinho()` em [include/functions_core.php](../include/functions_core.php) e `excluir_pedido_admin()` em [include/pedidos.php](../include/pedidos.php).

Isso evita divergência futura na condição que preserva produtos compartilhados. Não foram introduzidos wrappers de transação, novas conexões ou alterações nas consultas. Permanecem nos mesmos pontos:

- Validação de IDs e autorização.
- `prepare()`/`execute()` e parâmetro `produto`.
- Ordem das consultas, bloqueios, commit e rollback.
- Retornos booleanos e tratamento de exceções/logs.
- Remoção da arte pelo relacionamento já definido no banco.

`functions_core.php` já carrega `pedidos.php` antes da utilização das funções. A extração não exige novo include nem muda os contratos das funções existentes. A comparação de SQL normalizou apenas CRLF/LF dos arquivos; texto e parâmetros permaneceram equivalentes.

### Documentação

- Criado [README na raiz](../README.md) com requisitos, configuração real, execução, rotas, exemplos, estrutura, validação e manutenção.
- Transformado [docs/README.md](README.md) em índice para evitar duas versões das instruções de instalação.
- Separadas dependências confirmadas de características observadas em ambientes anteriores. Não há `.env`, Composer ou mbstring exigido por chamadas de produção identificadas.
- Registrada a ausência de baseline completa do banco, suíte atual, versão mínima formalmente suportada e configuração de produção.
- Preservados os relatórios históricos, sem apresentar suas contagens de testes como execução desta revisão.

### Verificação

Antes da alteração, os **30 arquivos PHP** passaram em `php -l` com PHP CLI 8.5.9. Depois da alteração, a mesma verificação foi repetida.

Foi utilizado um roteiro temporário fora do repositório, contra cópias anteriores e arquivos atuais. As **150 observações comparadas foram idênticas**:

| Grupo | Observações | O que foi comparado |
| --- | --- | --- |
| Exclusão no carrinho e pelo administrador | 34 | 17 cenários por operação: retorno, sequência SQL/parâmetros, estado da transação e texto dos logs. Inclui IDs inválidos, ausência de produto, papel inválido, resultado sem linhas e falhas simuladas nas etapas de banco. Etapas que não existem num dos fluxos equivalem à execução normal daquele fluxo. |
| Validações de dimensão, preço, inteiro, e-mail e senha | 95 | 19 entradas por função: strings, números, arrays, limites, valores não finitos e entradas inválidas. |
| Formatação monetária | 5 | Nulo, zero, valor decimal, casas excedentes e valor negativo. |
| Período das estatísticas | 6 | Padrões com data fixa, intervalo válido, data impossível, inversão, intervalo longo e array. |
| URLs | 5 | Raiz, páginas internas, serviços e instalação em subpasta. |
| Upload sem arquivo ou com erro | 5 | Resultado e mensagem por referência; não houve upload HTTP real. |

As operações de banco usaram objetos simulados, sem abrir conexão ou modificar dados. Essa comparação verifica a preservação das interações PHP; **não comprova integridade, concorrência, FKs ou execução SQL em PostgreSQL real**. As conversões de `NAN` produziram avisos preexistentes no PHP 8.5.9, observados antes e depois; o comportamento não foi corrigido nesta entrega.

Não existe suíte de testes no checkout. O roteiro temporário não foi adicionado como substituto da suíte removida. Não foram realizados testes HTTP, validação visual ou testes de carga nesta revisão. A lógica monetária e as permissões precisam continuar sendo exercitadas em testes de integração quando essa infraestrutura for retomada.

### Decisões adiadas para preservar comportamento

| Candidato | Por que não foi alterado |
| --- | --- |
| Remover conexão do catálogo ou mover guardas antes dos includes | Mudaria a precedência entre falha de banco e redirecionamento/autorização. |
| Centralizar todas as transações em uma função genérica | Pode mudar captura de exceções, ordem dos bloqueios, rollback e logs. O benefício não justifica esse risco nesta entrega. |
| Reescrever o cálculo do carrinho em lote | Pode mudar arredondamento, locks e falha parcial. Exige testes com PostgreSQL e volume representativo. |
| Renomear `cproduto`, `consulta_user` e outras funções/parâmetros | Os nomes fazem parte das chamadas atuais e podem ter consumidores externos, inclusive argumentos nomeados. Não há inventário que prove ausência desses consumidores. |
| Remover `dimensao_valida` por não encontrar chamador interno | Ausência de chamada no checkout não prova que a função pública está morta. |
| Aplicar tipos estritos, novos casts ou trocar `isset` por `empty` | Altera coerção, aceitação de valores e erros observáveis. |
| Arredondar no formatador monetário ou limitar dimensões de outra forma | Mudaria saídas conhecidas e a faixa de entradas aceitas. |
| Remover senhas legadas, endurecer senhas, cookies ou CSRF de autenticação | São mudanças funcionais de segurança; permanecem na auditoria/backlog, sem correção silenciosa. |
| Alterar fechamento PHP, saída dos includes ou mensagens | Pode afetar bytes enviados, headers e consumidores das mensagens. Não foi feita limpeza estética em massa. |
| Criar baseline SQL ou reexecutar seeds | Não há especificação completa confirmada; inventar o schema ou repetir INSERTs poderia mudar dados e constraints. |
| Integrar imagens ou completar a estilização | Mudaria a apresentação observável; continua como trabalho visual separado, já solicitado anteriormente. |

A principal limitação de confiança é a falta de testes de integração versionados. Esta revisão reduz uma duplicação concreta e documenta a operação existente; não declara correção dos achados de segurança nem aprovação para produção.

<a id="revisao-visual"></a>

## Revisão visual — 21/10/2026

Foram comparadas as alterações desde `6250dd8` até `2e5c950`, incluindo a integração de CSS global, imagens, página inicial, autenticação e telas administrativas. Os commits de manutenção e documentação presentes nesse intervalo foram preservados.

### Achados e ajustes

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

### Refinamento com Superdesign

O projeto remoto existente foi reutilizado, com autorização específica para enviar sete arquivos de contexto visual. O logotipo e as cinco imagens genéricas foram reaproveitados. Foram criados componentes de cabeçalho/rodapé e uma reprodução inicial, seguida de refinamento com Gemini 3.1 Pro. Uma correção direta ajustou o enquadramento da marca e a estrutura semântica do rascunho.

- [Canvas Artup](https://superdesign.dev/teams/9cc38fdf-6824-4983-8c03-5352ff4eb4d8/projects/c2298b83-777c-4eaa-ae8d-b2c34875ffa1).
- [Prévia da página inicial](https://p.superdesign.dev/draft/e16c4e40-2e05-4156-aa3a-49dfdbdc7232).
- Rascunho `e16c4e40-2e05-4156-aa3a-49dfdbdc7232`, versão 3.

No PHP, a página inicial ganhou hierarquia mais direta: apresentação, serviços e depois o passo a passo. O catálogo tem ação explícita nos cartões, alturas coerentes e imagens com dimensões declaradas e carregamento adiado. Os quatro serviços usam uma apresentação lateral no desktop e formulário agrupado em especificações e arte opcional. Os componentes empilham em telas pequenas.

As fotos continuam identificadas como ilustrativas. Não foram acrescentados preços fictícios, depoimentos, contatos, pagamentos ou promessas de entrega. A aplicação continua com PHP/HTML/CSS, sem Tailwind/CDN ou JavaScript de design nos templates de produção. O HTML remoto do Superdesign é uma referência visual; não foi copiado como dependência de execução.

### Verificação

As verificações usaram servidor PHP local temporário, navegador Chrome via Playwright e objetos PDO simulados. Nenhuma conexão com o banco da aplicação foi aberta. Fixtures com um pedido, nome longo de arte, valores e dados vazios exercitaram a renderização. As ferramentas ficaram fora do repositório, sem recriar a suíte removida anteriormente.

- **52 verificações de página aprovadas:** 13 páginas em 320, 390, 768 e 1440 px. Todas responderam com um `main`, sem erros PHP detectados no HTML, imagens ausentes, controles sem rótulo ou transbordamento horizontal da página. Todas passaram a ter o atalho ao conteúdo. As tabelas conservam rolagem própria.
- **32 verificações de contratos/acessibilidade aprovadas:** comparação de campos, métodos, enctype, ações, opções e limites de dez formulários antes/depois; reprodução da falha original do cadastro e verificação da correção; foco do atalho ao conteúdo; recusa de CSRF inválido em cinco fluxos; visitante/cliente em administração; filtro inválido; logout por GET; prévia e botão de inclusão com preços de fixture; rolagem de tabela por teclado; cinco estados vazios; movimento reduzido e impressão.
- **30 arquivos PHP aprovados no lint final.**
- Capturas de tela de página inicial, serviço, edição de produtos e estatísticas foram produzidas em desktop e celular; as capturas da página inicial, serviço e estatísticas foram inspecionadas visualmente.

A comparação de formulários ignora o valor aleatório dos tokens, mas mantém sua presença. O cadastro foi verificado separadamente porque a versão anterior não renderizava os campos. Os valores da prévia usados no roteiro são simulados: isso verifica a apresentação e a ação, não a fórmula SQL.

Essas verificações não certificam WCAG nem substituem testes de integração reais de gravação, concorrência, upload HTTP, autorização de arquivos ou cálculo PostgreSQL. Não foram alterados os módulos de negócio, consultas, migrações ou dados persistentes. Os achados históricos de segurança permanecem no backlog.
