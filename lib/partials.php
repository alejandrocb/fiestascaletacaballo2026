<?php
/** Fragmentos HTML reutilizables (cabecera, pie, visor, icono de lapa). */

declare(strict_types=1);

function lapa_icon(string $class = 'lapa-ico'): string
{
    // Silueta sencilla de una lapa (concha) en SVG.
    return '<svg class="' . e($class) . '" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">'
        . '<ellipse cx="24" cy="36" rx="19" ry="5" fill="currentColor" opacity=".9"/>'
        . '<path d="M6 35C6 22 14 8 24 8s18 14 18 27" fill="currentColor"/>'
        . '<path d="M24 9c-1 8-6 18-16 25M24 9c1 8 6 18 16 25M24 9v26" stroke="rgba(255,255,255,.35)" stroke-width="1.4" stroke-linecap="round"/>'
        . '</svg>';
}

function page_head(string $pageTitle): void
{
    $config = get_config();
    $full = $pageTitle !== '' ? $pageTitle . ' · ' . $config['title'] : $config['title'] . ' · ' . $config['subtitle'];
    ?><!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0a3d5c">
<title><?= e($full) ?></title>
<meta name="description" content="Galería de fotos de las Fiestas de La Lapa de Caleta de Caballo (Teguise, Lanzarote).">
<meta property="og:title" content="<?= e($config['title'] . ' · ' . $config['subtitle']) ?>">
<meta property="og:description" content="Mira y comparte las fotos de las fiestas.">
<meta property="og:type" content="website">
<link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
<link rel="icon" href="favicon.ico">
<link rel="apple-touch-icon" href="apple-touch-icon.png">
<link rel="manifest" href="site.webmanifest">
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php
}

function public_topbar(): void
{
    $config = get_config();
    ?>
<div class="topbar">
  <a class="brand" href="index.php">
    <?= lapa_icon() ?>
    <span>La Lapa<small><?= e($config['subtitle']) ?></small></span>
  </a>
  <div class="topbar-actions">
    <?php $wa = 'https://wa.me/?text=' . rawurlencode('¡Mira las fotos de las Fiestas de La Lapa! ' . gallery_url()); ?>
    <a class="btn btn-wa btn-sm" href="<?= e($wa) ?>" target="_blank" rel="noopener"
       data-share="<?= e(gallery_url()) ?>" data-share-text="¡Mira las fotos de las Fiestas de La Lapa!" data-native>
      Compartir
    </a>
  </div>
</div>
<?php
}

function lightbox_markup(): void
{
    ?>
<div class="lb" id="lightbox" role="dialog" aria-modal="true" aria-label="Foto ampliada">
  <button class="lb-close" aria-label="Cerrar">&times;</button>
  <button class="lb-nav lb-prev" aria-label="Anterior">&#8249;</button>
  <img src="" alt="">
  <button class="lb-nav lb-next" aria-label="Siguiente">&#8250;</button>
  <div class="lb-cap"></div>
</div>
<?php
}

function page_footer(): void
{
    ?>
<footer>
  <div class="footer-links">
    <a href="index.php">Galería</a>
    <?php if (get_config()['allow_public_uploads']): ?><a href="enviar.php">Enviar fotos</a><?php endif; ?>
    <a href="privacidad.php">Aviso de privacidad</a>
    <a href="admin.php">Panel</a>
  </div>
  <p><?= e(get_config()['title']) ?> · Caleta de Caballo · Teguise (Lanzarote)</p>
</footer>
<script src="assets/app.js"></script>
</body>
</html>
<?php
}

function render_flashes(): void
{
    foreach (take_flashes() as $f) {
        echo '<div class="msg msg-ok">' . e($f) . '</div>';
    }
}
