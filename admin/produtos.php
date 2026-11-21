<?php
require_once __DIR__ . '/../includes/init.php';

exigirAdmin();

$pdo = conectar();

// No admin aparecem TODOS os produtos, inclusive os desativados
$produtos = $pdo->query(
    "SELECT p.id, p.nome, p.preco, p.estoque, p.imagem, p.ativo, c.nome AS categoria_nome
     FROM produtos p
     JOIN categorias c ON c.id = p.categoria_id
     ORDER BY p.ativo DESC, p.nome"
)->fetchAll();

$tituloPagina = 'Produtos (admin)';
$menuAtivo = 'produtos';
include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Produtos</h1>
    <a href="<?= e(url('admin/produto_form.php')) ?>" class="btn btn-warning">+ Cadastrar produto</a>
</div>

<?php include __DIR__ . '/_menu.php'; ?>

<div class="table-responsive">
    <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
            <tr>
                <th>ID</th>
                <th>Produto</th>
                <th>Categoria</th>
                <th class="text-end">Preço</th>
                <th class="text-center">Estoque</th>
                <th class="text-center">Situação</th>
                <th class="text-center">Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produtos as $p): ?>
                <tr class="<?= $p['ativo'] ? '' : 'table-secondary' ?>">
                    <td><?= (int) $p['id'] ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <img src="<?= e(imagemProduto($p['imagem'])) ?>" class="img-mini" alt="">
                            <?= e($p['nome']) ?>
                        </div>
                    </td>
                    <td><?= e($p['categoria_nome']) ?></td>
                    <td class="text-end"><?= e(formatarPreco($p['preco'])) ?></td>
                    <td class="text-center"><?= (int) $p['estoque'] ?></td>
                    <td class="text-center">
                        <?php if ($p['ativo']): ?>
                            <span class="badge bg-success">Ativo</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Desativado</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="d-flex gap-1 justify-content-center">
                            <a href="<?= e(url('admin/produto_form.php?id=' . (int) $p['id'])) ?>"
                               class="btn btn-outline-primary btn-sm">Editar</a>

                            <?php if ($p['ativo']): ?>
                                <form action="<?= e(url('admin/produto_acao.php')) ?>" method="post"
                                      data-confirmar="Desativar este produto? Ele some da loja, mas o histórico de vendas continua.">
                                    <?= campoCsrf() ?>
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                    <input type="hidden" name="acao" value="desativar">
                                    <button type="submit" class="btn btn-outline-secondary btn-sm">Desativar</button>
                                </form>
                            <?php else: ?>
                                <form action="<?= e(url('admin/produto_acao.php')) ?>" method="post">
                                    <?= campoCsrf() ?>
                                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                    <input type="hidden" name="acao" value="ativar">
                                    <button type="submit" class="btn btn-outline-success btn-sm">Reativar</button>
                                </form>
                            <?php endif; ?>

                            <form action="<?= e(url('admin/produto_acao.php')) ?>" method="post"
                                  data-confirmar="Excluir este produto? Se ele já foi vendido, será apenas desativado.">
                                <?= campoCsrf() ?>
                                <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                <input type="hidden" name="acao" value="excluir">
                                <button type="submit" class="btn btn-outline-danger btn-sm">Excluir</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
