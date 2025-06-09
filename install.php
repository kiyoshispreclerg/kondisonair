<?php

define('CONFIG_PATH', 'db.php');

if (file_exists(CONFIG_PATH)) {
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

        function generateId() {
            // Epoch: 1º Jan 2025, em milissegundos
            $epoch = 1735689600000;
            
            $timestamp = round(microtime(true) * 1000) - $epoch;
            
            if ($timestamp < 0 || $timestamp > 0x1FFFFFFFFFF) { // 2^41 - 1
                throw new Exception("Timestamp fora do intervalo para gerar ID");
            }
            
            $random = mt_rand(0, 0x7FFFFF); // 2^23 - 1
            
            $id = ($timestamp << 23) | $random;
            return $id;
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
                $language_id = 1; // id idioma usuário

                $hashed_pass = password_hash($admin_pass, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO usuarios (id, username, senha, nome_completo, descricao, id_idioma_nativo, data_cadastro, email, confirmacao, acesso) 
                    VALUES (?, ?, ?, ?, '', ?, NOW(), '', '1', 100)");
                $stmt->execute([$id_usuario, $admin_name, $hashed_pass, $admin_name, $language_id]);

                $config = "<?php\n";
                $config .= "\$mysql_host = '$host';\n";
                $config .= "\$mysql_user = '$user';\n";
                $config .= "\$mysql_pass = '$pass';\n";
                $config .= "\$mysql_db   = '$dbname';\n";
                $config .= "\n";
                
                if (file_put_contents(CONFIG_PATH, $config) === false) {
                    throw new Exception("Falha ao criar o arquivo de configuração.");
                }

                $success = true;
            } catch (Exception $e) {
                error_log("Erro de instalação: " . $e->getMessage());
                $error = "Erro durante a instalação. Verifique os dados e tente novamente.";
            }
        }
    }
}


?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Instalação - Kondisonair</title>
</head>
<body>
    <h1>Instalação do Kondisonair</h1>
    <?php if ($success): ?>
        <p>Instalação concluída com sucesso! <a href="index.php">Ir para o site</a></p>
    <?php else: ?>
        <?php if ($error): ?>
            <p style="color: red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
            <h2>Configuração do Banco de Dados</h2>
            <label>Host:</label><br>
            <input type="text" name="host" value="localhost" required><br>
            <label>Usuário do Banco:</label><br>
            <input type="text" name="user" required><br>
            <label>Senha do Banco:</label><br>
            <input type="password" name="pass"><br>
            <label>Nome do Banco:</label><br>
            <input type="text" name="dbname" required><br>
            <h2>Usuário Admin</h2>
            <label>Nome do Admin:</label><br>
            <input type="text" name="admin_name" required><br>
            <label>Senha do Admin:</label><br>
            <input type="password" name="admin_pass" required><br>
            <button type="submit">Instalar</button>
        </form>
    <?php endif; ?>
<style>
body {
    font-family: Arial, sans-serif;
    max-width: 600px;
    margin: 20px auto;
    padding: 20px;
}
h1, h2 {
    color: #333;
}
label {
    display: block;
    margin: 10px 0 5px;
}
input {
    width: 100%;
    padding: 8px;
    margin-bottom: 10px;
}
button {
    background-color: #4CAF50;
    color: white;
    padding: 10px 20px;
    border: none;
    cursor: pointer;
}
button:hover {
    background-color: #45a049;
}
</style>
</body>
</html>