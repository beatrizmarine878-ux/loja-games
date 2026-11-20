<?php
// Carrega as variáveis do arquivo .env

function env($chave, $padrao = null)
{
    static $variaveis = null;

    if ($variaveis === null) {
        $variaveis = [];

        $arquivo = __DIR__ . '/../.env';

        if (file_exists($arquivo)) {
            $linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($linhas as $linha) {
                $linha = trim($linha);

                if ($linha === '' || strpos($linha, '#') === 0) {
                    continue;
                }

                $partes = explode('=', $linha, 2);

                if (count($partes) === 2) {
                    $nome = trim($partes[0]);
                    $valor = trim($partes[1]);

                    if (
                        strlen($valor) >= 2 &&
                        (($valor[0] === '"' && $valor[strlen($valor) - 1] === '"') ||
                        ($valor[0] === "'" && $valor[strlen($valor) - 1] === "'"))
                    ) {
                        $valor = substr($valor, 1, -1);
                    }

                    $variaveis[$nome] = $valor;
                }
            }
        }
    }

    return array_key_exists($chave, $variaveis)
        ? $variaveis[$chave]
        : $padrao;
}
