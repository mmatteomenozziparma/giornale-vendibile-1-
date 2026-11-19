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
    'backend' => dirname(__DIR__, 2) . '/backend/php',

    // true = mostra gli errori PHP a video (solo in sviluppo).
    'debug' => false,

    // La demo non va indicizzata dai motori di ricerca.
    'noindex' => true,
];
