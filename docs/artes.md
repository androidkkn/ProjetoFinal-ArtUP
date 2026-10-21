# Envio e download das artes

Os quatro serviços aceitam uma arte opcional por produto em PNG, JPG/JPEG, WEBP ou PDF. A validação confere o upload HTTP, o tamanho real, o MIME detectado pelo servidor e a extensão. Imagens são decodificadas com GD para rejeitar arquivos corrompidos, sem regravar ou reduzir a qualidade do original. PDFs passam por uma checagem básica do cabeçalho e do marcador final `%%EOF`; essa checagem não substitui a validação completa do documento para impressão. Arquivos vazios, truncados, extensões incompatíveis e tipos não permitidos são recusados.

O limite da aplicação é 10 MB. O formulário usa o menor limite entre a aplicação, `upload_max_filesize` e `post_max_size` do PHP, reservando espaço para os outros campos. Na configuração atual, o limite efetivo é **2 MB**. Para permitir 10 MB em outra instalação, configure `upload_max_filesize=10M` e `post_max_size=12M` no PHP do servidor.

Imagens têm limite de 40 megapixels e passam por uma verificação de memória disponível antes da decodificação. Se a resolução exceder esses limites, o formulário pede uma versão menor ou PDF. O servidor precisa das extensões Fileinfo e GD, com suporte aos formatos aceitos.

## Armazenamento e vínculo

As artes ficam na tabela `artes` do PostgreSQL, com conteúdo binário `BYTEA`, nome original, MIME, tamanho, usuário e produto. Não existem URLs públicas para os arquivos. Os backups do banco devem incluir essa tabela.

O primeiro envio guarda um rascunho associado à sessão e à categoria. Ele é preservado ao calcular novamente a prévia, sem exigir que o usuário selecione o arquivo outra vez. Enviar uma nova arte substitui o rascunho anterior; uma substituição inválida preserva o arquivo anterior. O botão Remover arte permite descartar o rascunho antes de adicionar o produto.

Cada formulário informa qual rascunho foi exibido. Se outra aba substituir/remover a arte, ou o rascunho expirar, o formulário antigo bloqueia a inclusão e pede nova conferência. Assim, uma aba não inclui silenciosamente a arte escolhida por outra. As abas do mesmo serviço ainda compartilham um rascunho, com esse bloqueio de conflitos.

Ao adicionar o produto ao carrinho, o vínculo com a arte é salvo na mesma transação do produto e dos preços. Uma falha mantém o rascunho e não cria um produto parcial.

Rascunhos expiram após 24 horas e são removidos quando um novo arquivo é recebido. Artes vinculadas a produtos não expiram. Confirmar pedidos preserva o vínculo; excluir um item descartável do carrinho exclui seu produto e sua arte por `ON DELETE CASCADE`.

## Acesso

O carrinho, Meus pedidos e a administração de pedidos mostram o nome da arte e um link de download em `app/arte.php?produto=ID`. O cliente só pode baixar artes dos próprios produtos em carrinho ou pedidos; administradores autenticados podem baixar as artes dos produtos. Usuários sem acesso recebem 404, e visitantes sem login são redirecionados para o login.

Os arquivos são enviados como anexos, com tipo MIME validado, `nosniff` e cache desativado. Nomes são escapados no HTML e codificados no cabeçalho de download.

## Instalação

Execute `php database/migrar_artes.php` para criar a tabela e seu índice. A migração é aditiva e pode ser executada novamente; não altera os produtos ou pedidos existentes.

