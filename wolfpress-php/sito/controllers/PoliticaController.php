<?php
/**
 * Politica: partiti, candidati ed eletti (i modelli "Atlante civico" delle schermate).
 */
declare(strict_types=1);

final class PoliticaController extends SitoController
{
    protected string $voce = 'politica';

    /** Indice: partiti, persone e tabella dei modelli (candidatura e mandato per livello). */
    public function index(): void
    {
        $this->vista('politica/index', [
            'titolo' => 'Politica · WolfPress',
            'partiti' => Partito::tutti(),
            'politici' => Politico::tutti(),
            'notizie' => array_slice(Articolo::filtra('Politica'), 0, 3),
        ]);
    }

    /** Sezione regionale di un partito. */
    public function partito(): void
    {
        $partito = Partito::trova($this->parametro('id')) ?? (Partito::tutti()[0] ?? null);
        if ($partito === null || ($this->parametro('id') !== '' && $partito['id'] !== $this->parametro('id'))) {
            $this->pagina404('Il partito richiesto non esiste.');
            return;
        }
        $this->vista('politica/partito', [
            'titolo' => $partito['nome'] . ' · ' . $partito['sezione'] . ' · WolfPress',
            'descrizione' => $partito['descrizione'],
            'partito' => $partito,
            'politici' => Politico::perPartito($partito['id']),
        ]);
    }

    /** Scheda di candidatura o di mandato di un politico. */
    public function profilo(): void
    {
        $politico = Politico::trova($this->parametro('livello'));
        $tipo = $this->parametro('tipo') ?: 'candidatura';
        if ($politico === null || !isset(Politico::SCENARI[$tipo])) {
            $this->pagina404('La scheda politica richiesta non esiste.');
            return;
        }
        $this->vista('politica/profilo', [
            'titolo' => $politico['nome'] . ' · ' . $politico[$tipo]['ruolo'] . ' · WolfPress',
            'descrizione' => $politico['bio'],
            'politico' => $politico,
            'tipo' => $tipo,
            'dati' => $politico[$tipo],
            'livello' => Politico::LIVELLI[$politico['id']],
        ]);
    }
}
