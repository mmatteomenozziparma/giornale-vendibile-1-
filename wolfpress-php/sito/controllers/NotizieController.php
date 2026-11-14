<?php
/**
 * Notizie: elenco filtrabile per sezione e testata, e pagina del singolo articolo.
 */
declare(strict_types=1);

final class NotizieController extends SitoController
{
    protected string $voce = 'notizie';

    public function index(): void
    {
        $sezione = $this->tra($this->parametro('sezione'), Articolo::sezioni(), '');
        $testata = $this->tra($this->parametro('testata'), array_column(Testata::tutti(), 'id'), '');

        $titolo = 'Tutte le notizie';
        if ($sezione !== '') {
            $titolo = $sezione;
        } elseif ($testata !== '') {
            $titolo = Testata::nome($testata);
        }

        $this->vista('notizie/elenco', [
            'titolo' => $titolo . ' · WolfPress',
            'intestazione' => $titolo,
            'articoli' => Articolo::filtra($sezione, $testata),
            'sezioni' => Articolo::sezioni(),
            'testate' => Testata::tutti(),
            'sezione' => $sezione,
            'testata' => $testata,
        ]);
    }

    public function articolo(): void
    {
        $articolo = Articolo::pubblicatoPerSlug($this->parametro('slug'));
        if ($articolo === null) {
            $this->pagina404('L\'articolo non esiste oppure non è più pubblicato.');
            return;
        }

        $altri = array_values(array_filter(
            Articolo::filtra($articolo['sezione']),
            static fn (array $a): bool => $a['id'] !== $articolo['id']
        ));

        $this->vista('notizie/articolo', [
            'titolo' => $articolo['titolo'] . ' · WolfPress',
            'descrizione' => $articolo['sommario'],
            'articolo' => $articolo,
            'testata' => Testata::trova($articolo['testata']),
            'altri' => array_slice($altri, 0, 3),
        ]);
    }
}
