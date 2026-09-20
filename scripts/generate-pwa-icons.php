<?php
// DISI-82: generar iconos PWA (192x192 y 512x512) con PHP GD.
// Diseño: fondo indigo, texto "GS" blanco bold + borde redondeado.

function generateIcon(int $size, string $path): void
{
    if (! extension_loaded('gd')) {
        fwrite(STDERR, "GD no disponible\n");
        exit(1);
    }

    $img = imagecreatetruecolor($size, $size);
    // Indigo 700 #3730a3
    $bg = imagecolorallocate($img, 55, 48, 163);
    // Blanco
    $white = imagecolorallocate($img, 255, 255, 255);

    // Fondo indigo
    imagefill($img, 0, 0, $bg);

    // Texto "GS" centrado usando fuentes built-in de GD (escalado).
    // Para tamaños grandes dibujamos varias veces para simular escala.
    $gdFont = 5; // 9x15 px por char
    $text = 'GS';
    $baseW = imagefontwidth($gdFont) * strlen($text);
    $baseH = imagefontheight($gdFont);
    $scale = max(1, (int) floor(($size * 0.18) / $baseH));
    $scaledW = $baseW * $scale;
    $scaledH = $baseH * $scale;
    $x = (int) (($size - $scaledW) / 2);
    $y = (int) (($size - $scaledH) / 2);

    // Dibujar el texto escalado (multiples veces)
    for ($i = 0; $i < $scale; $i++) {
        for ($j = 0; $j < $scale; $j++) {
            imagestring($img, $gdFont, $x + ($i * $baseW), $y + ($j * $baseH), $text, $white);
        }
    }

    // Guardar PNG
    imagepng($img, $path, 6);
    imagedestroy($img);
    echo "Generated $path (" . filesize($path) . " bytes)\n";
}

$outDir = __DIR__ . '/../public/images/pwa';
if (! is_dir($outDir)) {
    mkdir($outDir, 0755, true);
}

generateIcon(192, $outDir . '/icon-192.png');
generateIcon(512, $outDir . '/icon-512.png');