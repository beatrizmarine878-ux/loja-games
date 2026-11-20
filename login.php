<?php
require_once __DIR__ . '/includes/init.php';

// Quem já está logado não precisa ver o login
if (estaLogado()) {
    redirecionar('index.php');
}

$pdo = conectar();
$erro = '';
$email = '';

// Página para onde ir depois do login (ex.: checkout.php)
$proximo = $_GET['proximo'] ?? ($_POST['proximo'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validarCsrf()) {
        $erro = 'Sua sessão expirou. Recarregue a página e tente de novo.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $senha = $_POST['senha'] ?? '';

        // Prepared statement: o e-mail nunca é concatenado na query
        $stmt = $pdo->prepare("SELECT id, nome, email, senha_hash, perfil FROM usuarios WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $usuario = $stmt->fetch();

        // password_verify compara a senha digitada com o hash salvo no banco
        if ($usuario && password_verify($senha, $usuario['senha_hash'])) {

            // gera um novo ID de sessão ao logar (evita session fixation)
            session_regenerate_id(true);

            $_SESSION['usuario'] = [
                'id'     => (int) $usuario['id'],
                'nome'   => $usuario['nome'],
                'email'  => $usuario['email'],
                'perfil' => $usuario['perfil'],
            ];

            $padrao = ($usuario['perfil'] === 'admin') ? 'admin/index.php' : 'index.php';
            redirecionar(destinoSeguro($proximo, $padrao));
        } else {
            // mesma mensagem para e-mail errado ou senha errada (não revela qual foi)
            $erro = 'E-mail ou senha incorretos.';
        }
    }
}

$tituloPagina = 'Entrar';
include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Entrar na sua conta</h1>

                <?php if ($erro !== ''): ?>
                    <div class="alert alert-danger"><?= e($erro) ?></div>
                <?php endif; ?>

                <form method="post" action="<?= e(url('login.php')) ?>" novalidate>
                    <?= campoCsrf() ?>
                    <input type="hidden" name="proximo" value="<?= e($proximo) ?>">

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= e($email) ?>" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="senha" class="form-label">Senha</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="senha" name="senha" required>
                            <button type="button" class="btn btn-outline-secondary" data-mostrar-senha="senha">Mostrar</button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning w-100">Entrar</button>
                </form>

                <p class="mt-3 mb-0 small">
                    Não tem conta?
                    <a href="<?= e(url('cadastro.php' . ($proximo !== '' ? '?proximo=' . urlencode($proximo) : ''))) ?>">Cadastre-se</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
