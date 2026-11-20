<?php
require_once __DIR__ . '/includes/init.php';

// O logout só funciona por POST com token CSRF (o botão "Sair" do menu faz isso)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && validarCsrf()) {

    // limpa os dados da sessão
    $_SESSION = [];

    // apaga também o cookie da sessão no navegador
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    session_destroy();

    // abre uma sessão nova só para poder mostrar a mensagem de saída
    session_start();
    setFlash('info', 'Você saiu da sua conta.');
}

redirecionar('index.php');
