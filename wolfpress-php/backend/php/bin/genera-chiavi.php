<?php
/**
 * Genera le tre chiavi segrete da incollare nel file .env.
 * Uso:  php bin/genera-chiavi.php
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    exit(1);
}
foreach (['CHIAVE_CIFRATURA', 'CHIAVE_INDICE', 'PEPE_PASSWORD'] as $nome) {
    echo $nome . '=' . base64_encode(random_bytes(32)) . PHP_EOL;
}
