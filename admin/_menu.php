<?php
// Menu do painel admin. Cada página define $menuAtivo antes de incluir este arquivo.
$menuAtivo = $menuAtivo ?? '';
?>
<ul class="nav nav-pills menu-admin mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $menuAtivo === 'painel' ? 'active' : '' ?>"
           href="<?= e(url('admin/index.php')) ?>">Painel</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $menuAtivo === 'produtos' ? 'active' : '' ?>"
           href="<?= e(url('admin/produtos.php')) ?>">Produtos</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $menuAtivo === 'pedidos' ? 'active' : '' ?>"
           href="<?= e(url('admin/pedidos.php')) ?>">Pedidos</a>
    </li>
</ul>
