<?php
require_once __DIR__ . '/includes/init.php';

$pdo = conectar();

// -----------------------------------------------------------
// Ações do carrinho (todas chegam por POST)
// O carrinho fica em $_SESSION['carrinho'] no formato [id_produto => quantidade]
// -----------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validarCsrf()) {
        setFlash('danger', 'Sua sessão expirou. Tente de novo.');
        redirecionar('carrinho.php');
    }

    $acao      = $_POST['acao'] ?? '';
    $produtoId = (int) ($_POST['produto_id'] ?? 0);
    $quantidade = (int) ($_POST['quantidade'] ?? 1);

    if (!isset($_SESSION['carrinho'])) {
        $_SESSION['carrinho'] = [];
    }

    if ($acao === 'adicionar') {
        $produto = buscarProdutoAtivo($pdo, $produtoId);

        if (!$produto) {
            setFlash('danger', 'Produto não encontrado.');
        } elseif ($produto['estoque'] < 1) {
            setFlash('danger', 'Este produto está esgotado.');
        } else {
            if ($quantidade < 1) {
                $quantidade = 1;
            }

            // soma com o que já tinha no carrinho
            $jaTem = $_SESSION['carrinho'][$produtoId] ?? 0;
            $novaQtd = $jaTem + $quantidade;

            // não pode passar do estoque
            if ($novaQtd > $produto['estoque']) {
                $novaQtd = (int) $produto['estoque'];
                setFlash('warning', 'Limitamos a quantidade ao estoque disponível (' . $novaQtd . ' unidade(s)).');
            } else {
                setFlash('success', '"' . $produto['nome'] . '" foi adicionado ao carrinho.');
            }

            $_SESSION['carrinho'][$produtoId] = $novaQtd;
        }

    } elseif ($acao === 'atualizar') {
        $produto = buscarProdutoAtivo($pdo, $produtoId);

        if (!$produto || !isset($_SESSION['carrinho'][$produtoId])) {
            setFlash('danger', 'Item não encontrado no carrinho.');
        } elseif ($quantidade < 1) {
            // quantidade zero ou negativa = remove o item
            unset($_SESSION['carrinho'][$produtoId]);
            setFlash('info', 'Item removido do carrinho.');
        } else {
            if ($quantidade > $produto['estoque']) {
                $quantidade = (int) $produto['estoque'];
                setFlash('warning', 'Só temos ' . $quantidade . ' unidade(s) de "' . $produto['nome'] . '" em estoque.');
            } else {
                setFlash('success', 'Quantidade atualizada.');
            }
            $_SESSION['carrinho'][$produtoId] = $quantidade;
        }

    } elseif ($acao === 'remover') {
        unset($_SESSION['carrinho'][$produtoId]);
        setFlash('info', 'Item removido do carrinho.');

    } elseif ($acao === 'limpar') {
        $_SESSION['carrinho'] = [];
        setFlash('info', 'Carrinho esvaziado.');
    }

    // Redireciona para evitar reenvio do formulário ao atualizar a página (padrão PRG)
    redirecionar('carrinho.php');
}

// -----------------------------------------------------------
// Mostra o carrinho
// -----------------------------------------------------------
[$itens, $total] = carregarCarrinho($pdo);

$tituloPagina = 'Carrinho';
include __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4">Meu carrinho</h1>

<?php if (count($itens) === 0): ?>
    <div class="alert alert-info">
        Seu carrinho está vazio. <a href="<?= e(url('index.php')) ?>">Ir para a loja</a>
    </div>
<?php else: ?>

    <div class="table-responsive">
        <table class="table table-bordered align-middle bg-white">
            <thead class="table-light">
                <tr>
                    <th>Produto</th>
                    <th class="text-end">Preço unit.</th>
                    <th style="width: 230px;">Quantidade</th>
                    <th class="text-end">Subtotal</th>
                    <th class="text-center">Remover</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $item): ?>
                    <?php $p = $item['produto']; ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= e(imagemProduto($p['imagem'])) ?>" class="img-mini" alt="">
                                <a href="<?= e(url('produto.php?id=' . (int) $p['id'])) ?>"
                                   class="text-decoration-none"><?= e($p['nome']) ?></a>
                            </div>
                        </td>
                        <td class="text-end"><?= e(formatarPreco($p['preco'])) ?></td>
                        <td>
                            <form action="<?= e(url('carrinho.php')) ?>" method="post" class="d-flex gap-2">
                                <?= campoCsrf() ?>
                                <input type="hidden" name="acao" value="atualizar">
                                <input type="hidden" name="produto_id" value="<?= (int) $p['id'] ?>">
                                <input type="number" name="quantidade" class="form-control campo-qtd"
                                       value="<?= (int) $item['quantidade'] ?>" min="1"
                                       max="<?= (int) $p['estoque'] ?>" data-estoque="<?= (int) $p['estoque'] ?>"
                                       aria-label="Quantidade de <?= e($p['nome']) ?>">
                                <button type="submit" class="btn btn-outline-primary btn-sm">Atualizar</button>
                            </form>
                            <div class="form-text">Disponível: <?= (int) $p['estoque'] ?></div>
                        </td>
                        <td class="text-end fw-semibold"><?= e(formatarPreco($item['subtotal'])) ?></td>
                        <td class="text-center">
                            <form action="<?= e(url('carrinho.php')) ?>" method="post">
                                <?= campoCsrf() ?>
                                <input type="hidden" name="acao" value="remover">
                                <input type="hidden" name="produto_id" value="<?= (int) $p['id'] ?>">
                                <button type="submit" class="btn btn-outline-danger btn-sm">Remover</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-light">
                    <th colspan="3" class="text-end">Total geral</th>
                    <th class="text-end fs-5"><?= e(formatarPreco($total)) ?></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="d-flex flex-wrap justify-content-between gap-2">
        <div class="d-flex gap-2">
            <a href="<?= e(url('index.php')) ?>" class="btn btn-outline-dark">Continuar comprando</a>

            <form action="<?= e(url('carrinho.php')) ?>" method="post"
                  data-confirmar="Deseja esvaziar o carrinho?">
                <?= campoCsrf() ?>
                <input type="hidden" name="acao" value="limpar">
                <button type="submit" class="btn btn-outline-danger">Esvaziar carrinho</button>
            </form>
        </div>

        <a href="<?= e(url('checkout.php')) ?>" class="btn btn-warning btn-lg">Finalizar compra</a>
    </div>

<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
