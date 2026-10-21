# Product backlog — ArtUP

Atualizado em 21/10/2026. Objetivo do produto: permitir que clientes solicitem produtos gráficos com
preço e arte definidos e que a gráfica acompanhe produção, cancelamentos e indicadores de pedidos.

Este backlog combina o estado do repositório, os achados da [auditoria](auditoria.md) e as evoluções
propostas para o produto. Não representa aprovação de segurança para produção nem compromisso de prazo.
As estimativas são relativas: **P** pequena, **M** média, **G** grande. Responsáveis e datas ficam a definir.

## Convenções

- **P0:** necessário antes de publicar ou ampliar o acesso externo.
- **P1:** próxima rodada de integridade, operação e desempenho.
- **P2:** melhoria de manutenção e experiência após os bloqueios anteriores.
- **P3:** oportunidade que precisa de descoberta/validação antes de implementação.
- **Concluído:** comportamento implementado no código; pendências de segurança relacionadas têm itens próprios.
- **Pendente:** mudança ainda não implementada. **A validar:** escopo de produto ainda não definido.

## Incrementos já implementados

| ID | Entrega | Critério atendido | Estado |
| --- | --- | --- | --- |
| PB-001 | Cadastro e login de clientes | Conta pública nasce sem permissão administrativa e autentica por sessão. Melhorias de segurança abaixo continuam pendentes. | Concluído |
| PB-002 | Catálogo dos quatro serviços | Adesivos, banners, placas e papelaria possuem formulários; comprimento/largura são apresentados em cm. | Concluído |
| PB-003 | Prévia e cálculo de preços | Área em cm é convertida para m²; papelaria usa A4 como referência; valor unitário é arredondado antes do total. | Concluído |
| PB-004 | Carrinho e confirmação | Itens podem ser removidos; confirmação é atômica e preserva os preços aceitos. | Concluído |
| PB-005 | Envio e acesso às artes | Arquivos permitidos são validados, vinculados ao produto e baixados por proprietário ou administrador. | Concluído |
| PB-006 | Administração de produtos | Administrador edita tarifas, tipos e itens com recálculo explícito e transacional. | Concluído |
| PB-007 | Gestão e exclusão de pedidos | Administrador altera status e exclui pedidos após confirmação, preservando produtos ainda compartilhados. | Concluído |
| PB-008 | Solicitação de cancelamento | Cliente solicita o próprio pedido; administrador aprova/recusa e a decisão fica registrada. | Concluído |
| PB-009 | Painel de estatísticas | Administrador filtra por datas e consulta indicadores, status, meses e categorias; cancelados e valores ausentes têm regras explícitas. | Concluído nesta entrega |
| PB-010 | Navegação e logout | Menus refletem o perfil e a página administrativa atual; logout exige POST e token. | Concluído |
| PB-037 | Consulta administrativa de clientes | Todos os usuários, inclusive sem pedidos, com busca, paginação, indicadores e histórico individual; dados de senha não são consultados. | Concluído |

## P0 — Antes de publicar

| ID / tamanho | História e resultado esperado | Critérios de aceitação | Dependências / origem | Estado |
| --- | --- | --- | --- | --- |
| PB-011 / M | Como cliente, quero que meu e-mail identifique uma única conta. | Resolver duplicatas sem apagar pedidos automaticamente; índice único de e-mail normalizado; cadastros concorrentes retornam erro compreensível. | S04; precede recuperação de conta. | Pendente |
| PB-012 / G | Como cliente, quero recuperar minha conta sem manter senha em texto puro. | Recuperação por token de uso único com expiração e hash armazenado; tratar contas legadas; remover comparação direta; rehash após login quando necessário. | PB-011 e PB-013; S01. | Pendente |
| PB-013 / M | Como cliente, quero uma política de senhas resistente a adivinhação. | Definir comprimento mínimo adequado à ausência de MFA e teto operacional; aceitar frases/Unicode; não truncar; interface e servidor usam a mesma regra. | S03. | Pendente |
| PB-014 / M | Como administrador, quero reduzir tentativas automatizadas de autenticação. | Limites por IP e identidade com contador atômico/expiração; resposta 429; registrar eventos sem senhas; não bloquear contas permanentemente. | S02; coordenar com infraestrutura. | Pendente |
| PB-015 / G | Como administrador, quero que a revogação de acesso valha para sessões existentes. | Validar papel atual ou versão revogável; expiração ociosa/absoluta; cookies HttpOnly/SameSite e Secure em HTTPS; manter regeneração no login. | S06/S07. | Pendente |
| PB-016 / G | Como responsável pela implantação, quero expor somente os arquivos públicos. | Definir document root público e rotas; bloquear Git/configurações; HTTPS; erros fora do navegador; cabeçalhos de segurança compatíveis com recursos reais; verificar o SAPI de produção. | S08/S09/S10; exposição pública ainda precisa ser verificada na hospedagem. | Pendente |

## P1 — Integridade e operação

