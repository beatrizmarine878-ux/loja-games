<?php
require_once __DIR__ . '/includes/init.php';

$pdo = conectar();

$id = (int) ($_GET['id'] ?? 0);
$produto = $id > 0 ? buscarProdutoAtivo($pdo, $id) : false;

// Produto não existe ou está desativado
if (!$produto) {
    http_response_code(404);
    $tituloPagina = 'Produto não encontrado';
    include __DIR__ . '/includes/header.php';
    echo '<div class="alert alert-warning">Produto não encontrado. <a href="' . e(url('index.php')) . '">Voltar para a loja</a></div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$tituloPagina = $produto['nome'];
$estoque = (int) $produto['estoque'];

include __DIR__ . '/includes/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(url('index.php')) ?>">Loja</a></li>
        <li class="breadcrumb-item">
            <a href="<?= e(url('index.php?categoria=' . (int) $produto['categoria_id'])) ?>"><?= e($produto['categoria_nome']) ?></a>
        </li>
        <li class="breadcrumb-item active" aria-current="page"><?= e($produto['nome']) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <div class="col-md-6">
        <img src="<?= e(imagemProduto($produto['imagem'])) ?>" class="img-fluid rounded border"
             alt="<?= e($produto['nome']) ?>">
    </div>

    <div class="col-md-6">
        <span class="small text-muted">Código do produto: #<?= (int) $produto['id'] ?></span>
        <h1 class="h3 mt-1"><?= e($produto['nome']) ?></h1>
        <span class="badge text-bg-secondary mb-3"><?= e($produto['categoria_nome']) ?></span>

        <div class="preco-grande mb-2"><?= e(formatarPreco($produto['preco'])) ?></div>
        <p class="text-muted small">Preço unitário</p>

        <div class="mb-3"><?= badgeEstoque($estoque) ?></div>

        <h2 class="h6">Descrição</h2>
        <p><?= nl2br(e($produto['descricao'])) ?></p>

        <?php if ($estoque > 0): ?>
            <form action="<?= e(url('carrinho.php')) ?>" method="post" class="d-flex gap-2 align-items-center mt-4">
                <?= campoCsrf() ?>
                <input type="hidden" name="acao" value="adicionar">
                <input type="hidden" name="produto_id" value="<?= (int) $produto['id'] ?>">

                <label for="quantidade" class="form-label mb-0">Quantidade:</label>
                <input type="number" id="quantidade" name="quantidade" class="form-control campo-qtd"
                       value="1" min="1" max="<?= $estoque ?>" data-estoque="<?= $estoque ?>">

                <button type="submit" class="btn btn-warning">Adicionar ao carrinho</button>
            </form>
        <?php else: ?>
            <div class="alert alert-secondary mt-4">Este produto está esgotado no momento.</div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
