CREATE DATABASE IF NOT EXISTS estoque_produtos CHARACTER SET utf8 COLLATE utf8_general_ci;
USE estoque_produtos;
CREATE TABLE IF NOT EXISTS produtos (
 id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 descricao VARCHAR(120) NOT NULL,
 categoria VARCHAR(80) NOT NULL,
 valor_compra DECIMAL(10,2) NOT NULL DEFAULT 0.00,
 valor_venda DECIMAL(10,2) NOT NULL DEFAULT 0.00,
 estoque INT UNSIGNED NOT NULL DEFAULT 0,
 criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 KEY idx_descricao (descricao),
 KEY idx_categoria (categoria)
);
-- Execute o INSERT abaixo apenas uma vez, se quiser o produto de exemplo.
INSERT INTO produtos (descricao, categoria, valor_compra, valor_venda, estoque) VALUES ('Notebook Samsung', 'Informática', 3800.00, 4120.00, 5);
