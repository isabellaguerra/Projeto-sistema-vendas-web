<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'conexao.php';

// Importa os arquivos reais do PHPMailer que você colocou na pasta htdocs
require 'Exception.php';
require 'PHPMailer.php';
require 'SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_SESSION['logado']) && $_SESSION['logado'] === true) {
    header("Location: pedido.php");
    exit;
}

$erro = '';
$ok   = '';
$aba_ativa = 'login'; 

// Se o usuário clicar no link do e-mail, ele chega com o token na URL (?token=...)
if (isset($_GET['token'])) {
    $aba_ativa = 'validar_token';
    $_SESSION['token_url'] = $_GET['token'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = isset($_POST['acao']) ? $_POST['acao'] : '';

    // ==========================================
    // 1. LÓGICA DE LOGIN
    // ==========================================
    if ($acao === 'login') {
        $aba_ativa = 'login';
        $usuario = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
        $senha   = isset($_POST['senha']) ? (string)$_POST['senha'] : '';

        if (empty($usuario) || empty($senha)) {
            $erro = 'Preencha todos os campos para entrar!';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE usuario = ? LIMIT 1");
                $stmt->execute([$usuario]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($user && password_verify($senha, $user['senha'])) {
                    $_SESSION['logado'] = true;
                    $_SESSION['usuario_id'] = $user['id'];
                    $_SESSION['usuario_nome'] = isset($user['nome']) ? $user['nome'] : explode('@', $usuario)[0];
                    header("Location: pedido.php");
                    exit;
                } else {
                    $erro = 'E-mail ou senha incorretos!';
                }
            } catch (PDOException $e) {
                $erro = 'Erro no banco: ' . $e->getMessage();
            }
        }
    }

    // ==========================================
    // LÓGICA DE CADASTRO (SEM O CAMPO NOME)
    // ==========================================
    elseif ($acao === 'cadastrar') {
        $aba_ativa = 'cadastro';
        $usuario = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
        $senha   = isset($_POST['senha']) ? (string)$_POST['senha'] : '';

        if (empty($usuario) || empty($senha)) {
            $erro = 'Preencha todos os campos para se cadastrar!';
        } elseif (strlen($senha) < 8) {
            $erro = 'A senha deve ter pelo menos 8 caracteres!';
        } else {
            try {
                // Verifica se o e-mail já existe
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? LIMIT 1");
                $stmt->execute([$usuario]);
                
                if ($stmt->fetch()) {
                    $erro = 'Este e-mail já está cadastrado! Tente fazer login.';
                } else {
                    // Cria o hash seguro da senha
                    $senha_hash = password_hash($senha, PASSWORD_DEFAULT);
                    
                    // Insere apenas o usuário e senha no banco
                    $ins = $pdo->prepare("INSERT INTO usuarios (usuario, senha) VALUES (?, ?)");
                    $ins->execute([$usuario, $senha_hash]);

                    $ok = 'Conta criada com sucesso! 💕 Agora você já pode entrar.';
                    $aba_ativa = 'login';
                }
            } catch (PDOException $e) {
                $erro = 'Erro ao cadastrar: ' . $e->getMessage();
            }
        }
    }

    // ==========================================
    // 2. ENVIAR E-MAIL COM TOKEN E LINK
    // ==========================================
    elseif ($acao === 'solicitar_token') {
        $aba_ativa = 'recuperar';
        $usuario = isset($_POST['email']) ? trim((string)$_POST['email']) : '';

        if (empty($usuario)) {
            $erro = 'Informe seu e-mail!';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id FROM usuarios WHERE usuario = ? LIMIT 1");
                $stmt->execute([$usuario]);
                
                if (!$stmt->fetch()) {
                    $erro = 'Este e-mail não está cadastrado!';
                } else {
                    $token = bin2hex(random_bytes(16));
                    $expira = date('Y-m-d H:i:s', strtotime('+15 minutes'));

                    $up = $pdo->prepare("UPDATE usuarios SET token_recuperacao = ?, token_expira = ? WHERE usuario = ?");
                    $up->execute([$token, $expira, $usuario]);

                    $link = "http://localhost/login.php?token=" . $token;

                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'sandbox.smtp.mailtrap.io'; 
                        $mail->SMTPAuth   = true;
                        $mail->Username   = '846039311eecbb';        
                        $mail->Password   = '8f6a555909397b';          
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                        $mail->Port       = 587;
                        $mail->CharSet    = 'UTF-8';

                        $mail->setFrom('nao-responda@fofolicias.com', 'Fofolicias 🍓');
                        $mail->addAddress($usuario);

                        $mail->isHTML(true);
                        $mail->Subject = 'Recuperação de Senha · Fofolicias 🍓';
                        $mail->Body    = "
                            <div style='font-family: sans-serif; max-width: 500px; padding: 20px; border: 1px solid #eee; border-radius: 15px;'>
                                <h2 style='color: #ff6b8b;'>Olá! 💕</h2>
                                <p>Recebemos um pedido para redefinir a sua senha no Fofolicias.</p>
                                <p>Para criar uma nova senha, clique no botão mágico abaixo:</p>
                                <a href='$link' style='display: inline-block; background: #ff6b8b; color: white; padding: 12px 25px; text-decoration: none; border-radius: 20px; font-weight: bold; margin: 15px 0;'>Criar Nova Senha 💖</a>
                                <p style='font-size: 12px; color: #888;'>Este link é válido por 15 minutos. Se você não pediu a alteração, pode ignorar este e-mail.</p>
                            </div>
                        ";

                        $mail->send();
                        $ok = 'Link de recuperação enviado com sucesso! Verifique o Mailtrap. 💌';
                        $aba_ativa = 'login';
                    } catch (Exception $e) {
                        $erro = "O e-mail não pôde ser enviado. Erro do Mailer: {$mail->ErrorInfo}";
                    }
                }
            } catch (PDOException $e) {
                $erro = 'Erro: ' . $e->getMessage();
            }
        }
    }

    // ==========================================
    // 3. ALTERAR SENHA CONFIRMANDO O TOKEN DO BANCO
    // ==========================================
    elseif ($acao === 'validar_alterar') {
        $aba_ativa = 'validar_token';
        $token_atual = isset($_SESSION['token_url']) ? $_SESSION['token_url'] : '';
        $nova_senha  = isset($_POST['nova_senha']) ? (string)$_POST['nova_senha'] : '';

        if (empty($token_atual)) {
            $erro = 'Token inválido ou ausente. Solicite um novo link.';
            $aba_ativa = 'recuperar';
        } elseif (strlen($nova_senha) < 8) {
            $erro = 'A nova senha deve ter pelo menos 8 caracteres!';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, usuario FROM usuarios WHERE token_recuperacao = ? AND token_expira > NOW() LIMIT 1");
                $stmt->execute([$token_atual]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$user) {
                    $erro = 'O link de recuperação é inválido ou já expirou!';
                    $aba_ativa = 'recuperar';
                } else {
                    $novo_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                    
                    $update = $pdo->prepare("UPDATE usuarios SET senha = ?, token_recuperacao = NULL, token_expira = NULL WHERE id = ?");
                    $update->execute([$novo_hash, $user['id']]);
                    
                    unset($_SESSION['token_url']);
                    $ok = 'Senha redefinida com sucesso! 💕 Entre com seus novos dados.';
                    $aba_ativa = 'login';
                }
            } catch (PDOException $e) {
                $erro = 'Erro ao atualizar: ' . $e->getMessage();
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
    <title>Acesso · Fofolicias</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .form-aba { display: none; }
        .form-aba.ativa { display: block; }
        .link-alternativo { color: var(--rosa-escuro, #ff6b8b); cursor: pointer; text-decoration: underline; font-weight: 500; }
        .texto-centro { text-align: center; margin-top: 15px; font-size: 14px; color: #666; }
    </style>
</head>
<body class="login-page">
    <header class="login-header">
        <nav><h1><a href="index.php" style="background: var(--grad-hero); -webkit-background-clip: text; background-clip: text; color: transparent; text-decoration:none">Fofolicias 🍓</a></h1></nav>
    </header>
    
    <div class="login-wrapper">
        <div class="login-container box">
            
            <?php if ($erro): ?><p class="erro"><?php echo htmlspecialchars($erro, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
            <?php if ($ok): ?><p class="ok"><?php echo htmlspecialchars($ok, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
            
            <div id="aba-login" class="form-aba <?php echo $aba_ativa === 'login' ? 'ativa' : ''; ?>">
                <h2>Entrar</h2>
                <form method="POST" action="login.php">
                    <input type="hidden" name="acao" value="login">
                    <input class="input-box" type="email" name="email" placeholder="Seu e-mail" required>
                    <input class="input-box" type="password" name="senha" placeholder="Sua senha" required>
                    <p class="trocar" style="text-align: right; margin: -4px 0 14px 0; font-size: 13px;">
                        <span class="link-alternativo" onclick="mudarAba('recuperar')">Esqueceu a senha?</span>
                    </p>
                    <button type="submit">Entrar 💖</button>
                </form>
                <p class="texto-centro">Não tem uma conta? <span class="link-alternativo" onclick="mudarAba('cadastro')">Cadastre-se aqui</span></p>
            </div>

            <div id="aba-cadastro" class="form-aba <?php echo $aba_ativa === 'cadastro' ? 'ativa' : ''; ?>">
                <h2>Criar Conta</h2>
                <p style="font-size: 13px; margin-bottom: 15px; color: #666;">Cadastre-se para começar a fazer seus pedidos fofos!</p>
                <form method="POST" action="login.php">
                    <input type="hidden" name="acao" value="cadastrar">
                    <input class="input-box" type="email" name="email" placeholder="Seu e-mail" required>
                    <input class="input-box" type="password" name="senha" placeholder="Sua senha (mín. 8 caracteres)" required>
                    <button type="submit">Criar Minha Conta ✨</button>
                </form>
                <p class="texto-centro">Já tem uma conta? <span class="link-alternativo" onclick="mudarAba('login')">Voltar para o Login</span></p>
            </div>

            <div id="aba-recuperar" class="form-aba <?php echo $aba_ativa === 'recuperar' ? 'ativa' : ''; ?>">
                <h2>Recuperar Senha</h2>
                <p style="font-size: 13px; margin-bottom: 15px; color: #666;">Enviaremos um link de alteração seguro para a sua caixa de entrada.</p>
                <form method="POST" action="login.php">
                    <input type="hidden" name="acao" value="solicitar_token">
                    <input class="input-box" type="email" name="email" placeholder="Seu e-mail cadastrado" required>
                    <button type="submit">Enviar Link de Segurança 💖</button>
                </form>
                <p class="texto-centro"><span class="link-alternativo" onclick="mudarAba('login')">Voltar para o Login</span></p>
            </div>

            <div id="aba-validar_token" class="form-aba <?php echo $aba_ativa === 'validar_token' ? 'ativa' : ''; ?>">
                <h2>Escolha a Nova Senha</h2>
                <p style="font-size: 13px; margin-bottom: 15px; color: #bb3355; font-weight: bold;">Link validado pelo banco de dados com sucesso!</p>
                <form method="POST" action="login.php">
                    <input type="hidden" name="acao" value="validar_alterar">
                    <input class="input-box" type="password" name="nova_senha" placeholder="Nova Senha (mín. 8 caracteres)" required>
                    <button type="submit">Confirmar Nova Senha 💖</button>
                </form>
            </div>

        </div>
    </div>

    <script>
        function mudarAba(abaNome) {
            document.querySelectorAll('.form-aba').forEach(aba => {
                aba.classList.remove('ativa');
            });
            const alvo = document.getElementById('aba-' + abaNome);
            if(alvo) alvo.classList.add('ativa');
        }
    </script>
</body>
</html>