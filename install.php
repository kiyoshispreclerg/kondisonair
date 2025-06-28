<?php
require('config.php');

if (file_exists(DB_CONFIG)) {
    header('Location: index.php');
    exit;
}

session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //session_start();
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = "Token CSRF inválido.";
    } else {
        $host = trim($_POST['host'] ?? '');
        $user = trim($_POST['user'] ?? '');
        $pass = trim($_POST['pass'] ?? '');
        $dbname = trim($_POST['dbname'] ?? '');
        $admin_name = trim($_POST['admin_name'] ?? '');
        $admin_pass = trim($_POST['admin_pass'] ?? '');
        $def_lang = trim($_POST['def_lang'] ?? 1);

        if (!preg_match('/^[a-zA-Z0-9_.-]+$/', $host)) {
            $error = "Host inválido. Use apenas letras, números, pontos, hífens ou underscores.";
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $dbname)) {
            $error = "Nome do banco de dados inválido. Use apenas letras, números ou underscores.";
        }
        if (!preg_match('/^[a-zA-Z0-9]{3,50}$/', $admin_name)) {
            $error = "Nome do admin inválido. Use 3 a 50 caracteres (letras e/ou números).";
        }

        if (strlen($host) > 255 || strlen($dbname) > 64 || strlen($user) > 100 || strlen($admin_name) > 100) {
            $error = "Um ou mais campos excedem o tamanho máximo permitido.";
        }

        if (empty($host) || empty($user) || empty($dbname) || empty($admin_name) || empty($admin_pass)) {
            $error = "Todos os campos são obrigatórios.";
        } else {
            try {
                $pdo = new PDO("mysql:host=$host", $user, $pass);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                $pdo->exec("CREATE DATABASE IF NOT EXISTS $dbname");
                $pdo->exec("USE $dbname");

                $schema = file_get_contents(__DIR__ . '/schema.sql');
                $pdo->exec($schema);

                $data = file_get_contents(__DIR__ . '/data.sql');
                $pdo->exec($data);

                $id_usuario = generateId();

                $stmt = $pdo->prepare("INSERT INTO opcoes_sistema (id, opcao, valor) 
                    VALUES (12, 'def_lang', ?)");
                $stmt->execute([$def_lang]);

                $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (id, username, senha, nome_completo, descricao, id_idioma_nativo, data_cadastro, email, confirmacao, acesso) 
                    VALUES (?, ?, ?, ?, '', ?, NOW(), '', '1', 100)");
                $stmt->execute([$id_usuario, $admin_name, $hashed_pass, $admin_name, $def_lang]);

                $config = "<?php\n";
                $config .= "\$mysql_host = '$host';\n";
                $config .= "\$mysql_user = '$user';\n";
                $config .= "\$mysql_pass = '$pass';\n";
                $config .= "\$mysql_db   = '$dbname';\n";
                $config .= "\n";
                
                if (file_put_contents(DB_CONFIG, $config) === false) {
                    throw new Exception("Falha ao criar o arquivo de configuração.");
                }

                $success = true;
            } catch (Exception $e) {
                error_log("Erro de instalação: " . $e->getMessage());
                $error = "Erro durante a instalação. Verifique os dados e tente novamente.".$e->getMessage();
            }
        }
    }
}


?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Kondisonair</title>
    <link href="dist/css/tabler2.min.css?1692870487" rel="stylesheet"/>
</head>
<body>
    <div class="page-body">
        <div class="container-xl">
            <div class="text-center mb-4">
                <a href="." class="navbar-brand navbar-brand-autodark">
                    <img src="logo.png" width="110" height="32" alt="Tabler" class="navbar-brand-image">
                </a>
            </div>
            <div class="row mb-4 align-items-center">
                <div class="col">
                    <h2 class="page-title">Instalação do Kondisonair</h2>
                </div>
            </div>
            <div class="card">
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            Instalação concluída com sucesso! <a href="index.php" class="alert-link">Ir para o site</a>
                        </div>
                    <?php else: ?>
                        <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert">
                                <?php echo htmlspecialchars($error); ?>
                            </div>
                        <?php endif; ?>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                            <h3 class="card-title">Configuração do Banco de Dados</h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-label">Host</div>
                                    <input type="text" class="form-control" name="host" value="localhost" required>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-label">Usuário do Banco</div>
                                    <input type="text" class="form-control" name="user" required>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-label">Senha do Banco</div>
                                    <input type="password" class="form-control" name="pass">
                                </div>
                                <div class="col-md-6">
                                    <div class="form-label">Nome do Banco</div>
                                    <input type="text" class="form-control" name="dbname" required>
                                </div>
                            </div>
                            <h3 class="card-title mt-4">Usuário Admin</h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-label">Nome do Admin</div>
                                    <input type="text" class="form-control" name="admin_name" required>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-label">Senha do Admin</div>
                                    <input type="password" class="form-control" name="admin_pass" required>
                                </div>
                            </div>
                            <h3 class="card-title">Outras configurações</h3>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-label">Idioma padrão</div>
                                    <select name="def_lang" class="chosen-select form-control">
                                        <?php 
                                        foreach ($idiomas_sistema as $id => $nome_exibido) {
                                            echo '<option value="' . $id . '">' . htmlspecialchars($nome_exibido) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent mt-4">
                                <div class="btn-list justify-content-end">
                                    <button type="submit" class="btn btn-primary">Instalar</button>
                                </div>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>