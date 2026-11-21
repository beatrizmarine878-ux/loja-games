<?php
require_once __DIR__ . '/../includes/init.php';

exigirAdmin();

$pdo = conectar();

// Todos os pedidos, com o nome do cliente
$pedidos = $pdo->query(
    "SELECT p.id, p.data_pedido, p.forma_pagamento, p.total, u.nome AS cliente, u.email
     FROM pedidos p
     JOIN usuarios u ON u.id = p.usuario_id
     ORDER BY p.data_pedido DESC, p.id DESC"
)->fetchAll();

// Consulta dos itens (preparada uma vez, usada em cada pedido)
$stmtItens = $pdo->prepare(
    "SELECT i.quantidade, i.preco_unitario, pr.nome
     FROM itens_pedido i
     JOIN produtos pr ON pr.id = i.produto_id
     WHERE i.pedido_id = :pedido"
);

$tituloPagina = 'Pedidos (admin)';
$menuAtivo = 'pedidos';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3">Pedidos realizados</h1>
<?php include __DIR__ . '/_menu.php'; ?>

<?php if (count($pedidos) === 0): ?>
    <div class="alert alert-info">Ainda não há pedidos.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>Pedido</th>
                    <th>Data</th>
                    <th>Cliente</th>
                    <th>Pagamento</th>
                    <th class="text-end">Total</th>
                    <th class="text-center">Itens</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pedidos as $pedido): ?>
                    <?php
                    $stmtItens->execute([':pedido' => $pedido['id']]);
                    $itens = $stmtItens->fetchAll();
                    $idCollapse = 'itens-' . (int) $pedido['id'];
                    ?>
                    <tr>
                        <td>#<?= (int) $pedido['id'] ?></td>
                        <td><?= e(date('d/m/Y H:i', strtotime($pedido['data_pedido']))) ?></td>
                        <td>
                            <?= e($pedido['cliente']) ?><br>
                            <span class="small text-muted"><?= e($pedido['email']) ?></span>
                        </td>
                        <td><?= e(FORMAS_PAGAMENTO[$pedido['forma_pagamento']]) ?></td>
                        <td class="text-end fw-semibold"><?= e(formatarPreco($pedido['total'])) ?></td>
                        <td class="text-center">
                            <button class="btn btn-outline-primary btn-sm" type="button"
                                    data-bs-toggle="collapse" data-bs-target="#<?= e($idCollapse) ?>"
                                    aria-expanded="false" aria-controls="<?= e($idCollapse) ?>">
                                Ver <?= count($itens) ?> item(ns)
                            </button>
                        </td>
                    </tr>
                    <tr class="collapse" id="<?= e($idCollapse) ?>">
                        <td colspan="6" class="bg-light">
                            <table class="table table-sm mb-0">
                                <thead>
                                    <tr>
                                        <th>Produto</th>
                                        <th class="text-center">Qtd.</th>
                                        <th class="text-end">Preço unit. (na compra)</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($itens as $item): ?>
                                        <tr>
                                            <td><?= e($item['nome']) ?></td>
                                            <td class="text-center"><?= (int) $item['quantidade'] ?></td>
                                            <td class="text-end"><?= e(formatarPreco($item['preco_unitario'])) ?></td>
                                            <td class="text-end"><?= e(formatarPreco($item['preco_unitario'] * $item['quantidade'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
