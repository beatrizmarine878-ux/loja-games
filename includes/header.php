<?php
// Cabeçalho que aparece em todas as páginas.
// Cada página pode definir $tituloPagina antes de incluir este arquivo.
$tituloPagina = $tituloPagina ?? 'Level Up Store';
$qtdCarrinho  = quantidadeNoCarrinho();
$usuario      = usuarioLogado();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($tituloPagina) ?> | Level Up Store</title>

    <!-- Bootstrap 5 (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(url('assets/css/style.css')) ?>">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark barra-topo">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= e(url('index.php')) ?>">🎮 Level Up</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal"
                aria-controls="menuPrincipal" aria-expanded="false" aria-label="Abrir menu">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuPrincipal">
            <!-- Busca de produtos -->
            <form class="d-flex my-2 my-lg-0 mx-lg-3 flex-grow-1" action="<?= e(url('index.php')) ?>" method="get" role="search">
                <input class="form-control me-2" type="search" name="busca" placeholder="Buscar produtos..."
                       value="<?= e($_GET['busca'] ?? '') ?>" aria-label="Buscar produtos">
                <button class="btn btn-warning" type="submit">Buscar</button>
            </form>

            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
                <li class="nav-item">
                    <a class="nav-link" href="<?= e(url('index.php')) ?>">Loja</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= e(url('carrinho.php')) ?>">
                        🛒 Carrinho
                        <span class="badge bg-warning text-dark" id="contador-carrinho"><?= (int) $qtdCarrinho ?></span>
                    </a>
                </li>

                <?php if ($usuario): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(url('meus_pedidos.php')) ?>">Meus pedidos</a>
                    </li>
                    <?php if (ehAdmin()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= e(url('admin/index.php')) ?>">Painel admin</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item text-light small px-lg-2">
                        Olá, <?= e(explode(' ', $usuario['nome'])[0]) ?>
                    </li>
                    <li class="nav-item">
                        <form action="<?= e(url('logout.php')) ?>" method="post" class="d-inline">
                            <?= campoCsrf() ?>
                            <button type="submit" class="btn btn-outline-light btn-sm">Sair</button>
                        </form>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= e(url('login.php')) ?>">Entrar</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-warning btn-sm" href="<?= e(url('cadastro.php')) ?>">Cadastrar</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container my-4 flex-grow-1">
    <?php mostrarFlash(); ?>
