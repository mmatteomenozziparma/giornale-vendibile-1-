<?php
/**
 * Accessi demo: si entra scegliendo un ruolo e un profilo di esempio (senza password).
 */
declare(strict_types=1);

final class AccessoController extends PortaleController
{
    protected string $area = 'accesso';

    /** Profili di esempio dell'editore. */
    private const EDITORI = [
        'ed1' => ['Laura Monti', 'Editore'],
        'ed2' => ['Paolo Ferri', 'Direttore responsabile'],
    ];

    public function index(): void
    {
        $ruolo = $this->tra($this->parametro('ruolo'), array_keys(Auth::RUOLI), 'editore');
        $this->voce = $ruolo;

        $titoli = [
            'editore' => ['Accesso editore', 'Editore', 'Profilo dimostrativo della testata WolfPress.', 'Profilo editoriale'],
            'giornalista' => ['Accesso giornalista', 'Giornalista', 'Profilo dimostrativo della testata WolfPress.', 'Giornalista'],
            'politico' => ['Accesso politico', 'Politico', 'Gestisci la pagina pubblica di candidatura e di mandato.', 'Scheda politica'],
            'tecnico' => ['Accesso operatore tecnico', 'Operatore tecnico', 'Profilo riservato al team del fornitore nella simulazione.', 'Operatore'],
        ];

        $this->vista('accesso', [
            'titolo' => $titoli[$ruolo][0] . ' · WolfPress',
            'ruolo' => $ruolo,
            'testi' => $titoli[$ruolo],
            'profili' => $this->profili($ruolo),
        ]);
    }

    public function entra(): void
    {
        $this->soloPost();
        $ruolo = $this->tra($this->campo('ruolo'), array_keys(Auth::RUOLI), '');
        $profili = $ruolo !== '' ? $this->profili($ruolo) : [];
        $profilo = $this->campo('profilo');

        if ($ruolo === '' || !isset($profili[$profilo])) {
            $this->avviso('Scegli un profilo valido per entrare.', 'errore');
            $this->vai('accesso', $ruolo !== '' ? ['ruolo' => $ruolo] : []);
        }

        $nome = trim(explode('·', $profili[$profilo])[0]);
        Auth::entra($ruolo, $nome, $profilo);
        $this->avviso('Sei entrato come ' . $nome . ' (' . Auth::RUOLI[$ruolo] . ').');
        $this->vai('home');
    }

    public function esci(): void
    {
        $this->soloPost();
        Auth::esci();
        $this->vai('accesso');
    }

    /** Profili disponibili per ruolo: [id => etichetta]. */
    private function profili(string $ruolo): array
    {
        $profili = [];
        switch ($ruolo) {
            case 'editore':
                foreach (self::EDITORI as $id => [$nome, $qualifica]) {
                    $profili[$id] = $nome . ' · ' . $qualifica;
                }
                break;
            case 'giornalista':
                foreach (Giornalista::attivi() as $g) {
                    $profili[$g['id']] = $g['nome'] . ' · ' . $g['ruolo'] . ', ' . $g['sezione'];
                }
                break;
            case 'politico':
                foreach (Politico::tutti() as $p) {
                    $profili[$p['id']] = $p['nome'] . ' · ' . Politico::LIVELLI[$p['id']];
                }
                break;
            case 'tecnico':
                foreach (Operatore::attivi() as $o) {
                    $profili[$o['id']] = $o['nome'] . ' · ' . $o['ruolo'];
                }
                break;
        }
        return $profili;
    }
}
