<?php
// Funções que são usadas em várias páginas do sistema.

// Formas de pagamento aceitas (chave = valor salvo no banco)
const FORMAS_PAGAMENTO = [
    'pix'    => 'PIX',
    'boleto' => 'Boleto',
    'cartao' => 'Cartão de crédito',
];

// ---------------------------------------------------------
// Utilidades gerais
// ---------------------------------------------------------

// Escapa texto antes de mostrar no HTML (evita XSS)
function e($texto)
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// Monta o endereço de uma página usando APP_BASE_URL do .env
function url($caminho = '')
{
    $base = rtrim(env('APP_BASE_URL', ''), '/');
    return $base . '/' . ltrim($caminho, '/');
}

function redirecionar($caminho)
{
    header('Location: ' . url($caminho));
    exit;
}

function formatarPreco($valor)
{
    return 'R$ ' . number_format((float) $valor, 2, ',', '.');
}

// Devolve o caminho da imagem do produto (ou uma imagem padrão)
function imagemProduto($arquivo)
{
    if (empty($arquivo)) {
        return url('assets/img/sem-imagem.png');
    }
    return url('assets/img/produtos/' . rawurlencode($arquivo));
}

// Etiqueta de disponibilidade de estoque
function badgeEstoque($estoque)
{
    $estoque = (int) $estoque;

    if ($estoque <= 0) {
        return '<span class="badge bg-danger">Esgotado</span>';
    }
    if ($estoque <= 5) {
        return '<span class="badge bg-warning text-dark">Últimas ' . $estoque . ' unidades</span>';
    }
    return '<span class="badge bg-success">Em estoque</span>';
}

// ---------------------------------------------------------
// Mensagens de aviso (flash) - aparecem uma vez e somem
// tipos do Bootstrap: success, danger, warning, info
// ---------------------------------------------------------
function setFlash($tipo, $mensagem)
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function mostrarFlash()
{
    if (empty($_SESSION['flash'])) {
        return;
    }

    foreach ($_SESSION['flash'] as $f) {
        echo '<div class="alert alert-' . e($f['tipo']) . ' alert-dismissible fade show" role="alert">';
        echo e($f['mensagem']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>';
        echo '</div>';
    }

    unset($_SESSION['flash']);
}

// ---------------------------------------------------------
// CSRF - token escondido nos formulários POST
// (impede que outro site envie formulários em nome do usuário)
// ---------------------------------------------------------
function tokenCsrf()
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function campoCsrf()
{
    return '<input type="hidden" name="csrf" value="' . e(tokenCsrf()) . '">';
}

function validarCsrf()
{
    $enviado = $_POST['csrf'] ?? '';
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $enviado);
}

// ---------------------------------------------------------
// Autenticação e controle de acesso (sempre validado no servidor)
// ---------------------------------------------------------
function usuarioLogado()
{
    return $_SESSION['usuario'] ?? null;
}

function estaLogado()
{
    return isset($_SESSION['usuario']);
}

function ehAdmin()
{
    return estaLogado() && $_SESSION['usuario']['perfil'] === 'admin';
}

// Só deixa passar se estiver logado; senão manda para o login
function exigirLogin($retorno = '')
{
    if (!estaLogado()) {
        setFlash('warning', 'Faça login ou cadastre-se para continuar.');
        $destino = 'login.php';
        if ($retorno !== '') {
            $destino .= '?proximo=' . urlencode($retorno);
        }
        redirecionar($destino);
    }
}

