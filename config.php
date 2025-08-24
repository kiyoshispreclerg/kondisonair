<?php

define('DB_CONFIG', 'db.php');

function generateId($tabela = null) { // se vier tabela, conferir q id não existe mesmo
    // Epoch: 1º Jan 2025, em milissegundos
    $epoch = 1735689600000;
    
    // Timestamp em milissegundos desde o epoch
    $timestamp = round(microtime(true) * 1000) - $epoch;
    
    // Verifica se o timestamp está dentro do intervalo de 41 bits
    if ($timestamp < 0 || $timestamp > 0x1FFFFFFFFFF) { // 2^41 - 1
        throw new Exception("Timestamp fora do intervalo para gerar ID");
    }
    
    // Gera valor aleatório para 23 bits (0 a 8.388.607)
    $random = mt_rand(0, 0x7FFFFF); // 2^23 - 1
    
    // Combina: 41 bits para timestamp, 23 bits para aleatoriedade
    $id = ($timestamp << 23) | $random;
    
    return $id > 10000 ? $id : generateId($tabela);
}

$tituloPagina = 'Kondisonair';
$versaoK1 = 0; // release
$versaoK2 = 1; // major step
$versaoK3 = 15; // fixes

$idiomas_sistema = [
    1 => 'Português brasileiro',
    5 => 'English',
    4 => '日本語',
    6 => 'Esperanto'
];