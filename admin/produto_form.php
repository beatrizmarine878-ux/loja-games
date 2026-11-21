<?php
require_once __DIR__ . '/../includes/init.php';

exigirAdmin();

$pdo = conectar();

// Se vier ?id=... é edição; se não vier, é cadastro novo
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

// valores iniciais do formulário (cadastro novo)
$produto = [
    'nome'         => '',
    'descricao'    => '',
    'preco'        => '',
    'estoque'      => 0,
    'categoria_id' => 0,
    'imagem'       => '',
    'ativo'        => 1,
];

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $encontrado = $stmt->fetch();

    if (!$encontrado) {
        setFlash('danger', 'Produto não encontrado.');
        redirecionar('admin/produtos.php');
    }
    $produto = $encontrado;
}

$categorias = $pdo->query("SELECT id, nome FROM categorias ORDER BY nome")->fetchAll();
$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validarCsrf()) {
        $erros[] = 'Sua sessão expirou. Recarregue a página e tente de novo.';
    } else {
        $nome        = trim($_POST['nome'] ?? '');
        $descricao   = trim($_POST['descricao'] ?? '');
        $precoTexto  = str_replace(',', '.', trim($_POST['preco'] ?? ''));
        $estoqueTexto = trim($_POST['estoque'] ?? '');
        $categoriaId = (int) ($_POST['categoria_id'] ?? 0);
        $ativo       = isset($_POST['ativo']) ? 1 : 0;

        $produto['nome']         = $nome;
        $produto['descricao']    = $descricao;
        $produto['preco']        = $precoTexto;
        $produto['estoque']      = $estoqueTexto;
        $produto['categoria_id'] = $categoriaId;
        $produto['ativo']        = $ativo;

        if ($nome === '') {
            $erros[] = 'Informe o nome do produto.';
        } elseif (mb_strlen($nome) > 150) {
            $erros[] = 'O nome pode ter no máximo 150 caracteres.';
        }

        if (!is_numeric($precoTexto) || (float) $precoTexto < 0 || (float) $precoTexto > 99999999.99) {
            $erros[] = 'Informe um preço válido (ex.: 49,90).';
        }

        $estoque = filter_var($estoqueTexto, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
        if ($estoque === false) {
            $erros[] = 'O estoque precisa ser um número inteiro maior ou igual a zero.';
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categorias WHERE id = :id");
        $stmt->execute([':id' => $categoriaId]);
        if ($stmt->fetchColumn() == 0) {
            $erros[] = 'Escolha uma categoria.';
        }

        $novaImagem = null;
        if (count($erros) === 0) {
            $erroImagem = '';
            $novaImagem = processarUploadImagem('imagem', $erroImagem);
            if ($erroImagem !== '') {
                $erros[] = $erroImagem;
            }
        }

        if (count($erros) === 0) {
            $preco = round((float) $precoTexto, 2);

            $imagemAtual = !empty($produto['imagem']) ? $produto['imagem'] : null;
            $imagemFinal = $novaImagem ?? $imagemAtual;

            try {
                if ($id > 0) {
                    $stmt = $pdo->prepare(
                        "UPDATE produtos
                         SET categoria_id = :categoria, nome = :nome, descricao = :descricao,
                             preco = :preco, estoque = :estoque, imagem = :imagem, ativo = :ativo
                         WHERE id = :id"
                    );
                    $stmt->execute([
                        ':categoria' => $categoriaId,
                        ':nome'      => $nome,
                        ':descricao' => $descricao,
                        ':preco'     => $preco,
                        ':estoque'   => $estoque,
                        ':imagem'    => $imagemFinal,
                        ':ativo'     => $ativo,
                        ':id'        => $id,
                    ]);
                    setFlash('success', 'Produto atualizado com sucesso.');
                } else {
                    $stmt = $pdo->prepare(
                        "INSERT INTO produtos (categoria_id, nome, descricao, preco, estoque, imagem, ativo)
                         VALUES (:categoria, :nome, :descricao, :preco, :estoque, :imagem, :ativo)"
                    );
                    $stmt->execute([
                        ':categoria' => $categoriaId,
                        ':nome'      => $nome,
                        ':descricao' => $descricao,
                        ':preco'     => $preco,
                        ':estoque'   => $estoque,
                        ':imagem'    => $imagemFinal,
                        ':ativo'     => $ativo,
                    ]);
                    setFlash('success', 'Produto cadastrado com sucesso.');
                }

                redirecionar('admin/produtos.php');

            } catch (PDOException $e) {
                error_log('Erro ao salvar produto: ' . $e->getMessage());
                $erros[] = 'Não foi possível salvar o produto. Tente novamente.';
            }
        }
    }
}

$editando = $id > 0;
$tituloPagina = $editando ? 'Editar produto' : 'Cadastrar produto';
$menuAtivo = 'produtos';
include __DIR__ . '/../includes/header.php';
?>

<h1 class="h3 mb-3"><?= $editando ? 'Editar produto #' . (int) $id : 'Cadastrar produto' ?></h1>
<?php include __DIR__ . '/_menu.php'; ?>

<?php if (count($erros) > 0): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($erros as $erro): ?>
                <li><?= e($erro) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="<?= e(url('admin/produto_form.php')) ?>" enctype="multipart/form-data" novalidate>
    <?= campoCsrf() ?>
    <input type="hidden" name="id" value="<?= (int) $id ?>">

    <div class="row g-3">
        <div class="col-md-8">
            <label for="nome" class="form-label">Nome do produto</label>
            <input type="text" class="form-control" id="nome" name="nome" maxlength="150"
                   value="<?= e($produto['nome']) ?>" required>
        </div>

        <div class="col-md-4">
            <label for="categoria_id" class="form-label">Categoria</label>
            <select class="form-select" id="categoria_id" name="categoria_id" required>
                <option value="">Selecione...</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>"
                        <?= (int) $produto['categoria_id'] === (int) $cat['id'] ? 'selected' : '' ?>>
                        <?= e($cat['nome']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="col-12">
            <label for="descricao" class="form-label">Descrição</label>
            <textarea class="form-control" id="descricao" name="descricao" rows="4"><?= e($produto['descricao']) ?></textarea>
        </div>

        <div class="col-md-4">
            <label for="preco" class="form-label">Preço (R$)</label>
            <input type="text" class="form-control" id="preco" name="preco" inputmode="decimal"
                   placeholder="0,00" value="<?= e($produto['preco']) ?>" required>
        </div>

        <div class="col-md-4">
            <label for="estoque" class="form-label">Estoque (unidades)</label>
            <input type="number" class="form-control" id="estoque" name="estoque" min="0" step="1"
                   value="<?= e($produto['estoque']) ?>" required>
        </div>

        <div class="col-md-4">
            <label for="imagem" class="form-label">Imagem (JPG, PNG, WEBP ou GIF, até 2 MB)</label>
            <input type="file" class="form-control" id="imagem" name="imagem"
                   accept="image/jpeg,image/png,image/webp,image/gif">
        </div>

        <?php if ($editando && !empty($produto['imagem'])): ?>
            <div class="col-12">
                <span class="form-text d-block mb-1">Imagem atual (envie outra acima para trocar):</span>
                <img src="<?= e(imagemProduto($produto['imagem'])) ?>" class="img-thumbnail" style="max-width: 200px;" alt="Imagem atual">
            </div>
        <?php endif; ?>

        <div class="col-12">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1"
                    <?= $produto['ativo'] ? 'checked' : '' ?>>
                <label class="form-check-label" for="ativo">Produto ativo (aparece na loja)</label>
            </div>
        </div>
    </div>

    <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-warning"><?= $editando ? 'Salvar alterações' : 'Cadastrar produto' ?></button>
        <a href="<?= e(url('admin/produtos.php')) ?>" class="btn btn-outline-secondary">Cancelar</a>
    </div>
</form>

<?php include __DIR__ . '/../includes/footer.php'; ?>
