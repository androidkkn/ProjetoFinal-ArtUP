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

Em **Administração → Clientes**, o administrador consulta todos os usuários, busca por nome/e-mail/ID e abre o resumo de valores e o histórico paginado de cada conta. Veja [clientes](docs/funcionalidades.md#clientes).

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
cd ProjetoFinal-ArtUP
php -v
php -m
php --ini
```

Os comandos pressupõem `php` no `PATH`. No Windows, também é possível usar o caminho completo para `php.exe`. Confira as extensões e o `php.ini` carregado; o PHP do servidor web pode usar configuração diferente da CLI.

### 2. Crie um banco vazio e importe o dump

O arquivo [database/dump_banco.sql](database/dump_banco.sql) é um dump **SQL em texto**, gerado com PostgreSQL/pg_dump **18.6**. Ele inclui as nove tabelas, dados iniciais, sequências, índices e chaves estrangeiras, inclusive artes e cancelamentos. Use PostgreSQL 18.x e o cliente `psql` 18.x para seguir este tutorial.

Confira as ferramentas do PostgreSQL:

```sh
psql --version
createuser --version
createdb --version
```

Se os comandos não forem encontrados no Windows, adicione a pasta `bin` da sua instalação ao `PATH`. Exemplo para a instalação padrão da versão 18, na sessão atual do PowerShell:

```powershell
$env:Path = 'C:\Program Files\PostgreSQL\18\bin;' + $env:Path
```

Crie uma conta de banco chamada `artup`; `--pwprompt` solicita a senha que você usará no arquivo de configuração. Os comandos abaixo pressupõem uma instalação local na porta 5432 e a conta administrativa do PostgreSQL chamada `postgres`. Ajuste esses valores conforme sua instalação.

```sh
createuser -h 127.0.0.1 -p 5432 -U postgres --pwprompt artup
createdb -h 127.0.0.1 -p 5432 -U postgres --owner=artup artup
```

Na raiz do repositório, importe o dump no banco recém-criado:

```sh
psql -h 127.0.0.1 -p 5432 -U artup -d artup --set=ON_ERROR_STOP=1 --single-transaction --file=database/dump_banco.sql
```

Use a senha definida no `createuser`. O dump não exige a conta proprietária do banco de origem: as instruções de troca de proprietário foram removidas para que os objetos pertençam ao usuário que executa a importação.

Importe em **banco vazio**. O arquivo cria tabelas e carrega dados; não é uma atualização idempotente. `ON_ERROR_STOP` interrompe ao primeiro erro, e `--single-transaction` reverte a importação inteira nesse caso. O dump é SQL com `COPY` e comandos próprios do `psql`, como `\restrict`; use `psql`, não `pg_restore` nem o editor de consultas do pgAdmin.

Se a conta `artup` já existir, reutilize-a com sua senha. Se o banco `artup` já contiver dados, crie outro banco vazio e ajuste `-d` e `$nomeDB`; não repita a importação sobre a instalação existente.

O dump atual contém:

| Conteúdo | Quantidade |
| --- | --- |
| Categorias | 4 |
| Tipos de produto | 11 |
| Tarifas por categoria | 4 |
| Produtos existentes | 18 |
| Conta administrativa demonstrativa | 1 |
| Pedidos, carrinho, artes e solicitações de cancelamento | 0 |

Os 18 produtos são registros do snapshot e não representam pedidos confirmados. As categorias usam IDs fixos: **1 = adesivos, 2 = papelaria, 3 = placas e 4 = banners**. Não execute novamente os INSERTs de exemplo do guia do banco após restaurar o dump.

Os [diagramas do banco](docs/banco-de-dados.md#modelo-de-dados) detalham as tabelas e os relacionamentos. Medidas persistidas em `produto` continuam em metros; a interface usa centímetros.

### 3. Configure as credenciais

Crie `database/credenciais.php`, já ignorado pelo Git. Use o mesmo banco, usuário e senha dos comandos anteriores. O formato corresponde às variáveis lidas por [database/connect.php](database/connect.php):

```php
<?php
$ip = '127.0.0.1';
$nomeDB = 'artup';
$nomeUSR = 'artup';
$senha = 'senha_definida_no_createuser';
```

A conexão monta o DSN `pgsql:host=...;dbname=...` e habilita `PDO::ERRMODE_EXCEPTION`. O arquivo atual não oferece campos próprios para porta ou modo SSL.

**Não existe carregamento de `.env`, `$_ENV` ou `getenv()` na aplicação.** Definir `DB_HOST`, `DATABASE_URL` ou `APP_ENV` no terminal não configura este código. A conexão depende do arquivo PHP acima; limites e opções de sessão dependem do `php.ini`/servidor. Não versione credenciais nem copie seus valores para logs ou documentação.

### 4. Confira a restauração

Verifique as tabelas e os dados iniciais:

```sh
psql -h 127.0.0.1 -p 5432 -U artup -d artup --command="\dt public.*"
psql -h 127.0.0.1 -p 5432 -U artup -d artup --command="SELECT (SELECT COUNT(*) FROM categorias) AS categorias, (SELECT COUNT(*) FROM tipos_de_produto) AS tipos, (SELECT COUNT(*) FROM usuarios WHERE admin = TRUE) AS administradores;"
```

Após importar o dump fornecido, são esperadas nove tabelas e as contagens `4`, `11` e `1`, respectivamente.

**O dump já inclui as migrações 001 e 002.** Não é necessário executá-las para instalar esta versão a partir dele. Os scripts abaixo permanecem disponíveis para bancos anteriores que já tenham as tabelas base e precisem receber/verificar as estruturas adicionais:

```sh
php database/migrar_artes.php
php database/migrar_cancelamentos.php
```

Eles aceitam somente CLI; acesso HTTP retorna 404. As mensagens de sucesso são `Estrutura de artes criada/verificada com sucesso.` e `Estrutura de solicitações de cancelamento criada/verificada com sucesso.` As migrações são aditivas e não substituem a importação inicial do dump.

### 5. Confira upload e sessão

O teto de arte é 10 MiB (`10 * 1024 * 1024` bytes), reduzido pelos limites do PHP. Para disponibilizar esse teto, uma configuração possível, coerente com o código, é:

```ini
file_uploads = On
upload_max_filesize = 10M
post_max_size = 12M
```

O cálculo reserva 65.536 bytes de `post_max_size` para os demais campos. O limite efetivo aparece no formulário; ele não é fixo para todas as instalações. `memory_limit` também influencia a aceitação de imagens, que têm teto de 40 megapixels.

As artes dos clientes ficam como `BYTEA` **no banco**, não em `/img`. Essa pasta contém assets da interface. Inclua `artes` nos backups. O código usa a configuração de cookies e armazenamento de sessão do PHP; não há bootstrap comum que configure todas essas opções.

## Execução local

Na raiz do repositório:

```sh
php -S 127.0.0.1:8000 -t .
```

Abra `http://127.0.0.1:8000/login/login.php`. O cadastro está em `/login/cadastrar.php`. A página inicial exige autenticação. Encerre com `Ctrl+C`.

Esse comando é para desenvolvimento local em loopback. A raiz contém `.git`, documentação e arquivos internos; não há configuração de implantação pública versionada. Não exponha esse servidor como instalação de produção. Os requisitos de publicação abertos estão na [auditoria](docs/auditoria.md) e no [backlog](docs/product-backlog.md).

### Acesso administrativo de demonstração

Após restaurar o dump, entre com a conta incluída:

| Campo | Valor |
| --- | --- |
| E-mail | `adminzudo@admininstrador.com` |
| Senha | `temqpagarprasaberasenha` |

Essas credenciais são públicas e destinadas à demonstração local. A senha dessa conta está em texto puro no dump; não reutilize essa conta em uma instalação pública. O cadastro de novas contas continua usando hash Argon2id e criando clientes (`admin=false`). Não há tela de promoção de usuários; o papel administrativo é copiado para a sessão no login.

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
│   ├── credenciais.php          Configuração local, não versionada
│   ├── migrar_artes.php         Entrada CLI da migração 001
│   ├── migrar_cancelamentos.php Entrada CLI da migração 002
│   └── migrations/              Migrações adicionais já incorporadas ao dump
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

Veja o [índice de documentação](docs/README.md), com os guias consolidados de [funcionalidades](docs/funcionalidades.md), [banco de dados](docs/banco-de-dados.md), [auditoria histórica](docs/auditoria.md), [product backlog](docs/product-backlog.md) e [histórico de revisões](docs/revisoes.md).
