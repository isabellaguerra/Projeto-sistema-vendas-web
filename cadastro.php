<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexao.php';

// Se o usuário já estiver logado, manda direto para a página de pedidos
if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    header("Location: pedido.php");
    exit;
}

$erro = '';
$ok   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
    $senha   = isset($_POST['senha']) ? (string)$_POST['senha'] : '';

    if (empty($usuario) || empty($senha)) {
        $erro = 'Preencha todos os campos!';
    } elseif (!filter_var($usuario, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Insira um e-mail válido!';
    } elseif (strlen($senha) < 8) {
        $erro = 'A senha deve ter pelo menos 8 caracteres!';
    } else {
        try {
            // 1. Verifica se o e-mail já existe na coluna 'usuario'
            $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? LIMIT 1");
            $stmt->execute([$usuario]);
            
            if ($stmt->fetch()) {
                $erro = 'Este e-mail já está cadastrado!';
            } else {
                // 2. Cria a senha criptografada de forma segura
                $hash = password_hash($senha, PASSWORD_DEFAULT);
                
                // 3. Insere apenas nas colunas 'usuario' e 'senha' para evitar erros de estrutura
                $ins = $pdo->prepare("INSERT INTO usuarios (usuario, senha) VALUES (?, ?)");
                $ins->execute([$usuario, $hash]);
                
                $ok = 'Conta criada com sucesso! 💕 Agora você já pode entrar.';
            }
        } catch (PDOException $e) {
            $erro = 'Erro no banco de dados: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Conta · Fofolicias</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="login-page">
    <header class="login-header">
        <nav><h1><a href="index.php" style="background: var(--grad-hero); -webkit-background-clip: text; background-clip: text; color: transparent; text-decoration:none">Fofolicias 🍓</a></h1></nav>
    </header>
    <div class="login-wrapper">
        <div class="login-container">
            <h2>Criar Conta</h2>
            
            <?php if ($erro): ?><p class="erro"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
            <?php if ($ok): ?><p class="ok"><?php echo htmlspecialchars($ok, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
            
            <form method="POST" action="cadastro.php" novalidate>
                <input class="input-box" type="email" name="email" placeholder="Seu melhor e-mail" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                <input class="input-box" type="password" name="senha" placeholder="Crie uma senha (mín. 8 caracteres)" required>
                <button type="submit">Cadastrar 💖</button>
            </form>
            <p class="trocar" style="margin-top:14px">Já tem uma conta? <a href="login.php">Entrar aqui</a></p>
        </div>
    </div>
</body>
</html>