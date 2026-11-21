-- =====================================================
-- Level Up Store - Script do banco de dados
-- MySQL / MariaDB (testado pensando no XAMPP)
-- Pode ser importado pelo phpMyAdmin (aba "Importar")
-- =====================================================

CREATE DATABASE IF NOT EXISTS loja_games
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE loja_games;

-- Apaga as tabelas na ordem certa (filhas primeiro) para poder recriar tudo
DROP TABLE IF EXISTS itens_pedido;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS produtos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;

CREATE TABLE usuarios (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    senha_hash  VARCHAR(255) NOT NULL,
    perfil      ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente',
    criado_em   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB;

CREATE TABLE categorias (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nome  VARCHAR(80) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categorias_nome (nome)
) ENGINE=InnoDB;

CREATE TABLE produtos (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    categoria_id  INT UNSIGNED NOT NULL,
    nome          VARCHAR(150) NOT NULL,
    descricao     TEXT NULL,
    preco         DECIMAL(10,2) NOT NULL,
    estoque       INT UNSIGNED NOT NULL DEFAULT 0,
    imagem        VARCHAR(255) NULL,
    ativo         TINYINT(1) NOT NULL DEFAULT 1,
    criado_em     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_produtos_categoria (categoria_id),
    CONSTRAINT fk_produtos_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT ck_produtos_preco CHECK (preco >= 0)
) ENGINE=InnoDB;

CREATE TABLE pedidos (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    usuario_id       INT UNSIGNED NOT NULL,
    data_pedido      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    forma_pagamento  ENUM('pix', 'boleto', 'cartao') NOT NULL,
    total            DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_pedidos_usuario (usuario_id),
    CONSTRAINT fk_pedidos_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT ck_pedidos_total CHECK (total >= 0)
) ENGINE=InnoDB;

CREATE TABLE itens_pedido (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id       INT UNSIGNED NOT NULL,
    produto_id      INT UNSIGNED NOT NULL,
    quantidade      INT UNSIGNED NOT NULL,
    preco_unitario  DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_item_pedido_produto (pedido_id, produto_id),
    KEY idx_itens_produto (produto_id),
    CONSTRAINT fk_itens_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos (id)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_itens_produto
        FOREIGN KEY (produto_id) REFERENCES produtos (id)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT ck_itens_quantidade CHECK (quantidade > 0),
    CONSTRAINT ck_itens_preco CHECK (preco_unitario >= 0)
) ENGINE=InnoDB;

-- CARGA INICIAL DE TESTE

INSERT INTO usuarios (nome, email, senha_hash, perfil) VALUES
('Administrador', 'admin@levelup.com',   '$2y$10$ZdqOPhN4.ywx3pLuDEVmyOHJpT71cTkA6Q5L0pxDZKsok8Z2eK.ua', 'admin'),
('Cliente Teste', 'cliente@levelup.com', '$2y$10$RdtsavIV0sQlHP0XTvs3mu.lbybJdGglUP1ouNu3.C.VWYuNRroWi', 'cliente');

INSERT INTO categorias (nome) VALUES
('Periféricos'),
('Monitores e Câmeras'),
('Cadeiras e Acessórios'),
('Jogos');

INSERT INTO produtos (categoria_id, nome, descricao, preco, estoque, imagem) VALUES
(1, 'Controle Pro Sem Fio',
    'Controle sem fio com conexão Bluetooth, vibração dupla e bateria recarregável que dura até 20 horas. Compatível com PC e celular.',
    249.90, 15, 'controle-pro.png'),
(1, 'Headset Gamer 7.1',
    'Headset com som surround virtual 7.1, microfone removível com cancelamento de ruído e almofadas macias para longas partidas.',
    329.90, 10, 'headset-71.png'),
(1, 'Mouse Gamer RGB 12000 DPI',
    'Mouse com sensor óptico de 12000 DPI, 7 botões programáveis e iluminação RGB personalizável pelo software.',
    159.90, 25, 'mouse-rgb.png'),
(1, 'Teclado Mecânico ABNT2',
    'Teclado mecânico com switch blue, layout ABNT2 (com Ç), iluminação LED e estrutura reforçada em metal.',
    289.90, 12, 'teclado-mecanico.png'),
(1, 'Mousepad Gamer XL 90x40',
    'Mousepad grande com base antiderrapante e superfície de tecido otimizada para precisão e velocidade.',
    59.90, 40, 'mousepad-xl.png'),
(2, 'Monitor 24" 144Hz Full HD',
    'Monitor de 24 polegadas com taxa de atualização de 144Hz, resposta de 1ms e painel IPS com cores vivas.',
    1099.00, 6, 'monitor-144.png'),
(2, 'Webcam Full HD 1080p',
    'Webcam com resolução Full HD, foco automático e microfone embutido. Ideal para streams e aulas online.',
    179.90, 3, 'webcam-hd.png'),
(3, 'Cadeira Gamer Reclinável',
    'Cadeira ergonômica com encosto reclinável até 180 graus, apoio de braço ajustável e almofadas de pescoço e lombar.',
    899.90, 4, 'cadeira-gamer.png'),
(4, 'Pixel Quest (PC)',
    'Aventura em pixel art com exploração, puzzles e mais de 20 horas de história. Jogo fictício criado para este projeto.',
    49.90, 100, 'jogo-pixel.png'),
(4, 'Turbo Rally (PC)',
    'Jogo de corrida arcade com 30 pistas, carros customizáveis e modo multiplayer local. Jogo fictício criado para este projeto.',
    69.90, 60, 'jogo-corrida.png'),
(4, 'Dragon Cards (PC)',
    'Jogo de estratégia com cartas de dragões, mais de 200 cartas coletáveis e partidas rápidas. Jogo fictício criado para este projeto.',
    39.90, 0, 'jogo-cartas.png'),
(4, 'Coop Kitchen (PC)',
    'Jogo cooperativo de cozinha caótica para até 4 jogadores. Jogo fictício criado para este projeto.',
    59.90, 35, 'jogo-coop.png');
