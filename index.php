<?php
require_once __DIR__ . '/includes/init.php';

$pdo = conectar();
$tituloPagina = 'Loja';

// Filtros que vêm da URL (?busca=...&categoria=...)
$busca       = trim($_GET['busca'] ?? '');
$categoriaId = (int) ($_GET['categoria'] ?? 0);

// Categorias para os botões de filtro
$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();

// Monta a consulta. Só texto fixo vai na string SQL;
// os valores digitados pelo usuário entram sempre por parâmetros (prepared statement).
$sql = "SELECT p.id, p.nome, p.preco, p.estoque, p.imagem, c.nome AS categoria_nome
        FROM produtos p
        JOIN categorias c ON c.id = p.categoria_id
        WHERE p.ativo = 1";
$parametros = [];

if ($busca !== '') {
    // o mesmo valor é usado em dois campos, por isso dois nomes de parâmetro
    $sql .= " AND (p.nome LIKE :busca_nome OR p.descricao LIKE :busca_desc)";
    $parametros[':busca_nome'] = '%' . $busca . '%';
    $parametros[':busca_desc'] = '%' . $busca . '%';
}

if ($categoriaId > 0) {
    $sql .= " AND p.categoria_id = :categoria";
    $parametros[':categoria'] = $categoriaId;
}

$sql .= " ORDER BY p.nome";

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$produtos = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="p-4 mb-4 rounded-3 text-white" style="background: linear-gradient(120deg, #3b1a78, #7c3aed);">
    <h1 class="h2 mb-1">Suba de nível o seu setup</h1>
    <p class="mb-0">Periféricos, monitores, cadeiras e jogos com os melhores preços.</p>
</div>

<!-- Filtro por categoria -->
<div class="mb-4 d-flex flex-wrap gap-2 align-items-center">
    <span class="fw-semibold me-2">Categorias:</span>
    <a href="<?= e(url('index.php' . ($busca !== '' ? '?busca=' . urlencode($busca) : ''))) ?>"
       class="btn btn-sm <?= $categoriaId === 0 ? 'btn-dark' : 'btn-outline-dark' ?>">Todas</a>

    <?php foreach ($categorias as $cat): ?>
        <?php
        $link = 'index.php?categoria=' . (int) $cat['id'];
        if ($busca !== '') {
            $link .= '&busca=' . urlencode($busca);
        }
        ?>
        <a href="<?= e(url($link)) ?>"
           class="btn btn-sm <?= $categoriaId === (int) $cat['id'] ? 'btn-dark' : 'btn-outline-dark' ?>">
            <?= e($cat['nome']) ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($busca !== ''): ?>
    <p class="text-muted">
        Resultados para <strong>"<?= e($busca) ?>"</strong>
        (<?= count($produtos) ?> produto<?= count($produtos) === 1 ? '' : 's' ?>)
    </p>
<?php endif; ?>

<?php if (count($produtos) === 0): ?>
    <div class="alert alert-info">
        Nenhum produto encontrado. <a href="<?= e(url('index.php')) ?>">Ver todos os produtos</a>
    </div>
<?php else: ?>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
        <?php foreach ($produtos as $p): ?>
            <div class="col">
                <div class="card card-produto h-100">
                    <a href="<?= e(url('produto.php?id=' . (int) $p['id'])) ?>">
                        <img src="<?= e(imagemProduto($p['imagem'])) ?>" class="card-img-top img-produto"
                             alt="<?= e($p['nome']) ?>">
                    </a>
                    <div class="card-body d-flex flex-column">
                        <span class="small text-muted"><?= e($p['categoria_nome']) ?></span>
                        <h2 class="h6 card-title mt-1">
                            <a href="<?= e(url('produto.php?id=' . (int) $p['id'])) ?>"
                               class="text-decoration-none text-dark"><?= e($p['nome']) ?></a>
                        </h2>
                        <div class="preco mb-2"><?= e(formatarPreco($p['preco'])) ?></div>
                        <div class="mb-3"><?= badgeEstoque($p['estoque']) ?></div>

                        <div class="mt-auto d-grid gap-2">
                            <a href="<?= e(url('produto.php?id=' . (int) $p['id'])) ?>"
                               class="btn btn-outline-primary btn-sm">Ver detalhes</a>

                            <form action="<?= e(url('carrinho.php')) ?>" method="post">
                                <?= campoCsrf() ?>
                                <input type="hidden" name="acao" value="adicionar">
                                <input type="hidden" name="produto_id" value="<?= (int) $p['id'] ?>">
                                <input type="hidden" name="quantidade" value="1">
                                <button type="submit" class="btn btn-warning btn-sm w-100"
                                    <?= $p['estoque'] < 1 ? 'disabled' : '' ?>>
                                    <?= $p['estoque'] < 1 ? 'Indisponível' : 'Adicionar ao carrinho' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
