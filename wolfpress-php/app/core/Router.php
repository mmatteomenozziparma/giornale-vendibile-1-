<?php
/**
 * Router: legge il parametro ?r=controller/azione e chiama il metodo giusto.
 *
 * Esempi:
 *   index.php                      -> HomeController::index()
 *   index.php?r=notizie            -> NotizieController::index()
 *   index.php?r=tecnico/salva-stato -> TecnicoController::salvaStato()
 */
declare(strict_types=1);

final class Router
{
    /**
     * @param string        $cartellaController cartella con i file *Controller.php
     * @param string        $predefinita        rotta usata quando ?r manca
     * @param callable|null $controllo          funzione chiamata prima dell'azione (es. permessi)
     */
    public static function avvia(string $cartellaController, string $predefinita = 'home/index', ?callable $controllo = null): void
    {
        $rotta = $_GET['r'] ?? $predefinita;

        // Solo lettere minuscole e trattini: impedisce di caricare file arbitrari.
        if (!is_string($rotta) || !preg_match('#^[a-z]+(/[a-z]+(-[a-z]+)*)?$#', $rotta)) {
            self::nonTrovata($cartellaController);
            return;
        }

        $parti = explode('/', $rotta);
        $nome = $parti[0];
        $azione = $parti[1] ?? 'index';

        $classe = ucfirst($nome) . 'Controller';
        $file = $cartellaController . '/' . $classe . '.php';
        if (!is_file($file)) {
            self::nonTrovata($cartellaController);
            return;
        }
        require_once $file;

        // "salva-stato" diventa "salvaStato".
        $metodo = lcfirst(str_replace(' ', '', ucwords(str_replace('-', ' ', $azione))));

        if (!class_exists($classe, false) || !method_exists($classe, $metodo)) {
            self::nonTrovata($cartellaController);
            return;
        }
        $riflessione = new ReflectionMethod($classe, $metodo);
        if (!$riflessione->isPublic() || $riflessione->isStatic() || $riflessione->getDeclaringClass()->getName() !== $classe) {
            self::nonTrovata($cartellaController);
            return;
        }

        if ($controllo !== null && $controllo($nome, $metodo) === false) {
            return;
        }

        (new $classe())->$metodo();
    }

    private static function nonTrovata(string $cartellaController): void
    {
        $file = $cartellaController . '/ErroreController.php';
        if (is_file($file)) {
            require_once $file;
            (new ErroreController())->nonTrovata();
            return;
        }
        http_response_code(404);
        echo 'Pagina non trovata';
    }
}
