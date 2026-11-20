<?php
require_once __DIR__ . '/includes/init.php';

if (estaLogado()) {
    redirecionar('index.php');
}

$pdo = conectar();
$erros = [];
$nome = '';
$email = '';
$proximo = $_GET['proximo'] ?? ($_POST['proximo'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!validarCsrf()) {
        $erros[] = 'Sua sessão expirou. Recarregue a página e tente de novo.';
    } else {
        $nome      = trim($_POST['nome'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $senha     = $_POST['senha'] ?? '';
        $confirmar = $_POST['confirmar_senha'] ?? '';

        // ---- Validações ----
        if ($nome === '' || mb_strlen($nome) < 3) {
            $erros[] = 'Digite seu nome (mínimo 3 letras).';
        }
        if (mb_strlen($nome) > 100) {
            $erros[] = 'O nome pode ter no máximo 100 caracteres.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            $erros[] = 'Digite um e-mail válido.';
        }
        if (strlen($senha) < 6) {
            $erros[] = 'A senha precisa ter pelo menos 6 caracteres.';
        }
        if ($senha !== $confirmar) {
            $erros[] = 'As senhas não são iguais.';
        }

        // ---- E-mail já usado? ----
        if (count($erros) === 0) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetchColumn() > 0) {
                $erros[] = 'Já existe uma conta com esse e-mail.';
            }
        }

        // ---- Salva no banco ----
        if (count($erros) === 0) {
            // A senha NUNCA é salva em texto puro: password_hash gera o hash (bcrypt)
            $hash = password_hash($senha, PASSWORD_DEFAULT);

            try {
                // o perfil é sempre "cliente": ninguém vira admin pelo cadastro
                $stmt = $pdo->prepare(
                    "INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES (:nome, :email, :senha_hash, 'cliente')"
                );
                $stmt->execute([
                    ':nome'       => $nome,
                    ':email'      => $email,
                    ':senha_hash' => $hash,
                ]);

                // já deixa o usuário logado depois de cadastrar
                session_regenerate_id(true);
                $_SESSION['usuario'] = [
                    'id'     => (int) $pdo->lastInsertId(),
                    'nome'   => $nome,
                    'email'  => $email,
                    'perfil' => 'cliente',
                ];

                setFlash('success', 'Cadastro feito com sucesso! Bem-vindo(a), ' . $nome . '.');
                redirecionar(destinoSeguro($proximo, 'index.php'));

            } catch (PDOException $e) {
                // 23000 = violação de UNIQUE (alguém cadastrou o mesmo e-mail ao mesmo tempo)
                if ($e->getCode() === '23000') {
                    $erros[] = 'Já existe uma conta com esse e-mail.';
                } else {
                    error_log('Erro no cadastro: ' . $e->getMessage());
                    $erros[] = 'Não foi possível concluir o cadastro. Tente novamente.';
                }
            }
        }
    }
}

$tituloPagina = 'Cadastro';
include __DIR__ . '/includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h1 class="h4 mb-3">Criar conta</h1>

                <?php if (count($erros) > 0): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($erros as $erro): ?>
                                <li><?= e($erro) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(url('cadastro.php')) ?>" novalidate>
                    <?= campoCsrf() ?>
                    <input type="hidden" name="proximo" value="<?= e($proximo) ?>">

                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome completo</label>
                        <input type="text" class="form-control" id="nome" name="nome"
                               value="<?= e($nome) ?>" maxlength="100" required autofocus>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email"
                               value="<?= e($email) ?>" maxlength="150" required>
                    </div>

                    <div class="mb-3">
                        <label for="senha" class="form-label">Senha (mínimo 6 caracteres)</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="senha" name="senha" minlength="6" required>
                            <button type="button" class="btn btn-outline-secondary" data-mostrar-senha="senha">Mostrar</button>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="confirmar_senha" class="form-label">Confirmar senha</label>
                        <input type="password" class="form-control" id="confirmar_senha" name="confirmar_senha" required>
                    </div>

                    <button type="submit" class="btn btn-warning w-100">Cadastrar</button>
                </form>

                <p class="mt-3 mb-0 small">
                    Já tem conta?
                    <a href="<?= e(url('login.php' . ($proximo !== '' ? '?proximo=' . urlencode($proximo) : ''))) ?>">Entrar</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
