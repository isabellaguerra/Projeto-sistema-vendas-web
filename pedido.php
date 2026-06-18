<?php
session_start();
require_once 'conexao.php';

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM produtos");
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fazer Pedido - Fofolicias</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <nav>
            <h1>Olá, <?php echo $_SESSION['usuario_nome']; ?>! 🍓</h1>
            <ul>
                <li><a href="index.php#home">Home</a></li>
                <li><a href="index.php#sobre">Sobre</a></li>
                <li><a href="index.php#produtos">Produtos</a></li>
                <li><a href="index.php#avaliacoes">Avaliações</a></li>
                <li><a href="index.php#contato">Contato</a></li>
                <li><a href="logout.php" class="login-btn" style="background: #393939;">Sair</a></li>
            </ul>
        </nav>
    </header>

    <div class="pedido-container">
        <div class="produtos-lista">
            <?php foreach ($produtos as $prod): ?>
                <div class="produto-card">
                    <img src="<?php echo htmlspecialchars($prod['imagem']); ?>" alt="<?php echo htmlspecialchars($prod['nome']); ?>">
                    <h3><?php echo htmlspecialchars($prod['nome']); ?></h3>
                    <p class="preco">R$ <?php echo number_format($prod['preco'], 2, ',', '.'); ?></p>
                    <button onclick="adicionar('<?php echo htmlspecialchars($prod['nome']); ?>', <?php echo $prod['preco']; ?>, '<?php echo htmlspecialchars($prod['imagem']); ?>')">Adicionar ao Carrinho</button>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="carrinho">
            <h2>Seu Pedido</h2>
            <ul id="lista-pedido"></ul>
            <h3>Total: R$ <span id="total">0,00</span></h3>
            <button onclick="finalizarPedido()" style="background: #25D366; width: 100%; font-size: 16px; color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: bold;">
                Enviar Pedido para o WhatsApp 💬
            </button>
        </div>
    </div>

    <script>
        let total = 0;
        let carrinho = [];

        // FUNÇÃO ADICIONAR COM SUPORTE A IMAGEM
        function adicionar(nome, preco, img) {
            total += preco;
            document.getElementById("total").innerText = total.toFixed(2).replace('.', ',');
            carrinho.push({ nome: nome, preco: preco, img: img });
            atualizarInterfaceCarrinho();
        }

        function remover(index) {
            total -= carrinho[index].preco;
            document.getElementById("total").innerText = total.toFixed(2).replace('.', ',');
            carrinho.splice(index, 1);
            atualizarInterfaceCarrinho();
        }

        // MONTA A INTERFACE DO CARRINHO IGUAL AO SEU ORIGINAL COM A MINIATURA DA IMAGEM
        function atualizarInterfaceCarrinho() {
            const lista = document.getElementById("lista-pedido");
            lista.innerHTML = "";
            carrinho.forEach((item, index) => {
                const li = document.createElement("li");
                li.innerHTML = `
                    <img src="${item.img}" style="width: 50px; height: 50px; object-fit: contain;">
                    <span>${item.nome}</span>
                    <span>R$ ${item.preco.toFixed(2).replace('.', ',')}</span>
                    <button onclick="remover(${index})">X</button>
                `;
                lista.appendChild(li);
            });
        }

        function finalizarPedido() {
            if (carrinho.length === 0) {
                alert("O seu carrinho está completamente vazio!");
                return;
            }
            let texto = `Olá Fofolicias! 💕 Gostaria de fazer o seguinte pedido:\n\n`;
            carrinho.forEach(item => {
                texto += `🌸 • ${item.nome} (R$ ${item.preco.toFixed(2).replace('.', ',')})\n`;
            });
            texto += `\n*Total do Pedido:* R$ ${total.toFixed(2).replace('.', ',')}\n`;
            texto += `Cliente: <?php echo $_SESSION['usuario_nome']; ?>`;

            let numeroWhats = "5513999999999"; 
            let url = `https://api.whatsapp.com/send?phone=${numeroWhats}&text=${encodeURIComponent(texto)}`;
            window.open(url, '_blank');
        }
    </script>
</body>
</html>