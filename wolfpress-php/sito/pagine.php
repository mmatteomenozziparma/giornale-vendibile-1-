<?php
/**
 * Elenco delle pagine del sito: ogni riga è un file .php di questa cartella.
 *
 *     'nome del file' => ['controller', 'azione eseguita quando si apre la pagina']
 *
 * Il controller (cartella controllers/) prepara i dati, il file di pagina contiene l'HTML.
 * Le altre azioni dello stesso controller (di solito i form) si chiamano
 * con ?azione=nome sulla prima pagina di quel controller: es. login.php?azione=entra.
 */
return [
    'index' => ['home', 'index'],               // Home del sito: notizia in primo piano, ultime notizie, politica e testate.
    'notizie' => ['notizie', 'index'],          // Notizie: elenco filtrabile per sezione e testata.
    'articolo' => ['notizie', 'articolo'],      // Pagina di un articolo di giornale.
    'testate' => ['testate', 'index'],          // Elenco delle testate ospitate dalla piattaforma.
    'politica' => ['politica', 'index'],        // Politica: partiti, candidati ed eletti.
    'partito' => ['politica', 'partito'],       // Scheda di un partito.
    'politico' => ['politica', 'profilo'],      // Scheda di un politico: candidatura oppure mandato.
    'articolo-politico' => ['politica', 'articolo'],// Articolo firmato da un politico.
    'eventi' => ['eventi', 'index'],            // Prossimi eventi pubblici.
    'ricerca' => ['ricerca', 'index'],          // Ricerca unica: notizie, politici e partiti.
    'login' => ['account', 'accedi'],           // Accesso dei lettori (login).
    'registrazione' => ['account', 'registrati'],// Iscrizione dei lettori.
    'password' => ['account', 'password'],      // Password dimenticata e scelta della nuova password.
    'account' => ['account', 'index'],          // Account del lettore: iscrizioni, abbonamenti, donazioni, eventi.
    'sostieni' => ['sostieni', 'index'],        // Sostieni una testata o un politico: segui, dona, abbonati.
    'grazie' => ['sostieni', 'grazie'],         // Ritorno da Stripe dopo un pagamento riuscito.
    'annullato' => ['sostieni', 'annullato'],   // Ritorno da Stripe dopo un pagamento annullato.
    'chi-siamo' => ['pagine', 'chiSiamo'],      // Pagina informativa: chi siamo.
    'privacy' => ['pagine', 'privacy'],         // Pagina informativa: privacy.
    'accessibilita' => ['pagine', 'accessibilita'],// Pagina informativa: accessibilità.
    'errore' => ['errore', 'nonTrovata'],       // Pagina di errore del sito (404 e 500).
];
