<?php
require_once __DIR__ . '/includes/init.php';

// Só usuário logado pode finalizar a compra
exigirLogin('checkout.php');

$pdo = conectar();

// -----------------------------------------------------------
// Finalizar pedido (POST)
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validarCsrf()) {
        setFlash('danger', 'Sua sessão expirou. Tente de novo.');
        redirecionar('checkout.php');
    }

    $forma = $_POST['forma_pagamento'] ?? '';

    // só aceita PIX, boleto ou cartão
    if (!array_key_exists($forma, FORMAS_PAGAMENTO)) {
        setFlash('danger', 'Escolha uma forma de pagamento.');
        redirecionar('checkout.php');
    }

    $carrinho = $_SESSION['carrinho'] ?? [];
    if (count($carrinho) === 0) {
        setFlash('warning', 'Seu carrinho está vazio.');
        redirecionar('carrinho.php');
    }

    // ordena pelo id do produto: assim, se duas compras acontecerem ao mesmo
    // tempo, os produtos são travados sempre na mesma ordem (evita deadlock)
    ksort($carrinho);

    try {
        // TRANSAÇÃO: ou grava tudo (pedido + itens + estoque) ou não grava nada
        $pdo->beginTransaction();

        $linhas = [];
        $total = 0.0;

        // FOR UPDATE trava a linha do produto até o fim da transação,
        // assim ninguém compra a mesma unidade ao mesmo tempo
        $stmtProduto = $pdo->prepare(
            "SELECT id, nome, preco, estoque FROM produtos WHERE id = :id AND ativo = 1 FOR UPDATE"
        );

        foreach ($carrinho as $produtoId => $qtd) {
            $stmtProduto->execute([':id' => (int) $produtoId]);
            $produto = $stmtProduto->fetch();

            if (!$produto) {
                throw new RuntimeException('Um dos produtos do carrinho não está mais disponível.');
            }
            if ($qtd < 1 || $qtd > $produto['estoque']) {
                throw new RuntimeException(
                    'Estoque insuficiente para "' . $produto['nome'] . '". Restam ' . $produto['estoque'] . ' unidade(s).'
                );
            }

            // o preço vem do BANCO (nunca do navegador / sessão)
            $linhas[] = [
                'produto_id' => (int) $produto['id'],
                'quantidade' => (int) $qtd,
                'preco'      => $produto['preco'],
            ];
            $total += (float) $produto['preco'] * (int) $qtd;
        }

        $total = round($total, 2);

        // 1) Cria o registro do pedido
        $stmtPedido = $pdo->prepare(
            "INSERT INTO pedidos (usuario_id, forma_pagamento, total) VALUES (:usuario, :forma, :total)"
        );
        $stmtPedido->execute([
            ':usuario' => usuarioLogado()['id'],
            ':forma'   => $forma,
            ':total'   => $total,
        ]);
        $pedidoId = (int) $pdo->lastInsertId();

        // 2) Registra cada item (com o preço congelado) e dá baixa no estoque
        $stmtItem = $pdo->prepare(
            "INSERT INTO itens_pedido (pedido_id, produto_id, quantidade, preco_unitario)
             VALUES (:pedido, :produto, :qtd, :preco)"
        );
        $stmtEstoque = $pdo->prepare(
            "UPDATE produtos SET estoque = estoque - :qtd WHERE id = :id AND estoque >= :minimo"
        );

        foreach ($linhas as $linha) {
            $stmtItem->execute([
                ':pedido'  => $pedidoId,
                ':produto' => $linha['produto_id'],
                ':qtd'     => $linha['quantidade'],
                ':preco'   => $linha['preco'],
            ]);

            $stmtEstoque->execute([
                ':qtd'    => $linha['quantidade'],
                ':id'     => $linha['produto_id'],
                ':minimo' => $linha['quantidade'],
            ]);

            // se nenhuma linha foi alterada, o estoque não era suficiente
            if ($stmtEstoque->rowCount() !== 1) {
                throw new RuntimeException('Não foi possível reservar o estoque de um dos produtos.');
            }
        }

        // tudo certo: confirma a transação
        $pdo->commit();

        // 3) Limpa o carrinho da sessão
        unset($_SESSION['carrinho']);

        redirecionar('pedido_sucesso.php?id=' . $pedidoId);

    } catch (RuntimeException $e) {
        // erro de regra de negócio (estoque, produto indisponível...)
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        setFlash('danger', $e->getMessage());
        redirecionar('carrinho.php');

    } catch (PDOException $e) {
        // erro do banco: desfaz tudo e mostra mensagem genérica
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Erro ao finalizar pedido: ' . $e->getMessage());
        setFlash('danger', 'Ocorreu um erro ao finalizar o pedido. Nada foi cobrado, tente novamente.');
        redirecionar('checkout.php');
    }
}

// -----------------------------------------------------------
// Mostra a tela de checkout (GET)
// -----------------------------------------------------------
[$itens, $total] = carregarCarrinho($pdo);

if (count($itens) === 0) {
    setFlash('warning', 'Seu carrinho está vazio.');
    redirecionar('carrinho.php');
}

$tituloPagina = 'Finalizar compra';
include __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4">Finalizar compra</h1>

<div class="row g-4">
    <!-- Resumo do pedido -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header fw-semibold">Resumo do pedido</div>
            <div class="table-responsive">
                <table class="table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Produto</th>
                            <th class="text-center">Qtd.</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($itens as $item): ?>
                            <tr>
                                <td><?= e($item['produto']['nome']) ?></td>
                                <td class="text-center"><?= (int) $item['quantidade'] ?></td>
                                <td class="text-end"><?= e(formatarPreco($item['subtotal'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-light">
                            <th colspan="2" class="text-end">Total</th>
                            <th class="text-end fs-5"><?= e(formatarPreco($total)) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <a href="<?= e(url('carrinho.php')) ?>" class="btn btn-link mt-2">&larr; Voltar ao carrinho</a>
    </div>

    <!-- Pagamento (simulado) -->
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header fw-semibold">Forma de pagamento</div>
            <div class="card-body">
                <p class="small text-muted">
                    Comprando como <strong><?= e(usuarioLogado()['nome']) ?></strong>
                    (<?= e(usuarioLogado()['email']) ?>)
                </p>

                <form method="post" action="<?= e(url('checkout.php')) ?>">
                    <?= campoCsrf() ?>

                    <?php foreach (FORMAS_PAGAMENTO as $valor => $rotulo): ?>
                        <label class="opcao-pagamento border rounded p-3 mb-2 d-flex align-items-center gap-2 w-100"
                               for="pg_<?= e($valor) ?>">
                            <input type="radio" class="form-check-input mt-0" name="forma_pagamento"
                                   id="pg_<?= e($valor) ?>" value="<?= e($valor) ?>" required>
                            <span><?= e($rotulo) ?></span>
                        </label>
                    <?php endforeach; ?>

                    <div id="info-pagamento" class="alert alert-secondary small d-none mt-3"></div>

                    <button type="submit" class="btn btn-warning btn-lg w-100 mt-3">
                        Confirmar pagamento de <?= e(formatarPreco($total)) ?>
                    </button>
                </form>

                <p class="small text-muted mt-3 mb-0">
                    Simulação: nenhum pagamento real é feito e o pedido é confirmado imediatamente.
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
