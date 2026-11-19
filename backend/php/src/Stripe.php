<?php
/**
 * Pagamenti con Stripe Checkout (pagina di pagamento ospitata da Stripe).
 *
 * Perché è il metodo sicuro:
 *  - i dati della carta li inserisce l'utente sul sito di Stripe: non
 *    passano mai dal nostro server né dal nostro database (PCI-DSS SAQ A);
 *  - l'importo è deciso e validato dal server, mai preso "per buono" dal browser;
 *  - un pagamento risulta riuscito SOLO quando arriva il webhook di Stripe
 *    con firma valida, non quando il browser torna sulla pagina di successo;
 *  - ogni richiesta ha una chiave di idempotenza: un doppio clic non
 *    crea due pagamenti.
 */
declare(strict_types=1);

final class Stripe
{
    private const API = 'https://api.stripe.com/v1';

    /** Chiamata autenticata alle API Stripe (TLS verificato). */
    public static function chiama(string $percorso, array $parametri, string $idempotenza): array
    {
        $curl = curl_init((Config::produzione() ? self::API : Config::get('STRIPE_API_BASE', self::API)) . $percorso);
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($parametri),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . Config::obbligatorio('STRIPE_CHIAVE_SEGRETA'),
                'Idempotency-Key: ' . $idempotenza,
            ],
        ]);
        $risposta = curl_exec($curl);
        $stato = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        $errore = curl_error($curl);
        curl_close($curl);

        $dati = is_string($risposta) ? json_decode($risposta, true) : null;
        if ($stato < 200 || $stato >= 300 || !is_array($dati)) {
            // Il dettaglio va nel log del server, non al client.
            error_log("Stripe $percorso → HTTP $stato " . ($dati['error']['message'] ?? $errore));
            throw new ErroreApi('Il servizio di pagamento non è disponibile, riprova tra poco.', 502);
        }
        return $dati;
    }

    /** Checkout per una donazione una tantum. */
    public static function checkoutDonazione(int $idDonazione, int $centesimi, string $destinatario, ?string $email): array
    {
        $frontend = Config::obbligatorio('FRONTEND_URL');
        return self::chiama('/checkout/sessions', array_filter([
            'mode' => 'payment',
            'submit_type' => 'donate',
            'client_reference_id' => "donazione_$idDonazione",
            'customer_email' => $email,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $centesimi,
                    'product_data' => ['name' => "Donazione a $destinatario"],
                ],
            ]],
            'metadata' => ['tipo' => 'donazione', 'id' => $idDonazione],
            'payment_intent_data' => ['metadata' => ['tipo' => 'donazione', 'id' => $idDonazione]],
            'success_url' => "$frontend/donazione-ok?sessione={CHECKOUT_SESSION_ID}",
            'cancel_url' => "$frontend/donazione-annullata",
        ], static fn ($v) => $v !== null), "donazione-$idDonazione");
    }

    /** Checkout per un abbonamento ricorrente a un giornale. */
    public static function checkoutAbbonamento(int $idAbbonamento, int $centesimi, string $piano, string $giornale, string $email): array
    {
        $frontend = Config::obbligatorio('FRONTEND_URL');
        return self::chiama('/checkout/sessions', [
            'mode' => 'subscription',
            'client_reference_id' => "abbonamento_$idAbbonamento",
            'customer_email' => $email,
            'line_items' => [[
                'quantity' => 1,
                'price_data' => [
                    'currency' => 'eur',
                    'unit_amount' => $centesimi,
                    'recurring' => ['interval' => $piano === 'annuale' ? 'year' : 'month'],
                    'product_data' => ['name' => "Abbonamento $piano a $giornale"],
                ],
            ]],
            'metadata' => ['tipo' => 'abbonamento', 'id' => $idAbbonamento],
            'subscription_data' => ['metadata' => ['tipo' => 'abbonamento', 'id' => $idAbbonamento]],
            'success_url' => "$frontend/abbonamento-ok?sessione={CHECKOUT_SESSION_ID}",
            'cancel_url' => "$frontend/abbonamento-annullato",
        ], "abbonamento-$idAbbonamento");
    }

    /**
     * Verifica la firma del webhook (intestazione Stripe-Signature) e
     * restituisce l'evento. Rifiuta firme errate e messaggi più vecchi di
     * 5 minuti (protezione dal riutilizzo di un messaggio intercettato).
     */
    public static function eventoWebhook(string $corpo, string $firma, int $tolleranza = 300): array
    {
        $segreto = Config::obbligatorio('STRIPE_SEGRETO_WEBHOOK');
        $tempo = null;
        $firme = [];
        foreach (explode(',', $firma) as $parte) {
            $coppia = explode('=', trim($parte), 2);
            if (count($coppia) !== 2) {
                continue;
            }
            if ($coppia[0] === 't') {
                $tempo = $coppia[1];
            } elseif ($coppia[0] === 'v1') {
                $firme[] = $coppia[1];
            }
        }
        if ($tempo === null || !ctype_digit($tempo) || $firme === [] || abs(time() - (int) $tempo) > $tolleranza) {
            throw new ErroreApi('Firma del webhook non valida', 400);
        }
        $attesa = hash_hmac('sha256', $tempo . '.' . $corpo, $segreto);
        $valida = false;
        foreach ($firme as $ricevuta) {
            $valida = hash_equals($attesa, $ricevuta) || $valida;
        }
        $evento = json_decode($corpo, true);
        if (!$valida || !is_array($evento) || !isset($evento['id'], $evento['type'], $evento['data']['object'])) {
            throw new ErroreApi('Firma del webhook non valida', 400);
        }
        return $evento;
    }
}
