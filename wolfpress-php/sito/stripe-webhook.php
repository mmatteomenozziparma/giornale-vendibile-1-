<?php
/**
 * Webhook di Stripe: lo chiama Stripe (non il browser) per confermare i pagamenti.
 * Nessuna sessione e nessun cookie: l'autenticità è garantita dalla firma del messaggio.
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/app/bootstrap.php';

try {
    Http::json(Api::webhookStripe());
} catch (ErroreApi $errore) {
    Http::json(['errore' => $errore->getMessage()], $errore->stato);
} catch (Throwable $errore) {
    error_log('Webhook Stripe: ' . $errore->getMessage());
    Http::json(['errore' => 'Errore interno.'], 500);
}
