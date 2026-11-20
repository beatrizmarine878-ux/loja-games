<?php
require_once __DIR__ . '/includes/init.php';

exigirLogin('meus_pedidos.php');

$pdo = conectar();
$pedidoId = (int) ($_GET['id'] ?? 0);

// Só mostra o pedido se ele for do usuário logado
$stmt = $pdo->prepare("SELECT id, data_pedido, forma_pagamento, total FROM pedidos WHERE id = :id AND usuario_id = :usuario");
$stmt->execute([':id' => $pedidoId, ':usuario' => usuarioLogado()['id']]);
$pedido = $stmt->fetch();

if (!$pedido) {
    setFlash('danger', 'Pedido não encontrado.');
    redirecionar('meus_pedidos.php');
}

$stmt = $pdo->prepare(
    "SELECT i.quantidade, i.preco_unitario, p.nome
     FROM itens_pedido i
     JOIN produtos p ON p.id = i.produto_id
     WHERE i.pedido_id = :pedido"
);
$stmt->execute([':pedido' => $pedidoId]);
$itens = $stmt->fetchAll();

$tituloPagina = 'Pedido confirmado';
include __DIR__ . '/includes/header.php';
?>

<div class="text-center mb-4">
    <div class="display-4">✅</div>
    <h1 class="h3">Pedido #<?= (int) $pedido['id'] ?> confirmado!</h1>
    <p class="text-muted mb-0">Obrigado pela compra, <?= e(explode(' ', usuarioLogado()['nome'])[0]) ?>!</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">

        <div class="card mb-3">
            <div class="card-header fw-semibold">Pagamento (simulado)</div>
            <div class="card-body">
                <p class="mb-1">
                    Forma de pagamento: <strong><?= e(FORMAS_PAGAMENTO[$pedido['forma_pagamento']]) ?></strong>
                </p>

                <?php if ($pedido['forma_pagamento'] === 'pix'): ?>
                    <p class="small mb-1">Código PIX (copia e cola) de exemplo:</p>
                    <code class="d-block p-2 bg-light border rounded text-break">00020126360014BR.GOV.BCB.PIX0114LEVELUPSIMULADO5204000053039865802BR5909LEVELUP6009SAOPAULO62070503***6304ABCD</code>
                <?php elseif ($pedido['forma_pagamento'] === 'boleto'): ?>
                    <p class="small mb-1">Linha digitável de exemplo:</p>
                    <code class="d-block p-2 bg-light border rounded text-break">00190.00009 01234.567890 12345.678901 1 00000000000000</code>
                <?php else: ?>
                    <p class="small mb-0">Pagamento no cartão de crédito aprovado.</p>
                <?php endif; ?>

                <p class="small text-muted mt-2 mb-0">Nenhum valor foi cobrado de verdade: isto é só uma simulação.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header fw-semibold">
                Itens do pedido - <?= e(date('d/m/Y H:i', strtotime($pedido['data_pedido']))) ?>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th class="text-center">Qtd.</th>
                            <th class="text-end">Preço unit.</th>
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
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="3" class="text-end">Total</th>
                            <th class="text-end"><?= e(formatarPreco($pedido['total'])) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3 justify-content-center">
            <a href="<?= e(url('meus_pedidos.php')) ?>" class="btn btn-outline-dark">Meus pedidos</a>
            <a href="<?= e(url('index.php')) ?>" class="btn btn-warning">Continuar comprando</a>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
