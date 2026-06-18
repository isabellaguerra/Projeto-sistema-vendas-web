<?php
require_once __DIR__ . '/../includes/config.php';
require_admin();
$titulo = 'Admin · Produtos';

// Ações rápidas (toggle / remover) via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $acao = $_POST['acao'] ?? '';
    $id   = (int)($_POST['id'] ?? 0);
    if ($acao === 'toggle') {
        db()->prepare("UPDATE produtos SET disponivel = 1 - disponivel WHERE id = ?")->execute([$id]);
        flash('ok', 'Disponibilidade atualizada.');
    } elseif ($acao === 'excluir') {
        db()->prepare("DELETE FROM produtos WHERE id = ?")->execute([$id]);
        flash('ok', 'Produto removido.');
    }
    redirect('/miau-presentes/admin/produtos.php');
}

$produtos = db()->query("SELECT * FROM produtos ORDER BY id DESC")->fetchAll();

include __DIR__ . '/../includes/header.php';
?>
<div class="admin-shell">
  <?php include __DIR__ . '/_sidebar.php'; ?>
  <section>
    <div class="page-head">
      <h1>Produtos</h1>
      <a class="btn btn-primary" href="/miau-presentes/admin/produto_form.php">+ Novo produto</a>
    </div>
    <div class="table-wrap">
      <table class="tbl">
        <thead><tr>
          <th>#</th><th>Nome</th><th>Preço</th><th>Estoque</th><th>Status</th><th>Ações</th>
        </tr></thead>
        <tbody>
        <?php foreach ($produtos as $p): ?>
          <tr>
            <td>#<?= (int)$p['id'] ?></td>
            <td>
              <strong><?= e($p['nome']) ?></strong><br>
              <small style="color:var(--texto-suave)"><?= e(mb_strimwidth($p['descricao'] ?? '', 0, 60, '…')) ?></small>
            </td>
            <td><?= preco_br((float)$p['preco']) ?></td>
            <td><?= (int)$p['estoque'] ?></td>
            <td>
              <?php if ($p['disponivel']): ?>
                <span class="badge badge-ok">Disponível</span>
              <?php else: ?>
                <span class="badge badge-off">Indisponível</span>
              <?php endif; ?>
            </td>
            <td class="actions">
              <a class="btn btn-sm btn-ghost" href="/miau-presentes/admin/produto_form.php?id=<?= (int)$p['id'] ?>">Editar</a>
              <form method="post" style="display:inline">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="acao" value="toggle">
                <button class="btn btn-sm"><?= $p['disponivel'] ? 'Marcar indisponível' : 'Marcar disponível' ?></button>
              </form>
              <form method="post" style="display:inline" onsubmit="return confirm('Remover este produto?')">
                <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <input type="hidden" name="acao" value="excluir">
                <button class="btn btn-sm btn-danger">Excluir</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$produtos): ?>
          <tr><td colspan="6" style="text-align:center;color:var(--texto-suave);padding:24px">Nenhum produto cadastrado ainda.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>