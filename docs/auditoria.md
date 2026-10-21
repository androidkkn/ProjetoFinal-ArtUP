# Auditoria PHP, AppSec, qualidade e desempenho — ArtUP

Data: 21/10/2026. Escopo: árvore de trabalho atual, incluindo alterações anteriores ainda não commitadas.
Registro histórico: a suíte automatizada, suas ferramentas, configurações, cache e resultados foram
removidos posteriormente a pedido. Os resultados abaixo documentam as execuções anteriores à remoção.
Atualização posterior: a página de estatísticas foi implementada, resolvendo essa parte de B04.
Imagens/textos restantes e os demais achados continuam no [product backlog](product-backlog.md).
O funcionamento e os limites do painel estão em [estatísticas](funcionalidades.md#estatisticas).
Este relatório registra o estado auditado antes da mudança posterior da interface para centímetros,
descrita em [preços](funcionalidades.md#precos). Os números de linha referem-se àquele estado e podem se deslocar
com alterações posteriores. As funções anteriormente
concentradas em `include/functions.php` estão em `include/functions_core.php`; a fachada anterior continua válida.

## Resultado e limites

**A aplicação passou nos testes funcionais executados, mas ainda não deve ser considerada aprovada em segurança.**
Foram executados 101 testes PHPUnit, com 216 asserções, e os três roteiros anteriores, que verificam mais
281 condições. Os 13 contratos de segurança/correção desejada falham no estado auditado e identificam
problemas abertos; não são testes ignorados nem falhas artificialmente convertidas em sucesso.

A auditoria combinou leitura de todos os arquivos PHP de produção, buscas por fontes/consumidores de entrada,
inspeção somente de leitura do catálogo do PostgreSQL, testes unitários com mocks e requisições HTTP reais
em loopback. As escritas dos testes ocorreram exclusivamente em tabelas temporárias com `search_path=pg_temp`.
Nenhum pedido, usuário ou arte persistente foi excluído ou alterado nesta auditoria.

Ambiente observado: PHP CLI 8.5.9; PostgreSQL 18.6; PHPUnit 12.5.40; Argon2id, GD, fileinfo, mbstring e curl
disponíveis. Não havia Composer instalado nem framework de testes declarado. O PHAR oficial foi instalado
com versão e SHA-256 fixados. Foi adicionado `composer.json` como alternativa de instalação, sem dependências
de execução de terceiros. A configuração nova declara PHP >= 8.3, compatível com o PHPUnit escolhido;
essa versão mínima é uma decisão de manutenção proposta nesta entrega e deve ser alinhada à hospedagem.

Não foi acessada uma implantação pública, sua configuração Apache/Nginx/FPM, proxy, certificados ou backups.
Configurações inseguras locais são distinguidas de riscos condicionais de implantação. Não houve teste de
carga distribuída, exploração destrutiva, inspeção de todo o histórico Git nem prova formal de ausência de
vulnerabilidades. Não há driver Xdebug/PCOV nesta estação, portanto não é declarado percentual de cobertura.

## Fase 1 — AppSec / OWASP

### Controles verificados

| Área | Evidência e conclusão |
| --- | --- |
| SQL injection | Consultas de entrada usam `prepare` e `execute`/`bindValue`/`bindParam`. `functions_core.php:374` concatena somente um fragmento SQL fixo e parametriza os valores. Pesquisa com `' OR 1=1 --` não amplia resultados nem altera dados. Não foi confirmada SQLi. |
| RCE / desserialização | Não há `eval`, `unserialize`, `shell_exec`, `system` ou execução de shell em produção. Os `PDO::exec` de migrações executam SQL fixo. `app/arte.php:25` usa **fpassthru**, que transmite um arquivo/stream; não é a função de shell `passthru`. `proc_open` aparece apenas nas ferramentas/testes novos, com argumentos em array. |
| LFI/RFI | Inclusões de produção resolvem caminhos fixos com `__DIR__`; nenhuma usa caminho recebido do cliente. |
| XSS | Nome, pesquisa, motivo, campos de formulário e mensagens dinâmicas são escapados com `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`. Testes anteriores verificam motivo/arquivo malicioso. Não foi confirmado XSS executável. Recomenda-se também `ENT_SUBSTITUTE` para saídas textuais com UTF-8 inválido. |
| CSRF de negócio | Carrinho, produtos, pedidos, cancelamentos, exclusão e logout exigem token de sessão comparado com `hash_equals`. Logout recusa GET e token inválido. As exceções são S05. |
| Autorização | Guarda administrativa exige booleano `true`; cadastro público força `admin=false`. Downloads restringem proprietário com vínculo ou administrador, retornam 404 ao acesso indevido, `attachment`, `nosniff` e `private, no-store`. Limitação de revogação: S07. |
| Senhas novas | Cadastro usa Argon2id e o login de contas com hash usa `password_verify`; sessão é regenerada após autenticar (`login/login.php:27`). Problemas legados/política: S01–S03. |
| Uploads | MIME real, extensão, `is_uploaded_file`, limites de bytes/pixels/memória, decodificação de imagens, vínculo transacional e posse dos rascunhos são testados. PDF verifica assinatura e terminador, não toda a estrutura nem conteúdo ativo. Arquivos são armazenados como bytea e baixados como anexo; não são incluídos ou executados no PHP. |
| Segredos | Credenciais ficam no arquivo local ignorado `database/credenciais.php`, sem valores espalhados nas consultas. Nenhum segredo foi copiado ao relatório/testes. Isso não protege um servidor com document root inadequado (S09) nem substitui revisão do histórico Git. |

Não há achado de severidade **crítica** confirmado. As severidades abaixo representam impacto e condições
observadas, sem transformar ausência de um framework ou de uma PSR em vulnerabilidade.

### S01 — Alta — Login aceita armazenamento legado sem hash

**Local:** [login/login.php:20](../login/login.php#L20). OWASP: falhas de autenticação/criptografia.
`algoName === 'unknown'` aciona comparação direta com o conteúdo armazenado. A fixture com senha em texto
puro autentica por HTTP. A inspeção agregada encontrou três valores sem algoritmo reconhecido no banco
configurado; isso não prova que todos sejam texto puro, pois também podem ser formatos antigos não suportados.
Vazamento do banco pode revelar senhas reutilizadas. Não há migração/recuperação implementada nem rehash.

**Correção recomendada:** inventariar formatos sem registrar valores, exigir redefinição segura das contas
legadas e remover a comparação direta. Após login com hash válido, atualizar o custo/algoritmo quando necessário.
O fluxo de redefinição deve usar token de uso único, com hash armazenado e expiração; não basta bloquear contas.

```php
$stored = $usuario['senha'];
$senhaValida = password_get_info($stored)['algoName'] !== 'unknown'
    && password_verify($senhaInformada, $stored);
if ($senhaValida && password_needs_rehash($stored, PASSWORD_ARGON2ID)) {
    $stmt = $conexao->prepare('UPDATE usuarios SET senha=:novo WHERE id=:id AND senha=:antigo');
    $stmt->execute(['novo' => password_hash($senhaInformada, PASSWORD_ARGON2ID),
        'id' => $usuario['id'], 'antigo' => $stored]);
}
```

### S02 — Alta — Autenticação e cadastro sem limitação de tentativas

**Local:** [login/login.php:10](../login/login.php#L10), [login/cadastrar.php:8](../login/cadastrar.php#L8).
Todo POST pode consultar/verificar/calcular hash; não há contador, atraso controlado ou bloqueio temporário.
Permite ataques de credenciais e consumo de CPU/memória com Argon2id, sobretudo combinado com S03.
O custo real por tentativa e os limites da hospedagem não foram medidos.

**Correção recomendada:** limitar IP na borda e identidade normalizada na aplicação com contador atômico e TTL,
retornar 429 e monitorar falhas. Não usar `sleep` dentro de cada worker PHP nem bloqueio permanente de conta.
Exemplo de limite inicial Nginx, a ajustar à carga (integre à configuração FastCGI existente):

```nginx
# contexto http
limit_req_zone $binary_remote_addr zone=auth:10m rate=5r/m;
# dentro das locations reais de login e cadastro, antes do fastcgi_pass
limit_req zone=auth burst=5 nodelay;
limit_req_status 429;
```

Limites por identidade devem complementar o IP, com armazenamento compartilhado entre workers.
Referência: [OWASP Authentication](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html).

### S03 — Alta — Política permite senha de dois caracteres e entrada sem limite operacional

**Local:** [include/functions_core.php:584](../include/functions_core.php#L584),
[login/cadastrar.php:52](../login/cadastrar.php#L52), [login/login.php:12](../login/login.php#L12).
`a1` é aceita; somente letra e número são exigidos. Não há MFA. Senhas muito fracas são fáceis de adivinhar;
entradas muito grandes também são processadas antes de limites de aplicação.

**Correção recomendada:** política baseada em comprimento, permitir frases e Unicode, verificar senhas
comprometidas quando houver integração adequada, e definir teto em bytes sem truncamento silencioso.
Exemplo inicial (15 caracteres sem MFA; teto operacional de 1024 bytes), a refletir na interface e login:

```php
function validarSenha(string $senha): bool
{
    return mb_check_encoding($senha, 'UTF-8') && !str_contains($senha, "\0")
        && mb_strlen($senha, 'UTF-8') >= 15 && strlen($senha) <= 1024;
}
```

O teste de comprimento expressa a política recomendada; não foi imposta silenciosamente aos usuários atuais.
Referência: [OWASP Authentication, controles de força](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html#implement-proper-password-strength-controls).

### S04 — Média — Identidade de e-mail sem unicidade no banco

**Local:** [include/functions_core.php:609](../include/functions_core.php#L609),
[include/functions_core.php:632](../include/functions_core.php#L632). Catálogo observado: `usuarios` só possui índice primário.
Dois cadastros com o mesmo e-mail normalizado são aceitos; o `fetch` do login pode selecionar uma conta
ambígua. O tratamento de 23505 existe, mas não atua sem constraint/índice único.

**Correção recomendada:** resolver duplicatas existentes com decisão sobre as contas e criar índice único
funcional correspondente à consulta. Não fazer deduplicação automática por exclusão de usuários.

```sql
-- Revisão prévia em ambiente controlado:
SELECT lower(trim(email)) AS email_normalizado, count(*)
FROM usuarios GROUP BY 1 HAVING count(*) > 1;
-- Depois de resolver os conflitos:
CREATE UNIQUE INDEX uq_usuarios_email_normalizado ON usuarios (lower(trim(email)));
```

### S05 — Média — Login e cadastro não validam CSRF

**Local:** [login/login.php:10](../login/login.php#L10), [login/login.php:50](../login/login.php#L50),
[login/cadastrar.php:8](../login/cadastrar.php#L8), [login/cadastrar.php:43](../login/cadastrar.php#L43).
POST sem token autentica e cria conta. Login CSRF pode colocar a vítima numa conta controlada pelo atacante;
cadastro também permite submissão de origem externa. SameSite é defesa complementar.

**Correção recomendada:** token por sessão nos dois formulários, validado antes de consulta/hash/gravação.

```php
$_SESSION['csrf_auth'] ??= bin2hex(random_bytes(32));
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? null;
    if (!is_string($token) || !hash_equals($_SESSION['csrf_auth'], $token)) {
        http_response_code(403);
        exit('Recarregue a página e tente novamente.');
    }
}
// No formulário:
// <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_auth'], ENT_QUOTES, 'UTF-8') ?>">
```

Referência: [OWASP CSRF](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html).

### S06 — Média — Sessão depende de defaults inseguros observados localmente

**Local:** [login/verifica_user.php:5](../login/verifica_user.php#L5), [login/login.php:6](../login/login.php#L6),
[login/cadastrar.php:4](../login/cadastrar.php#L4), [login/logout.php:4](../login/logout.php#L4).
Os pontos de entrada chamam `session_start` sem configurar flags. No PHP local: HttpOnly desligado,
Secure desligado, SameSite vazio, `session.use_strict_mode=0`. O cookie HTTP emitido confirma os dois
primeiros contratos testáveis sem TLS (HttpOnly/SameSite). Isso não prova a configuração do FPM em produção.

**Correção recomendada:** bootstrap único antes de qualquer saída ou abertura de sessão; HTTPS obrigatório
na implantação e configuração de proxy confiável. Regeneração no login já existe e deve ser preservada.

```php
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => '/',
    'secure' => getenv('APP_ENV') === 'production']);
session_start();
```

Referência: [OWASP Session Management](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html).

### S07 — Alta — Revogação de administrador não invalida sessão; sem TTL de aplicação

**Local:** [login/login.php:28](../login/login.php#L28), [login/verifica_admin.php:4](../login/verifica_admin.php#L4),
[login/verifica_user.php:7](../login/verifica_user.php#L7).
O papel é copiado uma vez para `$_SESSION`; remover `usuarios.admin` mantém acesso administrativo em uma
sessão existente. A guarda verifica somente presença do ID e não implementa expiração ociosa/absoluta.
Foi reproduzido HTTP 200 administrativo com papel já revogado na fixture.

**Correção recomendada:** consultar o papel atual para operações administrativas, ou validar uma versão de
autorização revogável, e centralizar TTL. Exemplo de guarda após obter o PDO:

```php
$stmt = $conexao->prepare('SELECT admin FROM usuarios WHERE id=:id');
$stmt->execute(['id' => $_SESSION['id']]);
if ($stmt->fetchColumn() !== true) {
    $_SESSION['admin'] = false;
    http_response_code(403);
    exit('Acesso restrito.');
}
// No login, inicialize issued_at e last_seen; na guarda:
if (time() - (int) ($_SESSION['last_seen'] ?? 0) > 1800
    || time() - (int) ($_SESSION['issued_at'] ?? 0) > 28800) {
    $_SESSION = [];
    session_destroy();
    http_response_code(401);
    exit;
}
$_SESSION['last_seen'] = time();
```

### S08 — Média — HTML sem política de segurança comum

**Local:** [include/header.php:1](../include/header.php#L1), [login/login.php:38](../login/login.php#L38).
As respostas HTML locais não possuem CSP/frame-ancestors, X-Frame-Options, Referrer-Policy ou nosniff
centralizados. O download de artes já inclui nosniff. Páginas autenticadas também não estabelecem política
explícita comum de cache. Há risco de clickjacking e menor contenção de eventual XSS; nenhum XSS foi confirmado.

**Correção recomendada:** cabeçalhos no bootstrap, antes do HTML (o include visual atual aparece tarde):

```php
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Cache-Control: private, no-store'); // páginas autenticadas
// Na borda HTTPS, após validar todos os subdomínios:
// Strict-Transport-Security: max-age=31536000
```

Validar a CSP contra recursos reais antes de impor; não adicionar `unsafe-inline` por conveniência.

### S09 — Alta, condicional à implantação — Raiz do repositório publicável

**Local:** [database/connect.php:2](../database/connect.php#L2),
[parâmetros SQL](banco-de-dados.md#parametros-iniciais), estrutura sem `public/` e sem configuração
versionada de bloqueio de diretórios internos. O servidor PHP local iniciado na raiz entrega `/.git/HEAD`
com HTTP 200 (sonda inofensiva, sem buscar segredos). Isso não comprova exposição na hospedagem atual.
PHP corretamente configurado executa `credenciais.php`; sua localização por si só não prova download do fonte.
Porém metadados Git, SQL, backups e ferramentas ficam sob uma raiz indevida.

**Correção recomendada:** criar raiz pública contendo somente entrypoints/assets e manter aplicação,
credenciais, testes, ferramentas e Git fora dela. Exemplo de arquitetura de deploy a implementar:

```nginx
root /srv/artup/public;
location ~ /\. { deny all; }
# public/index.php deve encaminhar só rotas explicitamente permitidas.
```

Os entrypoints atuais precisam ser adaptados na migração; trocar só o `root` agora quebraria as rotas.
Usar segredo por ambiente fora da raiz e revisar histórico antes de publicar. Nenhuma credencial foi exposta
nos arquivos novos.

### S10 — Média, condicional à implantação — Exibição de erros não controlada pelo ambiente

**Local:** [database/connect.php:2](../database/connect.php#L2); configuração local em
`C:\SRC\php\php.ini:498` (display_errors), `:507` (startup) e `:393` (expose_php).
PHP local tem `display_errors` e `display_startup_errors` ativos, `expose_php=1`; não há configuração de
produção versionada. Falta do arquivo de credenciais ocorre antes do try; erros não capturados podem
revelar caminhos e detalhes. O catch de conexão já retorna mensagem genérica: isso é um controle existente.

**Correção recomendada:** ini do FPM/servidor, logs privados e tratamento central. Configurar no ini é
necessário porque falhas de startup/parse podem ocorrer antes do código:

```ini
display_errors = Off
display_startup_errors = Off
log_errors = On
expose_php = Off
error_log = /var/log/artup/php-error.log
```

Não desligar logs nem retornar `$e->getMessage()` ao navegador. Verificar a configuração efetiva do SAPI
de produção; a observação CLI não é prova dela.

### S11 — Baixa — Regras de exclusão Git não propagadas (corrigido nesta entrega)

**Local:** [.gitignore:1](../.gitignore#L1). O arquivo preexistente ignorava a si próprio e não era
versionado. Assim, as regras locais de proteção a credenciais não seriam levadas automaticamente a
um novo clone. Foi removida a regra `/.gitignore` e incluídos PHAR, cache, resultados e configuração de
testes na exclusão. O arquivo agora aparece entre os fontes a versionar; nenhum commit foi criado.

```gitignore
/database/credenciais.php
/vendor/
/tools/*.phar
/.env.testing
/test-results/
```

Isso evita inclusão acidental desses arquivos via Git e não substitui o bloqueio HTTP de S09.

## Fase 2 — Bugs, tipagem, PSR e arquitetura

### B01 — Média — Validação de dimensões diverge da persistência REAL

**Local:** [include/functions_core.php:30](../include/functions_core.php#L30),
[include/functions_core.php:141](../include/functions_core.php#L141).
`1e-100` passa na validação e produz preço `5.00`, mas o INSERT falha com SQLSTATE 22003 por underflow
de `produto.largura REAL`. O rollback funciona e não deixa produto órfão. REAL também aproxima medidas
decimais antes de um eventual recálculo, podendo produzir divergência em valores no limite de arredondamento.

**Correção recomendada:** definir precisão e faixa de medidas de negócio e manter a mesma representação
decimal na validação, coluna e cálculo. Exemplo de política de até seis casas em metros e teto 999999:

```php
$valor = str_replace(',', '.', trim($entrada));
if (!preg_match('/\A(?:0|[1-9]\d{0,5})(?:\.\d{1,6})?\z/', $valor)
    || (float) $valor <= 0) {
    return false;
}
return $valor;
```

```sql
-- Validar faixa/dados atuais antes da alteração:
ALTER TABLE produto ALTER COLUMN largura TYPE numeric(12,6) USING largura::numeric(12,6);
ALTER TABLE produto ALTER COLUMN comprimento TYPE numeric(12,6) USING comprimento::numeric(12,6);
```

A política é um exemplo a aprovar; não foi aplicada a preços ou dados atuais.

### B02 — Baixa — Formatação monetária trunca casas extras

**Local:** [include/functions_core.php:6](../include/functions_core.php#L6).
`formatar_preco('1.999')` produz `R$ 1,99`. Os fluxos atuais persistem NUMERIC com duas casas e passam,
portanto o problema afeta reutilização da função com precisão maior. Não foi encontrada perda de centavos
no cálculo SQL atual, que arredonda a unidade antes do total.

**Correção recomendada:** explicitar que o formatador recebe centavos inteiros, deixando a conversão decimal
arredondada na camada de cálculo, sem transformar valores financeiros em float:

```php
function formatarCentavos(int $centavos): string
{
    $sinal = $centavos < 0 ? '-' : '';
    $digitos = str_pad(ltrim((string) $centavos, '-'), 3, '0', STR_PAD_LEFT);
    $reais = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', substr($digitos, 0, -2));
    return 'R$ ' . $sinal . $reais . ',' . substr($digitos, -2);
}
// No banco: SELECT (round(:valor::numeric, 2) * 100)::bigint
```

### B03 — Média — Schema base permite estados inválidos

**Local:** [include/functions_core.php:140](../include/functions_core.php#L140),
[include/functions_core.php:404](../include/functions_core.php#L404),
[database/migrations/001_artes.sql:5](../database/migrations/001_artes.sql#L5).
O catálogo atual não possui CHECK de quantidade positiva em produto nem domínio de status em pedido.
FKs independentes de categoria/tipo não garantem que o tipo pertença à categoria. As regras PHP validam
esses casos, mas outros escritores/importações podem violá-las. As migrações de artes/cancelamento já têm
checks úteis, diferentemente do schema base. A fixture permite INSERT de quantidade -1, como o schema observado.

**Correção recomendada:** migração após inventário dos dados; estabelecer checks de quantidade/medidas/status
e FK composta categoria/tipo. O status legado `carrinho` precisa ser preservado até uma migração explícita:

```sql
ALTER TABLE produto ADD CONSTRAINT ck_produto_quantidade CHECK (quantidade > 0) NOT VALID;
ALTER TABLE pedido ADD CONSTRAINT ck_pedido_status
    CHECK (status IN ('pendente','em producao','concluido','cancelado','carrinho')) NOT VALID;
ALTER TABLE tipos_de_produto ADD CONSTRAINT uq_tipo_categoria UNIQUE (id, id_categoria);
ALTER TABLE produto ADD CONSTRAINT fk_produto_tipo_categoria
    FOREIGN KEY (id_tipo, id_categoria) REFERENCES tipos_de_produto (id, id_categoria) NOT VALID;
-- Corrigir inconsistências anteriores e VALIDATE CONSTRAINT em etapa controlada.
```

### B04 — Baixa — Recursos visuais e estatísticas incompletos

**Local:** [index.php:23](../index.php#L23), [index.php:28](../index.php#L28),
[app/catalogo.php:23](../app/catalogo.php#L23), [admin/estatisticas.php:17](../admin/estatisticas.php#L17).
O repositório só contém o logotipo em `img/`; foto da loja, novidades e quatro imagens do catálogo não
existem. A página administrativa de estatísticas ainda apresenta um texto provisório. Isso é incompletude
funcional, sem evidência de comprometimento de segurança.

**Correção recomendada:** fornecer os assets reais ou usar placeholder existente; implementar agregação
com autorização já presente. Exemplos sem inventar números de vendas:

```php
$imagem = is_file(__DIR__ . '/img/foto-loja.png') ? 'img/foto-loja.png' : 'img/logos/logo.png';
// <img src="<?= htmlspecialchars($imagem, ENT_QUOTES, 'UTF-8') ?>" alt="ArtUP">
$totais = $conexao->query("SELECT status, count(*) AS quantidade,
    coalesce(sum(valor_total), 0) AS valor FROM pedido
    WHERE status <> 'carrinho' GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
```

Definir os indicadores com o negócio; total de pedidos não equivale a receita recebida, pois não há pagamento integrado.

### Q01 — Média — Sem contratos de tipos no código de produção

**Local:** [include/functions_core.php:48](../include/functions_core.php#L48),
[include/artes.php:37](../include/artes.php#L37), [include/pedidos.php:6](../include/pedidos.php#L6).
Inspeção estática simples de 29 arquivos PHP de produção, excluindo o arquivo local de credenciais:
0 com `declare(strict_types=1)`, 36 funções nomeadas, 0 com tipos de parâmetros/retorno. Dados inválidos
podem atravessar camadas por coerção; não se deve habilitar strict_types em massa sem adaptar entradas.

**Correção recomendada:** validar/converter na borda HTTP e adicionar contratos gradualmente. PHP 8.3+
permite union types, enums e readonly para novos objetos de valor; não há classes/DTOs existentes que
simplesmente devam ser marcadas readonly. Exemplo de evolução, não implementado nesta auditoria:

```php
declare(strict_types=1);

enum StatusPedido: string
{
    case Pendente = 'pendente';
    case EmProducao = 'em producao';
    case Concluido = 'concluido';
    case Cancelado = 'cancelado';
}

final readonly class Preco
{
    public function __construct(public int $unitarioCentavos, public int $totalCentavos) {}
}
// Durante transição: function buscarPreco(PDO $pdo, int $categoria, ...): array|false
```

Todos os novos testes PHPUnit/objetos de suporte têm strict_types e contratos. A fachada de compatibilidade
e o código legado permanecem sem mudança de semântica de coerção.

### Q02 — Baixa — Violações concretas de PSR-12; avaliação de PSR-1/4

**Local:** [login/verifica_user.php:4](../login/verifica_user.php#L4),
[database/connect.php:15](../database/connect.php#L15), [database/connect.php:20](../database/connect.php#L20).
Há `if(` sem espaço, chaves sem espaçamento, fechamento `?>` em arquivo só PHP e 15 arquivos de produção
com CRLF na inspeção. PSR-12 exige LF e omissão desse fechamento. Não foi executado PHP_CodeSniffer;
esta é uma revisão manual/estática, não uma certificação automática de estilo.

**Correção recomendada:** configurar `.editorconfig`/formatador e aplicar mudanças de estilo separadamente:

```php
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Em connect.php: } catch (PDOException $e) { ... }
// Omitir o fechamento PHP se não há HTML no arquivo.
```

PSR-1: tags completas/echo curto estão adequadas; nomes de funções globais em snake_case não violam a
regra de camelCase de **métodos**. A separação feita nesta entrega removeu conexão/efeito de I/O do arquivo
de declarações. PSR-4 é sobre classes: o aplicativo procedural não tem classes para mapear e não declara
conformidade. `composer.json` agora mapeia as classes de teste por PSR-4, sem converter automaticamente a aplicação.
Referências: [PSR-1](https://www.php-fig.org/psr/psr-1/), [PSR-4](https://www.php-fig.org/psr/psr-4/),
[PSR-12](https://www.php-fig.org/psr/psr-12/).

### Q03 — Média — Sessão, SQL, regras e apresentação acoplados

**Local:** [include/functions_core.php:110](../include/functions_core.php#L110),
[include/pedidos.php:6](../include/pedidos.php#L6), [include/form_produto.php:28](../include/form_produto.php#L28),
[include/functions.php:6](../include/functions.php#L6).
Há superglobais GET/POST/SESSION em 16 arquivos de produção, inclusive em funções de negócio. PDO é
passado a várias funções (injeção explícita útil), mas autenticação, regras, transações e arrays de retorno
continuam compartilhados sem interfaces. Os controladores usam `header`/`exit`, exigindo subprocessos nos testes.

**Correção recomendada:** extrair primeiro serviços com usuário/entrada tipados e repositórios injetados;
manter superglobais no adaptador HTTP. Exemplo de fronteira proposta:

```php
interface CancelamentoService
{
    public function solicitar(int $usuarioId, int $pedidoId, string $motivo): bool;
}
// Uma implementação recebe PDO/repositório no construtor; o controlador recebe o serviço.
// Extrair a transação/lock atuais para ela, usando $usuarioId em lugar de $_SESSION.
```

PSR-7/PSR-15: não há mensagens HTTP nem middleware implementando essas interfaces. Isso é ausência de
adoção/interoperabilidade, não SQLi ou violação obrigatória num aplicativo procedural. Se a arquitetura
adotar essas PSRs, uma guarda pode receber `ServerRequestInterface`, delegar ao `RequestHandlerInterface`
e retornar `ResponseInterface`, tornando resposta/status testáveis sem `exit`. Depende da escolha e
instalação de uma implementação; não foi adicionada biblioteca de runtime só para aparentar conformidade.
Referências: [PSR-7](https://www.php-fig.org/psr/psr-7/), [PSR-15](https://www.php-fig.org/psr/psr-15/).

### Q04 — Média — Bootstrap do schema de produção não é reproduzível no checkout atual

**Local:** [database/migrations/001_artes.sql:5](../database/migrations/001_artes.sql#L5),
[database/migrations/002_cancelamentos.sql:5](../database/migrations/002_cancelamentos.sql#L5),
[parâmetros SQL](banco-de-dados.md#parametros-iniciais).
As duas migrações exigem tabelas base existentes; o SQL em docs contém consultas/seeds, não a criação de
todas as relações. Os arquivos antigos de schema aparecem removidos na árvore de trabalho, alteração
anterior a esta auditoria que foi preservada. Um banco vazio não pode receber somente essas duas migrações.
Fixtures resolvem isso para os testes, mas não substituem uma migração de produção versionada.

**Correção recomendada:** criar baseline revisada a partir do catálogo e controlar versões de migração,
sem executar o DDL de fixtures em produção. Exemplo mínimo da estrutura a completar com FKs/checks:

```sql
CREATE TABLE schema_migrations (versao varchar(100) PRIMARY KEY, aplicado_em timestamptz NOT NULL DEFAULT now());
-- 000_baseline.sql deve criar usuarios/categorias/tipos/tarifas/produto/carrinho/pedido
-- antes das referências de 001 e 002; registrar cada versão só após commit bem-sucedido.
```

O README antigo descrevia funções já implementadas como ausentes; foi atualizado nesta entrega para
documentar os fluxos atuais e os caminhos efetivamente existentes.

## Fase 3 — Performance, banco e I/O

### P01 — Média — N+1 no preenchimento de preços do carrinho

**Local:** [include/functions_core.php:256](../include/functions_core.php#L256).
Para N itens sem preço, há um SELECT inicial e, para cada item, um SELECT de preço e um UPDATE:
**1 + 2N execuções de statements**, além do controle de transação. São 201 para 100 itens, por contagem
do fluxo, não por benchmark. O loop também prepara cada UPDATE novamente e mantém locks até o final.
Os loops de renderização de pedidos/artes usam JOINs/EXISTS, não foi encontrado N+1 equivalente neles.

**Correção recomendada:** calcular/atualizar o lote no PostgreSQL com JOIN/CTE e um único arredondamento
unitário, mantendo transação e locks; abortar todo o lote se qualquer tarifa/item for inválido. Exemplo
concreto da atualização em lote para a categoria de placas (portar os demais CASEs da fórmula atual):

```sql
WITH calculados AS (
    SELECT c.id, p.quantidade,
        round(p.largura::numeric * p.comprimento::numeric * pr.valormetro, 2) AS unitario
    FROM carrinho c JOIN produto p ON p.id=c.id_produto
    LEFT JOIN precodimensoes pr ON pr.id_categoria=p.id_categoria
    WHERE c.id_usuario=:usuario AND p.id_categoria=3
      AND (c.preco_unitario IS NULL OR c.valor_total IS NULL)
), valores AS (
    SELECT *, round(unitario * quantidade, 2) AS total FROM calculados
)
UPDATE carrinho c SET preco_unitario=v.unitario, valor_total=v.total
FROM valores v WHERE c.id=v.id
  AND NOT EXISTS (SELECT 1 FROM valores WHERE unitario IS NULL OR unitario < 0
      OR total > 9999999999.99 OR quantidade <= 0);
```

O trecho é uma proposta parcial para revisão, não uma substituição completa da função: falta incluir
as outras três categorias e a política de B01. Como melhoria intermediária, preparar UPDATE uma vez
fora do foreach reduz trabalho de parse, mas ainda deixa 2N execuções e não resolve as viagens ao banco.
Ganho esperado do lote: reduzir essas execuções para poucas chamadas, especialmente útil num banco remoto;
não foi atribuído fator de aceleração sem medir latência/carga reais.

### P02 — Média — Listagens sem paginação e materialização total

**Local:** [include/functions_core.php:208](../include/functions_core.php#L208),
[include/functions_core.php:233](../include/functions_core.php#L233),
[include/functions_core.php:386](../include/functions_core.php#L386),
[include/functions_core.php:460](../include/functions_core.php#L460).
`fetchAll` traz todos os pedidos/carrinho/itens administrativos e o HTML renderiza tudo. Catálogo/tarifas
pequenos não justificam Generator isoladamente; histórico de pedidos e painel administrativo crescem
indefinidamente. Há risco de memória/tempo de resposta proporcional ao histórico.

**Correção recomendada:** paginação por cursor nas telas, limite de tamanho do carrinho e exportações
por lotes. Preservar filtro de proprietário em todas as páginas. Exemplo de página de histórico:

```sql
SELECT pe.id, pe.data, pe.status, pe.valor_total
FROM pedido pe WHERE pe.id_usuario=:usuario AND pe.status <> 'carrinho'
  AND (pe.data, pe.id) < (CAST(:cursor_data AS timestamp), CAST(:cursor_id AS integer))
ORDER BY pe.data DESC, pe.id DESC LIMIT 51;
```

Buscar 51 permite exibir 50 e sinalizar a próxima página. A primeira página omite a condição de cursor.
Para exportações grandes, `yield` sobre lotes limitados evita construir um array de todo o histórico:

```php
function exportarPedidos(PDO $pdo, int $usuarioId): Generator
{
    $ultimo = 0;
    do {
        $stmt = $pdo->prepare("SELECT id, status, valor_total FROM pedido
            WHERE id_usuario=:usuario AND id>:ultimo AND status <> 'carrinho'
            ORDER BY id LIMIT 500");
        $stmt->execute(['usuario' => $usuarioId, 'ultimo' => $ultimo]);
        $lote = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($lote as $linha) {
            $ultimo = $linha['id'];
            yield $linha;
        }
    } while (count($lote) === 500);
}
```

Isso limita o resultado de cada consulta, inclusive no driver. Trocar apenas `fetchAll` por `fetch/yield`
sem limitar a consulta não garante streaming/memória constante no PDO. Se for necessário snapshot estável
de toda a exportação, definir explicitamente consistência/limite superior e duração da transação.

### P03 — Média — Índices ausentes nos filtros e vínculos principais

**Local:** [include/functions_core.php:195](../include/functions_core.php#L195),
[include/functions_core.php:229](../include/functions_core.php#L229),
[include/pedidos.php:120](../include/pedidos.php#L120),
[include/functions_core.php:374](../include/functions_core.php#L374).
O catálogo inspecionado só mostrou PK para pedido e nenhum índice começando por usuário em carrinho.
FKs PostgreSQL não criam automaticamente índices no lado que referencia. Busca por e-mail usa expressão
sem índice correspondente (S04); busca por nome usa `%texto%`, para a qual um B-tree normal não resolve
o padrão. Não foi demonstrada lentidão atual em tabelas pequenas: o problema é de crescimento previsível.

**Correção recomendada:** índices alinhados aos filtros e à paginação, avaliados com EXPLAIN (ANALYZE,
BUFFERS) em dados representativos de teste; medir custo de escrita antes/depois:

```sql
CREATE INDEX idx_pedido_usuario_data ON pedido (id_usuario, data DESC, id DESC)
    WHERE status <> 'carrinho';
CREATE INDEX idx_pedido_produto ON pedido (id_produto);
CREATE INDEX idx_carrinho_usuario_id ON carrinho (id_usuario, id);
-- Índice de e-mail: usar o UNIQUE funcional proposto em S04.
```

Se a pesquisa de nomes se tornar frequente e volumosa, avaliar `pg_trgm` + GIN em `usuarios.nome`;
não instalar extensão só para poucas linhas. Os índices `uq_cancelamento_pendente`,
`idx_cancelamentos_pedido` e de rascunhos já existem e não devem ser recriados com nomes diferentes.
Ganho esperado: reduzir linhas visitadas em listagens por cliente e verificação de referências nas
exclusões; custo adicional: espaço e atualização de índices. Não foram inventados tempos de EXPLAIN.

### P04 — Média — Limpeza global de rascunhos no upload e crescimento de BYTEA

**Local:** [include/artes.php:161](../include/artes.php#L161),
[database/migrations/001_artes.sql:10](../database/migrations/001_artes.sql#L10).
Cada upload tenta remover todos os rascunhos expirados durante a transação do usuário. Se não houver
novos uploads, arquivos expirados ficam armazenados. Cada arquivo tem limite, mas não há quota total
de bytes por cliente/carrinho; sucessivas inclusões com arte aumentam banco, WAL e backups.
O número de rascunhos substituídos por categoria é limitado pelo fluxo normal; não se deve presumir
que um simples reenvio preserve infinitas versões.

**Correção recomendada:** limpeza agendada em lotes curtos e quota de armazenamento validada atomicamente
por usuário (com lock por usuário para evitar ultrapassagem concorrente). Exemplo do lote de limpeza:

```sql
WITH expiradas AS (
    SELECT id FROM artes WHERE id_produto IS NULL
      AND criado_em < CURRENT_TIMESTAMP - INTERVAL '24 hours'
    ORDER BY criado_em, id LIMIT 500 FOR UPDATE SKIP LOCKED
)
DELETE FROM artes a USING expiradas e WHERE a.id=e.id;
```

Manter BYTEA é razoável em escala pequena e preserva atomicidade. Object storage privado só deve ser
introduzido quando volume/backup justificarem; exigiria autorização equivalente, URLs curtas assinadas,
integridade e coordenação de exclusões. Não basta trocar o campo por uma URL pública.
Ganho esperado: upload deixa de pagar uma varredura/limpeza global e a retenção deixa de depender de tráfego.

### P05 — Baixa — Conexões antes da guarda e sessão presa durante leitura de arte

**Local:** [app/catalogo.php:1](../app/catalogo.php#L1), [app/carrinho.php:2](../app/carrinho.php#L2),
[include/form_produto.php:6](../include/form_produto.php#L6), [app/arte.php:23](../app/arte.php#L23).
Algumas páginas abrem conexão antes de recusar visitantes. O catálogo nem consulta o banco diretamente.
No download, a sessão só é liberada depois de consultar o BYTEA, serializando solicitações da mesma sessão
durante a latência de banco. O streaming de saída já ocorre após `session_write_close`, controle correto.

**Correção recomendada:** guarda primeiro, conexão sob demanda; no download somente de leitura, liberar
o lock da sessão antes da consulta (as variáveis já carregadas permanecem disponíveis em memória):

```php
require_once __DIR__ . '/../login/verifica_user.php';
session_write_close(); // rota de download não altera a sessão depois daqui
require_once __DIR__ . '/../include/functions.php';
$arte = consultar_arte_produto($conexao, $_GET['produto'] ?? null);
```

No catálogo, remover a conexão desnecessária; no carrinho/formulário, apenas reordenar guarda e conexão.
Ganho esperado: uma conexão evitada por acesso rejeitado e melhor concorrência entre abas durante downloads.

### Estratégias de cache e configuração — recomendações, sem falha de cache confirmada

| Estratégia | Aplicação ao projeto e ganho esperado | Limites |
| --- | --- | --- |
| OPcache | Evitar parse/compilação repetida das mesmas funções/templates entre requisições. `opcache.enable=1` foi observado; confirmar no FPM e dimensionar com métricas de hits/memória. | CLI está com `opcache.enable_cli=0`; tempos dos testes não medem produção. Não desligar checagem de timestamp sem invalidação controlada no deploy. |
| APCu | Cache curto de opções de tipos/categorias exibidas em formulários, se houver tráfego que justifique e extensão instalada. Economiza consultas repetidas desses pequenos conjuntos. | Não está instalado. Invalidar/versionar em edição administrativa. A conexão eager de P05 deve ser resolvida para aproveitar o ganho. Não usar como autoridade de preço no checkout. |
| Redis | Contadores atômicos/TTL de S02 e, em múltiplas instâncias, coordenação de sessão/cache compartilhado. | Não está instalado nem há API Redis no projeto. Exige timeout, autenticação/TLS conforme rede e política clara quando indisponível. Não introduzir dependência operacional apenas para cache de quatro categorias. |
| HTTP/arquivos | Cache longo para assets versionados, reduzir 404 após B04. | Manter artes privadas com no-store e autorização; não fazer cache público de páginas autenticadas. |

Exemplo conservador de configuração OPcache para validar na hospedagem:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=10000
opcache.validate_timestamps=1
opcache.revalidate_freq=2
```

Nenhum ganho percentual foi medido. Priorizar consultas/paginação/índices antes de cachear dados transacionais.

## Fase 4 — Histórico da validação

Os arquivos da suíte, inclusive os roteiros de regressão, configurações, ferramentas, certificados locais,
cache e resultados JUnit, foram removidos a pedido. Não há comandos da suíte disponíveis neste checkout.

| Execução anterior à remoção | Resultado histórico |
| --- | --- |
| Suíte funcional na auditoria | **101 testes, 216 asserções, aprovado.** |
| Suíte funcional após mudança para centímetros | **117 testes, 277 asserções, aprovado.** |
| Roteiros anteriores executados na suíte | **76 + 46 + 159 = 281 verificações**, aprovadas. |
| Contratos de segurança/correção na auditoria | **13 testes, 13 falhas**, reproduzindo achados abertos. |

Os contratos que falharam correspondiam a S01, S03, S04, S05 (dois casos), S06 (dois casos), S07,
S08, S09, B01, B02 e B03. A remoção dos arquivos não resolve esses achados. Os testes anteriores
usavam PostgreSQL com tabelas temporárias e mocks nativos de PDO/PDOStatement; não alteraram dados
persistentes de clientes. A separação das funções e as regras da aplicação foram preservadas.

### Ordem de correção recomendada

1. **Antes de publicar:** S01–S03 (senhas/abuso), S04 (identidade), S06–S07 (sessão/revogação), confirmar
   S09–S10 na implantação. S09 é de alta severidade quando a raiz do repo é exposta, condição reproduzida localmente.
2. **Na mesma rodada de segurança:** S05 (CSRF de autenticação) e S08 (cabeçalhos). Fazer ambas as suítes
   parte do controle de qualidade e tornar os contratos vermelhos aprovados conforme cada correção.
3. **Integridade e crescimento:** B01/B03, Q04 e P01–P04; validar migrações em cópia/ambiente de teste,
   sem transformar deduplicação ou mudança de precisão em exclusão automática de dados.
4. **Manutenção e experiência:** Q01–Q03, B02/B04 e P05, com alterações menores revisáveis. Cache adicional
   só depois de medir demanda e resolver os acessos ao banco já identificados.

Mudanças de produção feitas por esta entrega: separação das declarações de funções e da abertura de
conexão, permitindo PDO fornecido pelo chamador; chamadas e regras existentes foram preservadas.
Também foram atualizadas documentação e regras Git para os artefatos de teste (S11 resolvido).
Os demais achados listados permanecem abertos; suas correções são recomendações para implementação.
Referência para a infraestrutura de testes: [manual oficial do PHPUnit](https://docs.phpunit.de/en/12.5/installation.html).
