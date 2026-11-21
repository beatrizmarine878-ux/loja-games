// JavaScript da Level Up Store

document.addEventListener('DOMContentLoaded', function () {

    // Fecha automaticamente os alertas depois de alguns segundos
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(function (alerta) {
        setTimeout(function () {
            alerta.remove();
        }, 5000);
    });

    // Confirmação para ações de exclusão
    document.querySelectorAll('[data-confirm]').forEach(function (elemento) {
        elemento.addEventListener('click', function (event) {
            const mensagem = elemento.getAttribute('data-confirm');

            if (!confirm(mensagem)) {
                event.preventDefault();
            }
        });
    });

    // Atualiza a quantidade do carrinho respeitando o estoque informado
    document.querySelectorAll('.campo-qtd[data-estoque]').forEach(function (campo) {
        campo.addEventListener('change', function () {
            const estoque = parseInt(campo.dataset.estoque, 10);
            let quantidade = parseInt(campo.value, 10);

            if (isNaN(quantidade) || quantidade < 1) {
                quantidade = 1;
            }

            if (quantidade > estoque) {
                quantidade = estoque;
            }

            campo.value = quantidade;
        });
    });

});
