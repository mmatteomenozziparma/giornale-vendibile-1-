<?php
/**
 * Elenco delle pagine del portale: ogni riga è un file .php di questa cartella.
 *
 *     'nome del file' => ['controller', 'azione eseguita quando si apre la pagina']
 *
 * Il controller (cartella controllers/) prepara i dati, il file di pagina contiene l'HTML.
 * Le altre azioni dello stesso controller (di solito i form) si chiamano
 * con ?azione=nome sulla prima pagina di quel controller: es. login.php?azione=entra.
 */
return [
    'index' => ['home', 'index'],               // Pagina iniziale del portale: le aree disponibili per il proprio ruolo.
    'login' => ['accesso', 'index'],            // Accesso al portale (login).
    'registrazione' => ['accesso', 'registrazione'],// Iscrizione al portale: editore, giornalista o politico.
    'password' => ['accesso', 'password'],      // Password dimenticata e scelta della nuova password.
    'testata' => ['testata', 'index'],          // Creazione di una testata.
    'configuratore' => ['configuratore', 'index'],// Configuratore della testata.
    'cliente' => ['cliente', 'index'],          // Area cliente: scrivania della redazione.
    'articoli' => ['articoli', 'index'],        // Articoli del giornalista.
    'articolo' => ['articoli', 'nuovo'],        // Scrittura e modifica di un articolo.
    'editore' => ['editore', 'index'],          // Area editore: account dei giornalisti e chat di redazione.
    'redattore' => ['editore', 'profilo'],      // Scheda di un giornalista della redazione.
    'giornalista' => ['giornalista', 'index'],  // Profilo del giornalista: portfolio, agenda, chat.
    'politico' => ['politico', 'index'],        // Area politico: pagina di candidatura o di mandato.
    'politico-articoli' => ['politico', 'articoli'],// Articoli firmati dal politico.
    'eventi' => ['eventi', 'index'],            // Eventi: organizzati, inviti ricevuti, partecipazioni.
    'evento' => ['eventi', 'scheda'],           // Scheda di un evento.
    'assistenza' => ['assistenza', 'index'],    // Assistenza editore: ticket.
    'assistenza-chat' => ['assistenza', 'chat'],// Assistenza editore: chat con il fornitore.
    'tecnico' => ['tecnico', 'index'],          // Gestione tecnica: infrastruttura.
    'tecnico-personale' => ['tecnico', 'personale'],// Gestione tecnica: personale.
    'tecnico-account' => ['tecnico', 'account'],// Gestione tecnica: account da approvare.
    'tecnico-partiti' => ['tecnico', 'partiti'],// Gestione tecnica: partiti.
    'tecnico-clienti' => ['tecnico', 'clienti'],// Gestione tecnica: clienti e testate.
    'tecnico-referente' => ['tecnico', 'referente'],// Gestione tecnica: referente cliente.
    'tecnico-ticket' => ['tecnico', 'ticket'],  // Gestione tecnica: coda dei ticket.
    'tecnico-chat' => ['tecnico', 'chat'],      // Gestione tecnica: chat di assistenza.
    'atlante' => ['atlante', 'index'],                // Atlante Civico: clienti (candidati, partiti, liste, comitati).
    'atlante-cliente' => ['atlante', 'cliente'],      // Atlante Civico: scheda di un cliente (referenti, siti, offerte, verifiche).
    'atlante-fascicolo' => ['atlante', 'fascicolo'],  // Atlante Civico: fascicolo con documenti e firme.
    'atlante-modelli' => ['atlante', 'modelli'],      // Atlante Civico: modelli documentali MASTER.
    'errore' => ['errore', 'nonTrovata'],       // Pagina di errore del portale (404, 403, 500).
    'logo' => ['media', 'logo'],                    // Logo caricato dal configuratore (immagine, senza HTML).
];
