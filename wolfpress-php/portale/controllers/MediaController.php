<?php
/**
 * Serve il logo caricato dal configuratore (salvato fuori dalla cartella pubblica).
 */
declare(strict_types=1);

final class MediaController extends PortaleController
{
    public function logo(): void
    {
        $logo = Impostazioni::leggi()['logo'] ?? '';
        $file = WP_STORAGE . '/logo.bin';
        $tipi = ['image/png', 'image/jpeg', 'image/webp'];

        if (!is_array($logo) || !in_array($logo['tipo'] ?? '', $tipi, true) || !is_file($file)) {
            http_response_code(404);
            return;
        }
        header('Content-Type: ' . $logo['tipo']);
        header('Content-Length: ' . filesize($file));
        header('Cache-Control: private, max-age=86400');
        readfile($file);
    }
}