// Protege as páginas do /admin
function exigirAdmin()
{
    if (!estaLogado()) {
        setFlash('warning', 'Faça login para acessar o painel.');
        redirecionar('login.php?proximo=' . urlencode('admin/index.php'));
    }

    // confere no banco se o usuário ainda é admin (não confia só na sessão)
    $stmt = conectar()->prepare("SELECT perfil FROM usuarios WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['usuario']['id']]);
    $perfil = $stmt->fetchColumn();

    if ($perfil !== 'admin') {
        http_response_code(403);
        die('Acesso negado. Esta área é só para administradores. <a href="' . e(url('index.php')) . '">Voltar para a loja</a>');
    }
}

// Só aceita redirecionar para páginas conhecidas (evita "open redirect")
function destinoSeguro($proximo, $padrao = 'index.php')
{
    $permitidos = ['index.php', 'carrinho.php', 'checkout.php', 'meus_pedidos.php', 'admin/index.php'];
    return in_array($proximo, $permitidos, true) ? $proximo : $padrao;
}

// ---------------------------------------------------------
// Produtos e carrinho
// ---------------------------------------------------------

// Busca um produto ativo pelo id (ou false se não existir)
function buscarProdutoAtivo($pdo, $id)
{
    $sql = "SELECT p.*, c.nome AS categoria_nome
            FROM produtos p
            JOIN categorias c ON c.id = p.categoria_id
            WHERE p.id = :id AND p.ativo = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

// Soma a quantidade de todos os itens (número que aparece no menu)
function quantidadeNoCarrinho()
{
    $total = 0;
    foreach ($_SESSION['carrinho'] ?? [] as $qtd) {
        $total += (int) $qtd;
    }
    return $total;
}

// Lê o carrinho da sessão e busca os dados REAIS no banco (preço e estoque).
// Retorna [lista de itens, total geral].
// O carrinho na sessão guarda só: [id_produto => quantidade]
function carregarCarrinho($pdo)
{
    $itens = [];
    $total = 0.0;

    if (empty($_SESSION['carrinho'])) {
        return [$itens, $total];
    }

    foreach ($_SESSION['carrinho'] as $produtoId => $qtd) {
        $produto = buscarProdutoAtivo($pdo, (int) $produtoId);

        // produto sumiu, foi desativado ou acabou o estoque -> tira do carrinho
        if (!$produto || $produto['estoque'] < 1) {
            unset($_SESSION['carrinho'][$produtoId]);
            setFlash('warning', 'Um item foi removido do carrinho porque não está mais disponível.');
            continue;
        }

        // quantidade maior que o estoque atual -> ajusta
        if ($qtd > $produto['estoque']) {
            $qtd = (int) $produto['estoque'];
            $_SESSION['carrinho'][$produtoId] = $qtd;
            setFlash('warning', 'A quantidade de "' . $produto['nome'] . '" foi ajustada ao estoque disponível.');
        }

        $subtotal = (float) $produto['preco'] * $qtd;
        $total += $subtotal;

        $itens[] = [
            'produto'    => $produto,
            'quantidade' => (int) $qtd,
            'subtotal'   => $subtotal,
        ];
    }

    return [$itens, round($total, 2)];
}

// ---------------------------------------------------------
// Upload de imagem do produto (usado no painel admin)
// Devolve o nome do arquivo salvo, ou null se não enviou / deu erro.
// Se der erro, preenche $erro com a mensagem.
// ---------------------------------------------------------
function processarUploadImagem($campo, &$erro)
{
    if (!isset($_FILES[$campo]) || $_FILES[$campo]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $arquivo = $_FILES[$campo];

    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        $erro = 'Deu erro no envio da imagem. Tente de novo.';
        return null;
    }

    if ($arquivo['size'] > 2 * 1024 * 1024) {
        $erro = 'A imagem pode ter no máximo 2 MB.';
        return null;
    }

    $info = @getimagesize($arquivo['tmp_name']);
    $permitidos = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    if ($info === false || !isset($permitidos[$info['mime']])) {
        $erro = 'Envie uma imagem JPG, PNG, WEBP ou GIF.';
        return null;
    }

    $nome = bin2hex(random_bytes(8)) . '.' . $permitidos[$info['mime']];
    $pasta = __DIR__ . '/../assets/img/produtos/';

    if (!is_dir($pasta)) {
        mkdir($pasta, 0755, true);
    }

    if (!move_uploaded_file($arquivo['tmp_name'], $pasta . $nome)) {
        $erro = 'Não consegui salvar a imagem no servidor.';
        return null;
    }

    return $nome;
}
