<?php
require_once __DIR__ . '/includes/init.php';

exigirLogin('meus_pedidos.php');

$pdo = conectar();

// Pedidos só do usuário logado
$stmt = $pdo->prepare(
    "SELECT id, data_pedido, forma_pagamento, total
     FROM pedidos
     WHERE usuario_id = :usuario
     ORDER BY data_pedido DESC, id DESC"
);
$stmt->execute([':usuario' => usuarioLogado()['id']]);
$pedidos = $stmt->fetchAll();

// Prepara uma vez a consulta dos itens e usa para cada pedido
$stmtItens = $pdo->prepare(
    "SELECT i.quantidade, i.preco_unitario, p.nome
     FROM itens_pedido i
     JOIN produtos p ON p.id = i.produto_id
     WHERE i.pedido_id = :pedido"
);

$tituloPagina = 'Meus pedidos';
include __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4">Meus pedidos</h1>

<?php if (count($pedidos) === 0): ?>
    <div class="alert alert-info">
        Você ainda não fez nenhum pedido. <a href="<?= e(url('index.php')) ?>">Ir para a loja</a>
    </div>
<?php else: ?>
    <?php foreach ($pedidos as $pedido): ?>
        <?php
        $stmtItens->execute([':pedido' => $pedido['id']]);
        $itens = $stmtItens->fetchAll();
        ?>
        <div class="card mb-3">
            <div class="card-header d-flex flex-wrap justify-content-between gap-2">
                <span class="fw-semibold">Pedido #<?= (int) $pedido['id'] ?></span>
                <span class="text-muted small">
                    <?= e(date('d/m/Y H:i', strtotime($pedido['data_pedido']))) ?>
                    - <?= e(FORMAS_PAGAMENTO[$pedido['forma_pagamento']]) ?>
                </span>
            </div>
            <ul class="list-group list-group-flush">
                <?php foreach ($itens as $item): ?>
                    <li class="list-group-item d-flex justify-content-between">
                        <span><?= (int) $item['quantidade'] ?>x <?= e($item['nome']) ?></span>
                        <span><?= e(formatarPreco($item['preco_unitario'] * $item['quantidade'])) ?></span>
                    </li>
                <?php endforeach; ?>
                <li class="list-group-item d-flex justify-content-between fw-bold bg-light">
                    <span>Total</span>
                    <span><?= e(formatarPreco($pedido['total'])) ?></span>
                </li>
            </ul>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
