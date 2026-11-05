<?php

$host = '127.0.0.1';
$port = '3307';
$dbname = 'estoque_produtos';
$username = 'root';
$password = 'usbw';

try {
    $pdo = new PDO(
        'mysql:host=' . $host . ';port=' . $port . ';dbname=' . $dbname,
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

    $pdo->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC
    );

    $pdo->exec("SET NAMES utf8");

} catch (PDOException $e) {
    die('Erro ao conectar ao MySQL: ' . $e->getMessage());
}
?>