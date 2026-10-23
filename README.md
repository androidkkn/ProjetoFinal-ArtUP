# ArtUP

Aplicação web para uma gráfica: clientes configuram produtos, calculam preços, enviam artes e confirmam pedidos; administradores gerenciam preços, produção, cancelamentos e estatísticas.

O projeto usa **PHP procedural, HTML/CSS e PostgreSQL via PDO**, com páginas renderizadas no servidor. Não há framework, autoload do Composer, build de frontend ou integração de pagamento no código atual.

## Funcionalidades e fluxo

1. Cadastre uma conta em `login/cadastrar.php` e entre em `login/login.php`.
2. Escolha adesivos, papelaria, placas ou banners no catálogo.
3. Informe quantidade, medidas em **centímetros** ou formato do papel. A arte é opcional.
4. Clique em **Calcular prévia**, confira o preço e use **Adicionar ao carrinho**. Se os dados ou o preço mudarem, uma nova conferência é exigida.
5. No carrinho, revise os itens e clique em **Confirmar pedidos**. Cada item vira um pedido, mantendo o preço aceito.
6. Acompanhe a produção em **Meus pedidos** e, quando permitido, solicite cancelamento.

Administradores também podem editar tarifas e itens, mudar status, aprovar/recusar cancelamentos, excluir pedidos e consultar o painel por período. Os status são Pendente (`pendente`), Em produção (`em producao`), Retirada (`retirada`), Entregue (`concluido`) e Cancelado (`cancelado`). A chave `concluido` foi preservada por compatibilidade com os pedidos existentes.

