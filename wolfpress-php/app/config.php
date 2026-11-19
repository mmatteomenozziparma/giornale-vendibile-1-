<?php
/**
 * Configurazione generale di WolfPress (demo).
 * Modifica questi valori per adattare il progetto.
 */
return [
    // Nome della piattaforma mostrato nei due front end.
    'nome' => 'WolfPress',
    'motto' => 'Notizie, persone, territori',

    // Cartella del backend PHP (database, email, Stripe). Lì si trova anche il file .env.
    'backend' => dirname(__DIR__) . '/backend/php',

    // true = i contenuti sono di prova (dati caricati con bin/importa-demo.php): le pagine
    // mostrano le diciture «demo», «fittizio», «dimostrativo». Con dati veri mettere false.
    'demo' => true,

    // true = mostra gli errori PHP a video (solo in sviluppo).
    'debug' => false,

    // La demo non va indicizzata dai motori di ricerca.
    'noindex' => true,
];
