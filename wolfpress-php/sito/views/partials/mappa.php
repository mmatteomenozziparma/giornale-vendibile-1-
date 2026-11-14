<?php
/**
 * Mappa illustrativa della sezione regionale (disegno SVG, territorio di fantasia).
 * Variabile: $rete (elenco dei circoli: i primi tre vengono indicati sulla mappa).
 */
$punti = [[300, 248], [128, 238], [222, 296]];
?>
<svg class="wp-mappa" viewBox="0 0 470 460" role="img" aria-label="Mappa illustrativa del territorio con i circoli della sezione">
    <defs>
        <pattern id="quadretti" width="22" height="22" patternUnits="userSpaceOnUse">
            <path d="M22 0H0V22" fill="none" stroke="#cfdccb" stroke-width="0.6"/>
        </pattern>
    </defs>
    <rect width="470" height="460" fill="#dfe9da"/>
    <rect width="470" height="460" fill="url(#quadretti)"/>

    <g fill="none" stroke="#b9c9b5" stroke-width="1">
        <path d="M-10 70 C 90 20, 180 110, 290 60 S 430 10, 480 60"/>
        <path d="M-10 120 C 100 70, 190 160, 300 110 S 430 70, 480 120"/>
        <path d="M-10 330 C 80 380, 190 320, 300 380 S 420 410, 480 370"/>
        <path d="M-10 390 C 90 440, 200 380, 310 430 S 430 450, 480 420"/>
        <path d="M0 200 C 30 150, 30 300, 10 340"/>
        <path d="M455 150 C 430 220, 450 300, 470 330"/>
    </g>

    <polygon points="58,214 100,166 166,168 202,126 270,144 300,192 362,204 414,266 352,306 290,308 254,344 182,330 84,276"
             fill="#a9c0a6" fill-opacity="0.85" stroke="#2c5a48" stroke-width="2" stroke-linejoin="round"/>

    <g fill="none" stroke="#2c5a48" stroke-width="1.6" stroke-dasharray="5 5">
        <path d="M128 238 L222 204 L300 248 L222 296 Z"/>
        <path d="M222 204 L222 296"/>
        <path d="M300 248 L364 288"/>
    </g>

    <g fill="#8a2f22" stroke="#fffdf6" stroke-width="2">
        <circle cx="222" cy="204" r="6"/>
        <circle cx="364" cy="288" r="6"/>
        <?php foreach (array_slice($rete, 0, 3) as $i => $circolo): ?>
            <circle cx="<?= $punti[$i][0] ?>" cy="<?= $punti[$i][1] ?>" r="<?= $i === 0 ? 8 : 6 ?>"/>
        <?php endforeach; ?>
    </g>

    <g font-family="system-ui, sans-serif" font-size="13" font-weight="700" fill="#17352b">
        <?php foreach (array_slice($rete, 0, 3) as $i => $circolo): ?>
            <text x="<?= $punti[$i][0] + ($i === 1 ? -22 : 10) ?>" y="<?= $punti[$i][1] + ($i === 0 ? -8 : 22) ?>"><?= e($circolo['nome']) ?></text>
        <?php endforeach; ?>
    </g>

    <g transform="translate(424 60)" fill="none" stroke="#17352b" stroke-width="1.4">
        <circle r="15"/>
        <path d="M0 -22 V22 M-22 0 H22"/>
        <path d="M0 -17 L5 2 L0 -2 L-5 2 Z" fill="#17352b"/>
    </g>

    <text x="18" y="438" font-family="system-ui, sans-serif" font-size="10" letter-spacing="3" fill="#3f574c">MAPPA ILLUSTRATIVA</text>
</svg>
