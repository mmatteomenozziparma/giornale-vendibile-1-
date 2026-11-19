<?php
/**
 * Sostieni una testata o un politico: iscrizione gratuita, donazione e
 * abbonamento. I pagamenti avvengono sulla pagina di Stripe: qui non
 * passa nessun dato della carta. Un pagamento è registrato come riuscito
 * solo quando arriva il webhook firmato di Stripe (azione "webhook").
 */
declare(strict_types=1);

final class SostieniController extends SitoController
{
    public function index(): void
    {
        $a = $this->destinatario($this->parametro('testata'), $this->parametro('politico'));
        if ($a === null) {
            $this->pagina404('La testata o il politico indicato non esiste.');
            return;
        }
        $utente = Auth::utente();
        $lettore = $utente !== null && $utente['ruolo'] === 'user';
        $this->vista('sostieni', [
            'titolo' => 'Sostieni ' . $a['nome'] . ' · WolfPress',
            'a' => $a,
            'lettore' => $lettore,
            'iscritto' => $lettore && Database::valore(
                'SELECT 1 FROM iscrizioni WHERE user_id = ? AND (giornale_id <=> ?) AND (politico_id <=> ?)',
                [$utente['profilo_id'], $a['giornale_id'], $a['politico_id']]
            ) !== null,
            'abbonato' => $lettore && $a['giornale_id'] !== null && Database::valore(
                "SELECT 1 FROM abbonamenti WHERE user_id = ? AND giornale_id = ? AND stato IN ('attivo','insoluto')",
                [$utente['profilo_id'], $a['giornale_id']]
            ) !== null,
            'minimo' => (int) Config::get('DONAZIONE_MIN_CENT', '100') / 100,
        ]);
    }

    /** Donazione una tantum: crea il pagamento e manda il visitatore sulla pagina di Stripe. */
    public function dona(): void
    {
        $a = $this->richiesta();
        // "25" oppure "25,50" → centesimi interi, senza passare da numeri con la virgola.
        if (!preg_match('/^(\d{1,6})(?:[.,](\d{1,2}))?$/', $this->campo('importo', 12), $parti)) {
            $this->avviso('Scrivi l\'importo in euro, per esempio 10 oppure 10,50.', 'errore');
            $this->torna($a);
        }
        $centesimi = (int) $parti[1] * 100 + (int) str_pad($parti[2] ?? '', 2, '0');
        $this->paga($a, ['importo_centesimi' => $centesimi, 'messaggio' => $this->campo('messaggio', 500)], static fn (): array => Api::dona());
    }

    public function abbonati(): void
    {
        $a = $this->richiesta();
        if (Auth::ruolo() !== 'user') {
            $this->avviso('Per abbonarti accedi con il tuo account da lettore.', 'errore');
            $this->vai('account');
        }
        $this->paga($a, ['piano' => $this->campo('piano', 10)], static fn (): array => Api::abbonati());
    }

    /** Segue (o smette di seguire) gratis una testata o un politico. */
    public function segui(): void
    {
        $a = $this->richiesta();
        if (Auth::ruolo() !== 'user') {
            $this->avviso('Per seguire accedi con il tuo account da lettore.', 'errore');
            $this->vai('account');
        }
        $smetti = $this->campo('smetti', 1) === '1';
        Http::imposta(array_filter(['giornale_id' => $a['giornale_id'], 'politico_id' => $a['politico_id']]));
        try {
            $smetti ? Api::annullaIscrizione() : Api::iscriviti();
            $this->avviso($smetti ? 'Non segui più ' . $a['nome'] . '.' : 'Ora segui ' . $a['nome'] . '.');
        } catch (ErroreApi $errore) {
            $this->avviso($errore->getMessage(), 'errore');
        }
        $this->torna($a);
    }

    public function grazie(): void
    {
        $this->vista('pagina', [
            'titolo' => 'Grazie · WolfPress',
            'intestazione' => 'Grazie!',
            'paragrafi' => [
                'Il pagamento è stato inviato a Stripe. Appena viene confermato lo trovi nel tuo account; se hai effettuato l\'accesso riceverai anche un\'email di ricevuta.',
            ],
        ]);
    }

    public function annullato(): void
    {
        $this->vista('pagina', [
            'titolo' => 'Pagamento annullato · WolfPress',
            'intestazione' => 'Pagamento annullato',
            'paragrafi' => ['Non è stato addebitato nulla. Puoi riprovare quando vuoi.'],
        ]);
    }

    /** Chiamata da Stripe (non dal browser): conferma i pagamenti. */
    public function webhook(): void
    {
        try {
            Http::json(Api::webhookStripe());
        } catch (ErroreApi $errore) {
            Http::json(['errore' => $errore->getMessage()], $errore->stato);
        }
    }

    /** Controlli comuni alle azioni: form valido e destinatario esistente. */
    private function richiesta(): array
    {
        $this->soloPost();
        $a = $this->destinatario($this->campo('testata', 160), $this->campo('politico', 160));
        if ($a === null) {
            $this->avviso('La testata o il politico indicato non esiste.', 'errore');
            $this->vai();
        }
        return $a;
    }

    /** Passa i dati al backend, che crea la sessione di pagamento, e reindirizza a Stripe. */
    private function paga(array $a, array $dati, callable $operazione): void
    {
        Http::imposta(array_filter(['giornale_id' => $a['giornale_id'], 'politico_id' => $a['politico_id']] + $dati, static fn ($v): bool => $v !== null && $v !== ''));
        try {
            $esito = $operazione();
        } catch (ErroreApi $errore) {
            $this->avviso($errore->getMessage(), 'errore');
            $this->torna($a);
        }
        header('Location: ' . $esito['url_pagamento'], true, 303);
        exit;
    }

    private function torna(array $a): void
    {
        $this->vai('sostieni', $a['giornale_id'] !== null ? ['testata' => $a['slug']] : ['politico' => $a['slug']]);
    }

    /** Testata o politico da sostenere, a partire dallo slug. */
    private function destinatario(string $testata, string $politico): ?array
    {
        if ($testata !== '' && ($t = Testata::trova($testata)) !== null) {
            return ['tipo' => 'testata', 'slug' => $t['id'], 'nome' => $t['nome'], 'giornale_id' => $t['db_id'], 'politico_id' => null,
                'mensile' => $t['prezzo_mensile_cent'], 'annuale' => $t['prezzo_annuale_cent']];
        }
        if ($politico !== '' && ($p = Politico::trova($politico)) !== null) {
            return ['tipo' => 'politico', 'slug' => $p['id'], 'nome' => $p['nome'], 'giornale_id' => null, 'politico_id' => $p['db_id'],
                'mensile' => null, 'annuale' => null];
        }
        return null;
    }
}
