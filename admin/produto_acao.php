<?php
require_once __DIR__ . '/../includes/init.php';

exigirAdmin();

$pdo = conectar();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirecionar('admin/produtos.php');
}

if (!validarCsrf()) {
    setFlash('danger', 'Sua sessão expirou. Recarregue a página e tente novamente.');
    redirecionar('admin/produtos.php');
}

$acao = $_POST['acao'] ?? '';
$id = (int) ($_POST['id'] ?? 0);

if ($id <= 0) {
    setFlash('danger', 'Produto inválido.');
    redirecionar('admin/produtos.php');
}

try {
    if ($acao === 'excluir') {

        $stmt = $pdo->prepare("SELECT imagem FROM produtos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $produto = $stmt->fetch();

        if (!$produto) {
            setFlash('danger', 'Produto não encontrado.');
            redirecionar('admin/produtos.php');
        }

        $stmt = $pdo->prepare("DELETE FROM produtos WHERE id = :id");
        $stmt->execute([':id' => $id]);

        if (!empty($produto['imagem'])) {
            $caminhoImagem = __DIR__ . '/../assets/img/produtos/' . basename($produto['imagem']);

            if (is_file($caminhoImagem)) {
                @unlink($caminhoImagem);
            }
        }

        setFlash('success', 'Produto excluído com sucesso.');

    } elseif ($acao === 'ativar') {

        $stmt = $pdo->prepare("UPDATE produtos SET ativo = 1 WHERE id = :id");
        $stmt->execute([':id' => $id]);

        setFlash('success', 'Produto ativado com sucesso.');

    } elseif ($acao === 'desativar') {

        $stmt = $pdo->prepare("UPDATE produtos SET ativo = 0 WHERE id = :id");
        $stmt->execute([':id' => $id]);

        setFlash('success', 'Produto desativado com sucesso.');

    } else {
        setFlash('danger', 'Ação inválida.');
    }

} catch (PDOException $e) {
    error_log('Erro na ação do produto: ' . $e->getMessage());

    setFlash(
        'danger',
        'Não foi possível realizar a ação. O produto pode estar sendo usado em um pedido.'
    );
}

redirecionar('admin/produtos.php');
