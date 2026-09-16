CREATE DATABASE IF NOT EXISTS db_new_pet;
USE db_new_pet;

CREATE TABLE clientes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    telefone VARCHAR(20),
    cep VARCHAR(10),
    endereco VARCHAR(255),
    bairro VARCHAR(100),
    nome_animal VARCHAR(100),
    raca_animal VARCHAR(100),
    data_aniversario DATE
);

CREATE TABLE produtos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    valor DECIMAL(10,2) NOT NULL,
    data_cadastro DATE
);

CREATE TABLE vendas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id INT,
    produto_id INT,
    valor DECIMAL(10,2),
    tipo ENUM('pago', 'fiado') DEFAULT 'pago',
    data_venda DATE,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE CASCADE,
    FOREIGN KEY (produto_id) REFERENCES produtos(id) ON DELETE CASCADE
);