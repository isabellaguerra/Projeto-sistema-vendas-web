<?php
session_start();
require_once 'conexao.php';

$stmt = $pdo->query("SELECT * FROM produtos");
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$destino = (isset($_SESSION['logado']) && $_SESSION['logado'] === true) ? 'pedido.php' : 'login.php';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lojinha Fofolicias</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" type="image/png" href="IconCat.png">
</head>
<body>

    <header>
        <nav>
            <h1>Fofolicias</h1>
            <ul>
                <li><a href="#home">Home</a></li>
                <li><a href="#sobre">Sobre</a></li>
                <li><a href="#produtos">Produtos</a></li>
                <li><a href="#avaliacoes">Avaliações</a></li>
                <li><a href="#contato">Contato</a></li>
                
                <?php if (isset($_SESSION['logado']) && $_SESSION['logado'] === true): ?>
                    <li><a href="pedido.php" class="login-btn">Fazer Pedido</a></li>
                    <li><a href="logout.php" style="color: #fff; margin-left: 10px; text-decoration: none;">Sair</a></li>
                <?php else: ?>
                    <li><a href="login.php" class="login-btn">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <div id="home" class="fofo">
        <h2>Bem-vindo a sua loja Kawaii</h2>
        <p>Mais fofa, mais deliciosa e divertida.</p>
    </div>

    <section id="sobre">
        <h2>Sobre Nós</h2>
        <p>A Fofolicias nasceu para trazer produtos fofos e especiais direto do Japão e da Coreia para você! 💕🍓</p>
    </section>

    <section id="produtos">
        <h2>Produtos em Destaque</h2>
        <div class="produtos-container">
            <?php foreach ($produtos as $prod): 
                // Define links de imagens padrão caso o banco de dados não possua o link correto
                $nomeItem = strtolower($prod['nome']);
                $imagemUrl = htmlspecialchars($prod['imagem']);

                if (empty($prod['imagem']) || !filter_var($prod['imagem'], FILTER_VALIDATE_URL)) {
                    if (strpos($nomeItem, 'kitkat') !== false) {
                        $imagemUrl = 'https://i.ibb.co/27vN84X/kitkat-sakura.png';
                    } elseif (strpos($nomeItem, 'oreo') !== false) {
                        $imagemUrl = 'https://i.ibb.co/vYg6Pz4/oreo-pokemon.png';
                    } elseif (strpos($nomeItem, 'mochi') !== false) {
                        $imagemUrl = 'https://i.ibb.co/Mgs7z6F/mochi-strawberry.png';
                    } elseif (strpos($nomeItem, 'pringles') !== false) {
                        $imagemUrl = 'https://i.ibb.co/9v6N6gq/pringles-caramel.png';
                    } else {
                        $imagemUrl = 'https://i.ibb.co/27vN84X/kitkat-sakura.png'; // Imagem padrão fofa
                    }
                }
            ?>
                <div class="produto">
                    <img src="<?php echo $imagemUrl; ?>" alt="<?php echo htmlspecialchars($prod['nome']); ?>">
                    <h3><?php echo htmlspecialchars($prod['nome']); ?></h3>
                    <p><?php echo htmlspecialchars($prod['descricao']); ?></p>
                    <p style="color: #e76595; font-weight: bold;">R$ <?php echo number_format($prod['preco'], 2, ',', '.'); ?></p>
                    <button onclick="location.href='<?php echo $destino; ?>'">Comprar</button>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section id="avaliacoes">
        <h2>Avaliações</h2>
        <div class="avaliacoes-container">
            <div class="avaliacao">
                <p>"Amo o estilo dos produtos." <br><span class="avaliador"> - anita Rios </span></p>
            </div>
        </div>
    </section>

    <section id="contato">
        <h2>Contato</h2>
        <div class="contato-area">
            <div class="contato-info">
                <p>📞 (13) 99999-9999</p>
                <p>📧 contato@fofolicias.com</p>
            </div>
        </div>
    </section>

    <footer>
        <p>© 2026 Fofolicias — Todos os direitos reservados.</p>
    </footer>

</body>
</html>