Em **Administração → Clientes**, o administrador consulta todos os usuários, busca por nome/e-mail/ID, altera o perfil entre Cliente e Administrador e abre o resumo de valores e o histórico paginado de cada conta. O seletor aparece em todas as contas, inclusive na própria; pelo menos uma conta administrativa deve ser mantida. Veja [clientes](docs/funcionalidades.md#clientes).

O painel mostra valores de pedidos, **não pagamentos recebidos ou lucro**. Não existem fluxos implementados de pagamento, envio de notificações ou recuperação de senha.

## Requisitos

| Requisito | Uso confirmado |
| --- | --- |
| PHP com recursos de 8.0 ou posterior | O código usa `match`, `mixed`, union types e `str_contains`. Esse é o mínimo inferido da sintaxe; não há matriz de versões testadas nem versão mínima declarada em manifesto. |
| PDO e `pdo_pgsql` | Conexão, consultas preparadas e transações PostgreSQL. |
| Suporte a `PASSWORD_ARGON2ID` | Necessário para cadastrar contas. O cadastro é recusado quando esse suporte está ausente. |
| Fileinfo e GD | Identificação de MIME e decodificação de PNG/JPEG/WEBP. GD deve suportar esses formatos. |
| Sessões, Filter, PCRE, JSON e funções de data do PHP | Autenticação, validação e estatísticas. |
| PostgreSQL 18.x e ferramentas `psql`, `createuser`, `createdb` | O dump foi gerado na versão 18.6 e contém comandos do cliente `psql`. Não há matriz de compatibilidade testada com outras versões. |
| Diretórios temporário e de sessão graváveis pelo PHP | Upload HTTP e persistência das sessões. |

A revisão de manutenção foi executada com **PHP CLI 8.5.9**. A versão do servidor PHP/FPM ou banco de produção não foi verificada nesta revisão. Não foi encontrada chamada de produção que exija mbstring; sua presença no ambiente da auditoria histórica não é uma dependência demonstrada do aplicativo atual.

Não há `composer.json`, `package.json`, `vendor/` ou suíte de testes versionada. Composer, Node.js, Superdesign e imagegen não são necessários para executar a aplicação. As duas últimas ferramentas foram usadas para preparar contexto visual e imagens provisórias.

## Instalação e configuração

### 1. Obtenha o código e confira o PHP

Com acesso ao repositório:

```sh
git clone https://github.com/androidkkn/ProjetoFinal-ArtUP.git

```

### 2. Configure o banco

- O dump `database/dump_banco.sql` contém a estrutura completa e dados de demonstração. Ele deve ser restaurado em um banco PostgreSQL vazio.

1. Então primeiramente vamos criar um banco de dados e um usuário para a aplicação. No terminal, execute:

```sh
psql -U postgres
```

2. No prompt do PostgreSQL, crie o banco de dados e o usuário:

```sql
CREATE USER nome_do_usuario WITH PASSWORD 'senha_segura';
CREATE DATABASE nome_do_banco OWNER nome_do_usuario;
```
- Prontinho agora é so fazermos o dump do banco

3. Saia do prompt do PostgreSQL com `\q` e, na raiz do repositório, execute o seguinte comando para restaurar o dump:

```sh
psql -U nome_do_usuario -d nome_do_banco -f database/dump_banco.sql
```

- Agora temos o banco de dados configurado e pronto para ser usado pela aplicação.

### Configuração `database/credenciais.php`

- Crie `database/credenciais.php` com as credenciais do banco. Exemplo:

```php
<?php
$ip = 'IP_DO_SERVIDOR';
$nomeDB = 'NOME_DO_BANCO';
$nomeUSR = 'NOME_DO_USUARIO';
$senha = 'SUA_SENHA';
?>
```

- Agora o banco esta configurado e pronto para ser usado pela aplicação.

## Execução local

Na raiz do repositório:

```sh
php -S 127.0.0.1:8000 -t .
```

Abra `http://127.0.0.1:8000`. e Faça o login com a conta de demonstração ou cadastre uma nova conta.
### Acesso administrativo de demonstração

Após restaurar o dump, entre com a conta incluída:

| Campo | Valor |
| --- | --- |
| E-mail | `adminzudo@admininstrador.com` |
| Senha | `temqpagarprasaberasenha` |

Essas credenciais são públicas e destinadas à demonstração local. O cadastro de novas contas continua criando clientes (`admin=false`). Administradores podem promover ou rebaixar contas em **Administração → Clientes**, usando **Perfil de acesso → Salvar perfil**. O perfil é atualizado na sessão a cada requisição autenticada, inclusive para contas que já estavam conectadas. A própria conta pode virar Cliente quando houver outro administrador; nesse caso, a sessão é atualizada imediatamente e retorna à página inicial. A única conta administrativa não pode ser rebaixada: cadastre e promova outra conta antes.

## Exemplos de uso e contratos atuais

### Preços

Os exemplos usam **os valores SQL documentados no guia do banco**, não uma leitura das tarifas do banco instalado:

| Entrada | Cálculo atual | Resultado |
| --- | --- | --- |
| Adesivo BOPP, 200 × 300 cm, 3 unidades; base R$ 5,00 e tarifa R$ 0,10/m² | Área = 6 m²; unidade = 6 × 0,10 + 5,00 | R$ 5,60 por unidade; total R$ 16,80 |
| Papel sulfite A3, 1 unidade; A4 = R$ 0,50 | A3 = 2 × A4 | R$ 1,00 |
| Papel sulfite A8, 10 unidades; A4 = R$ 0,50 | Unidade = arredondar(0,50 × 0,0625, 2) | R$ 0,03 por unidade; total R$ 0,30 |

Adesivos/banners somam o valor base à área × tarifa. Placas usam somente área × tarifa; espessuras de 1, 2 e 3 mm têm a mesma regra. Papelaria usa fatores A1–A8 relativos ao A4. O cálculo usa `NUMERIC` no PostgreSQL e arredonda a unidade antes de multiplicar pela quantidade. Veja [preços](docs/funcionalidades.md#precos).

### Funções sem conexão com o banco

Este trecho pode ser executado por um script PHP na raiz. `functions_core.php` declara as funções sem abrir conexão:

```php
<?php
require __DIR__ . '/include/functions_core.php';

echo formatar_preco('1234.50'); // R$ 1.234,50
echo formatar_preco(null);      // Não calculado
echo normalizar_dimensao(' 12,50 '); // 12.50 (string; ainda em cm)
var_export(validar_inteiro_positivo('3')); // 3 (int)
var_export(validar_preco('1.999'));         // false
echo normalizar_email(' ANA@EXAMPLE.COM '); // ana@example.com
var_export(validar_senha('a1'));            // true: regra atual, não recomendação de segurança
```

O formatador não arredonda casas extras: `formatar_preco('1.999')` retorna `R$ 1,99`. Esse comportamento foi preservado. Funções de consulta geralmente retornam arrays ou `false`; listas vazias e erros devem ser diferenciados. Consulte cada função antes de alterar seu contrato.

### Rotas e erros

| Requisição/situação | Comportamento definido no código |
| --- | --- |
| Administrador acessa `/admin/estatisticas.php?inicio=2026-10-01&fim=2026-10-21` | Painel com os pedidos criados no intervalo, incluindo os dois dias. |
| Administrador envia `inicio=2026-02-30` ao painel | HTTP 422 e mensagem de datas inválidas; sem consulta de métricas. |
| Cliente autenticado acessa página administrativa | HTTP 403: `Acesso restrito ao administrador.` |
| Visitante acessa página protegida | Redirecionamento para login; algumas páginas abrem conexão antes da guarda, portanto falha de banco pode ocorrer primeiro. |
| Cliente acessa `/app/arte.php?produto=ID` com acesso e arte existente | Download como anexo; MIME, tamanho e nome original vêm do registro validado. |
| Usuário autenticado pede arte inexistente ou sem permissão | HTTP 404: `Arte não encontrada.` |
| GET `/login/logout.php` | HTTP 405, `Allow: POST`; a sessão não é encerrada. |
| Logout por POST com token válido | Limpa sessão/cookie e redireciona ao login com HTTP 303. |

Os formulários de produtos, carrinho, gestão de pedidos e logout usam tokens de sessão. Para reproduzir as ações, use o formulário recebido na mesma sessão. Login e cadastro ainda não validam CSRF. Não há API JSON pública documentada/implementada.

Cancelamento é uma solicitação: só pode ser aberto para pedido próprio pendente ou em produção, com motivo opcional de até 1000 caracteres. Aprovar cancela o pedido; recusar mantém seu status. Excluir pedido é operação administrativa definitiva. Produto e arte são preservados enquanto houver vínculo com outro pedido ou carrinho.

## Estrutura e pontos de entrada

```text
ProjetoFinal-ArtUP/
├── README.md                    Instalação, uso e manutenção
├── index.php                    Página inicial autenticada
├── login/                       Cadastro, autenticação, logout e guardas de acesso
├── app/                         Catálogo, carrinho, pedidos do cliente e download de arte
│   └── servicos/                 Define categoria/título e carrega formulário compartilhado
├── admin/                       Gestão de pedidos/produtos e estatísticas
│   └── estatisticas.css         Componentes do painel, complementando o CSS global
├── include/
│   ├── functions.php            Fachada: funções e obtenção/reutilização de uma conexão PDO
│   ├── functions_core.php       Validações, preços, carrinho, consultas e autenticação
│   ├── artes.php                Validação, rascunhos, persistência e autorização de artes
│   ├── pedidos.php              Cancelamentos, exclusão e SQL compartilhado de limpeza
│   ├── estatisticas.php         Validação de período e consulta agregada
│   ├── clientes.php             Consultas administrativas de usuários e histórico
│   ├── form_produto.php         Fluxo HTTP e HTML dos quatro serviços
│   ├── navegacao.php            URLs na raiz ou em subdiretórios
│   ├── header.php / HeaderAdmin.php  Menus por perfil e navegação administrativa
│   └── footer.php               Rodapé compartilhado
├── database/
│   ├── dump_banco.sql           Estrutura completa e dados de demonstração
│   ├── connect.php              Cria PDO e trata falha de conexão
│   └── credenciais.php          Configuração local, não versionada
├── docs/                        Guias, auditoria, diagrama e backlog
├── img/                         Logotipo e imagens genéricas provisórias
└── Estilizacoes/                Contexto e referências de design; não participa da execução PHP
```

As páginas combinam guarda de sessão, funções com PDO e renderização HTML/redirecionamento. A ordem exata varia: não reordene includes indiscriminadamente. `functions.php` reutiliza `$conexao` quando já é uma instância de PDO; `functions_core.php` pode ser incluído isoladamente em verificações sem banco.

As imagens de produtos e o design system unificado em `css/estilo.css` estão integrados aos templates (`index.php`, catálogo, serviços, carrinho, pedidos e telas administrativas), estendendo o padrão visual e de acessibilidade do painel de estatísticas para toda a aplicação. Os prompts das imagens estão documentados em [img/README.md](img/README.md).

A revisão posterior com Superdesign corrigiu a carga do helper de navegação no cadastro, o campo de upload em telas pequenas e a consistência dos menus. O escopo, a prévia visual e as 52 verificações de página e 32 verificações de contratos/acessibilidade estão em [revisão visual](docs/revisoes.md#revisao-visual).

## Testes e validação

**Não existe suíte automatizada pronta para executar neste checkout.** Os arquivos anteriores foram removidos a pedido; os números da auditoria são históricos. Não há comando confirmado `composer test`, PHPUnit/Pest instalado pelo projeto ou pipeline de CI versionado.

Uma verificação disponível é o lint do PHP. Na raiz, em PowerShell:

```powershell
$falhou = $false
Get-ChildItem -Recurse -File -Filter '*.php' |
    Where-Object { $_.Name -ne 'credenciais.php' -and $_.FullName -notmatch '[\\/](vendor|\.git)[\\/]' } |
    ForEach-Object {
        php -l $_.FullName
        if ($LASTEXITCODE -ne 0) { $falhou = $true }
    }
if ($falhou) { throw 'Falha na validação de sintaxe PHP.' }
```

Em shell POSIX, uma alternativa que também retorna falha se qualquer arquivo falhar:

```sh
find . -type f -name '*.php' ! -path './vendor/*' ! -path './.git/*' ! -name 'credenciais.php' \
  -exec sh -c 'for file do php -l "$file" || exit 1; done' sh {} +
```

Lint verifica sintaxe, não preços, permissões ou transações. Para testes funcionais, use banco isolado ou tabelas temporárias e sessões de teste. Não aponte uma futura suíte destrutiva para `database/credenciais.php` de produção.

Roteiro manual sugerido em ambiente isolado: cadastro/login; prévia dos quatro serviços; alteração de dados após a prévia; upload válido/inválido; remoção e confirmação do carrinho; download autorizado/negado; cancelamento e decisão administrativa; exclusão com produto compartilhado; filtro válido/inválido das estatísticas; logout por GET e POST.

Nesta revisão, os 30 arquivos PHP passaram no lint antes da alteração. Foram comparadas 150 observações antes/depois em roteiro temporário, incluindo consultas/parâmetros, commit/rollback, logs de erro, validações, datas e URLs. Os detalhes, limites e resultado final estão em [revisão de manutenção](docs/revisoes.md#manutencao). Esse roteiro não é suíte versionada nem validação de integração com PostgreSQL real.

## Contribuição e manutenção

- Faça alterações pequenas, separando regras de negócio, apresentação, banco e documentação nos commits.
- Preserve campos POST/GET, tokens, redirects, códigos HTTP, retornos `false`/`null` e mensagens de erro. Não aplique `strict_types` ou renomeie funções/parâmetros públicos sem analisar os chamadores.
- Mantenha o cálculo monetário no PostgreSQL com `NUMERIC`; não substitua por `float` no PHP. Entrada em cm, persistência em metros e espessura em mm são contratos diferentes.
- Preserve a ordem dos bloqueios e as fronteiras de transação. `SQL_EXCLUIR_PRODUTO_SEM_VINCULOS` concentra a condição compartilhada de remoção; os chamadores continuam responsáveis por autorização e commit/rollback.
- Mudanças de schema exigem migração revisada e validação isolada. Alterar precisão, regras de senha ou autorização constitui mudança funcional, mesmo quando for correção necessária.
- Atualize o guia específico em `docs/` e este README quando mudar a operação. Relatórios históricos não comprovam o estado de uma nova versão.
- Antes de publicar, trate os itens de segurança e implantação do backlog. Esta revisão de manutenção não os corrigiu.

Não foram encontrados licença, política formal de contribuição, automação de deploy ou configuração de servidor de produção. Esses pontos precisam ser definidos pelo responsável pelo repositório.

## Documentação complementar

Veja o [índice de documentação](docs/README.md), com os guias consolidados de [funcionalidades](docs/funcionalidades.md), [banco de dados](docs/banco-de-dados.md), [auditoria histórica](docs/auditoria.md), [product backlog](docs/product-backlog.md).