| ID / tamanho | História e resultado esperado | Critérios de aceitação | Dependências / origem | Estado |
| --- | --- | --- | --- | --- |
| PB-017 / P | Como usuário, quero proteção contra submissões externas no login/cadastro. | Token de sessão nos dois formulários; POST ausente/inválido recusado antes de consulta, hash ou gravação; mensagem de recarga. | S05. | Pendente |
| PB-018 / G | Como desenvolvedor, quero instalar o banco a partir do repositório. | Baseline cria todas as tabelas/constraints em banco vazio; migrações versionadas; aplicação e instruções usam o mesmo schema; não reutilizar fixtures como instalador. | Q04; dump SQL completo, dados de demonstração e tutorial disponíveis. Falta validar a restauração com `psql` em ambiente limpo. | Em progresso |
| PB-019 / G | Como administrador, quero impedir quantidades, medidas e estados inconsistentes. | Faixa/precisão de dimensões definida com o negócio; armazenamento decimal compatível com cm na interface; checks de quantidade/status; categoria/tipo coerentes; migração preserva dados e preços aceitos. | PB-018; B01/B03. | Pendente |
| PB-020 / M | Como administrador, quero consultas rápidas com mais pedidos. | Índices por data/cliente/produto alinhados às consultas, inclusive estatísticas; analisar planos em volume representativo; evitar índices duplicados e medir impacto nas escritas. | PB-018; P03. | Pendente |
| PB-021 / M | Como cliente, quero calcular o carrinho sem consultas repetidas por item. | Substituir 1+2N execuções por processamento em lote; preservar fórmulas, arredondamento, locks e rollback integral quando um item falhar. | PB-019; P01. | Pendente |
| PB-022 / M | Como usuário, quero navegar por históricos grandes sem travar a página. | Paginação com limites, ordenação estável e filtros; preservar autorização/posse entre páginas; definir limite de tamanho do carrinho. | PB-020; P02. | Pendente |
| PB-023 / M | Como responsável pela gráfica, quero controlar armazenamento das artes. | Limpeza agendada de rascunhos em lotes; quota por cliente validada sob concorrência; preservar artes vinculadas; mensagens claras ao atingir o limite. | P04. | Pendente |

## P2 — Manutenção e experiência

| ID / tamanho | História e resultado esperado | Critérios de aceitação | Dependências / origem | Estado |
| --- | --- | --- | --- | --- |
| PB-024 / G | Como desenvolvedor, quero regras testáveis sem depender de superglobais. | Extrair serviços/repositórios com dependências e identidade explícitas; tipagem gradual; manter os fluxos existentes e a fronteira HTTP. PSR-7/15 só se a arquitetura escolhida as adotar. | Q01/Q03; incremental após integridade. | Pendente |
| PB-025 / P | Como desenvolvedor, quero código com formatação consistente. | Configuração de estilo/editor; LF e fechamento PHP apropriado; mudanças de estilo separadas de regras de negócio. | Q02. | Pendente |
| PB-026 / P | Como usuário, quero formatação monetária com contrato claro. | Definir entrada em centavos ou decimal já arredondado; tratar casas extras sem truncamento silencioso; preservar exatidão de valores grandes. | B02. | Pendente |
| PB-027 / M | Como cliente, quero páginas com imagens e textos definitivos. | Imagens existentes para loja, novidades e catálogo; rodapé finalizado; textos provisórios removidos; alternativas acessíveis. A página de estatísticas já foi concluída em PB-009. | B04 resolvido. | Concluído nesta entrega |
| PB-028 / P | Como usuário, quero que páginas e downloads evitem bloqueios desnecessários. | Guarda antes da conexão em páginas privadas; remover conexão inútil do catálogo; liberar sessão antes da leitura de arte em rotas sem alterações de sessão. | P05. | Pendente |
| PB-029 / M | Como administrador, quero exportar os indicadores filtrados. | CSV com o mesmo período/regras de valores; somente administrador; proteção contra fórmulas de planilha; sem expor dados pessoais desnecessários. | PB-009; proposta de evolução. | Pendente |
| PB-030 / G | Como administrador, quero acompanhar alterações e tempos de produção. | Definir eventos, datas de transição e retenção; medir tempos com timestamps próprios; definir como exclusões aparecem no histórico sem mudar silenciosamente o comportamento atual. | PB-007/PB-008; evolução da rastreabilidade. | Pendente |
| PB-031 / M | Como usuário, quero navegação acessível e adaptação a telas pequenas em todo o site. | Foco visível, rótulos/erros acessíveis e layout responsivo nas páginas restantes; usar o painel como referência, sem depender apenas de cor. | PB-027; painel já possui apresentação responsiva. | Concluído nesta entrega |

## P3 — Descoberta, sem implementação nesta entrega

| ID | Oportunidade | O que precisa ser decidido | Estado |
| --- | --- | --- | --- |
| PB-032 | Kanban de produção | Etapas reais, transições permitidas, papéis e sincronização com o status do pedido. | A validar |
| PB-033 | Novas categorias do catálogo | Materiais, tipos, unidades, fórmula de preço, limites e critérios para disponibilização. | A validar |
| PB-034 | Pagamentos e indicadores de recebimento | Meios de pagamento, conciliação, estornos, segurança e distinção entre pedido e recebimento. Até lá, os valores do painel são valores de pedidos. | A validar |
| PB-035 | Notificações de pedidos/cancelamentos | Canal, consentimento/preferências, gatilhos e tratamento de falhas; nenhuma mensagem externa é enviada hoje. | A validar |
| PB-036 | Cache adicional | Medir gargalos antes de adotar APCu/Redis; definir invalidação e comportamento em falhas. OPcache deve ser verificado na implantação. | A validar |

## Sequência proposta e conclusão de um item

Resolver P0 antes de ampliar o acesso. PB-011 precede recuperação automatizada; PB-018 precede novas
migrações estruturais; PB-019 estabiliza as regras usadas no lote de preços; PB-020 apoia paginação e
estatísticas em maior volume. P2 e P3 não devem desviar a atenção desses bloqueios.

Um item só passa a concluído quando seus critérios estiverem implementados, as permissões e entradas
relevantes forem verificadas, a documentação estiver coerente e eventuais migrações tiverem plano de
execução/reversão compatível com os dados. Credenciais e dados pessoais não entram nos documentos.

A suíte automatizada anterior foi removida a pedido e não foi recriada nesta entrega. Sua remoção não
resolve os achados de segurança. As validações do painel são descritas em [estatísticas](funcionalidades.md#estatisticas).
