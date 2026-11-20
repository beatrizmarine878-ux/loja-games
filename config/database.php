<?php
// Conexão com o banco usando PDO.
// Nenhum dado de acesso fica escrito aqui: tudo vem do arquivo .env

function conectar()
{
    // "static" faz a conexão ser criada só uma vez por requisição
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    // Usuário, senha e banco vêm SÓ do .env (sem valor padrão no código)
    $host    = env('DB_HOST');
    $porta   = env('DB_PORT', '3306');
    $banco   = env('DB_NAME');
    $usuario = env('DB_USER');
    $senha   = env('DB_PASS', '');
    $charset = env('DB_CHARSET', 'utf8mb4');

    if (empty($host) || empty($banco) || empty($usuario)) {
        die('Configuração do banco incompleta. Confira DB_HOST, DB_NAME e DB_USER no arquivo .env.');
    }

    $dsn = "mysql:host=$host;port=$porta;dbname=$banco;charset=$charset";

    $opcoes = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $usuario, $senha, $opcoes);
    } catch (PDOException $e) {
        error_log('Erro de conexão com o banco: ' . $e->getMessage());

        if (env('APP_DEBUG', 'false') === 'true') {
            die('Erro de conexão: ' . $e->getMessage());
        }
        die('Não foi possível conectar ao banco de dados. Verifique o arquivo .env.');
    }

    return $pdo;
}
