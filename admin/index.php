<?php
require_once __DIR__ . '/../includes/init.php';

// Só admin entra aqui (validado no servidor)
exigirAdmin();

$pdo = conectar();

// Números do resumo (consultas sem parâmetros do usuário)
$totalProdutos  = $pdo->query("SELECT COUNT(*) FROM produtos WHERE ativo = 1")->fetchColumn();
$totalPedidos   = $pdo->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
$totalVendido   = $pdo->query("SELECT COALESCE(SUM(total), 0) FROM pedidos")->fetchColumn();
$totalClientes  = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE perfil = 'cliente'")->fetchColumn();

// Produtos ativos com pouco estoque (5 ou menos)
$estoqueBaixo = $pdo->query(
    "SELECT id, nome, estoque FROM produtos WHERE ativo = 1 AND estoque <= 5 ORDER BY estoque, nome"
)->fetchAll();

$tituloPagina = 'Painel administrativo';
$menuAtivo = 'painel';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3">Painel administrativo</h1>
<?php include __DIR__ . '/_menu.php'; ?>

<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <div class="fs-2 fw-bold"><?= (int) $totalProdutos ?></div>
                <div class="text-muted small">Produtos ativos</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <div class="fs-2 fw-bold"><?= (int) $totalPedidos ?></div>
                <div class="text-muted small">Pedidos realizados</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <div class="fs-2 fw-bold"><?= e(formatarPreco($totalVendido)) ?></div>
                <div class="text-muted small">Total vendido</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="card text-center h-100">
            <div class="card-body">
                <div class="fs-2 fw-bold"><?= (int) $totalClientes ?></div>
                <div class="text-muted small">Clientes cadastrados</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header fw-semibold">Produtos com estoque baixo (5 ou menos)</div>
    <?php if (count($estoqueBaixo) === 0): ?>
        <div class="card-body text-muted">Nenhum produto com estoque baixo.</div>
    <?php else: ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($estoqueBaixo as $p): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <a href="<?= e(url('admin/produto_form.php?id=' . (int) $p['id'])) ?>"
                       class="text-decoration-none"><?= e($p['nome']) ?></a>
                    <span class="badge <?= $p['estoque'] == 0 ? 'bg-danger' : 'bg-warning text-dark' ?>">
                        <?= (int) $p['estoque'] ?> un.
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
