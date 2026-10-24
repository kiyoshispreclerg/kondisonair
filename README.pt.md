🌐 **Português** | [English](README.md) | [Esperanto](README.eo.md) | [日本語](README.ja.md)

# Kondisonair

**Kondisonair** é uma plataforma que criei para organizar e compartilhar minhas línguas artificiais, mas estou publicando pois penso que pode ser útil para outros criadores de conlangs. Configure sons, formatos de sílabas, classes de palavras, sistemas de escrita, léxico, tabelas de conjugações e flexões, e textos nas conlangs. Inclui ferramentas como um gerador de palavras e um alterador sonoro para simular a evolução dos idiomas.

## Funcionalidades
- **Organização de Conlangs**: Crie e edite idiomas com detalhes como:
  - Sons: Defina tabelas de fonemas.
  - Formatos de Sílabas: Estruture sílabas.
  - Classes de Palavras: Organize substantivos, verbos, etc.
  - Tabelas de Conjugações e Flexões: Monte paradigmas e autoconjugações para verbos, substantivos, etc.
  - Sistemas de Escrita: Crie alfabetos ou scripts personalizados.
  - Léxico: Adicione vocabulário com significados detalhados.
- **Compartilhamento**: Publique seus idiomas para a comunidade diretamente na plataforma.
- **Gerador de Palavras**: Gere palavras automaticamente com base em padrões de sílabas.
- **Alterador Sonoro**: Simule mudanças sonoras.
- **Textos em Conlangs**: Carregue textos e pratique com exercícios de leitura interativos.
- **Organização de Mundos**: Crie e edite os mundos onde seus idiomas são falados:
  - Sistemas de Tempo: Crie calendários personalizados.
  - Entidades: Insira personagens e locais no seu mundo.
  - Parâmetros: Vincule parâmetros às entidades para acompanhar o progresso.
  - Linha do Tempo: Insira momentos importantes da história.
  - Histórias: Escreva textos vinculados a entidades e momentos.

## Tecnologias Usadas
- PHP, MySQL, JavaScript, [Tabler](https://tabler.io), [jQuery](https://jquery.com) etc.

**Nota**: Este é um projeto simples, sem Composer ou npm. Dependências estão incluídas nos arquivos. 

**Nota 2**: Muitas partes do código foram feitas com ajuda de IA.

## Instalação
1. Requisitos: um servidor web com **PHP** (com a extensão PDO MySQL) e **MySQL/MariaDB**.
2. Extraia os arquivos em uma pasta servida pelo seu servidor web (ex.: `htdocs/kondisonair`). A pasta precisa ter permissão de escrita para o servidor, para que o instalador possa criar o `db.php`.
3. Abra `install.php` no navegador (ex.: `http://localhost/kondisonair/install.php`).
4. Preencha host, usuário, senha e nome do banco de dados (o banco é criado se não existir), além do usuário/senha de administrador e do idioma padrão.
5. Ao terminar, o instalador cria o `db.php` com os dados de acesso ao banco e você já pode entrar. Se precisar alterar o acesso ao banco depois, edite o `db.php`.

Para rodar o instalador de novo, apague antes o `db.php` (isso não apaga o banco de dados).

## Licença
Este projeto é licenciado sob a [GNU GPLv3](LICENSE).

**Kondisonair** é apenas um projeto de hobby, mas fique à vontade para testar!