<?php
// Lê o arquivo .env e guarda as variáveis para o resto do sistema usar.
// Fiz na mão (sem Composer) para funcionar direto no XAMPP.

function carregarEnv($caminho)
{
    if (!file_exists($caminho)) {
        die('Arquivo .env não encontrado. Copie o .env.example para .env e preencha com os dados do seu banco.');
    }

    $linhas = file($caminho, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($linhas as $linha) {
        $linha = trim($linha);

        // pula comentários (#) e linhas sem "="
        if ($linha === '' || $linha[0] === '#' || strpos($linha, '=') === false) {
            continue;
        }

        // explode só no primeiro "=" (a senha pode ter "=" dentro)
        list($chave, $valor) = explode('=', $linha, 2);
        $chave = trim($chave);
        $valor = trim($valor);

        // tira aspas do começo e do fim, se tiver
        if (strlen($valor) >= 2 && ($valor[0] === '"' || $valor[0] === "'") && substr($valor, -1) === $valor[0]) {
            $valor = substr($valor, 1, -1);
        }

        $_ENV[$chave] = $valor;
        putenv($chave . '=' . $valor);
    }
}

// Pega o valor de uma variável. Se não existir, devolve o valor padrão.
function env($chave, $padrao = null)
{
    if (isset($_ENV[$chave])) {
        return $_ENV[$chave];
    }

    $valor = getenv($chave);
    return ($valor === false) ? $padrao : $valor;
}
