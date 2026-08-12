<?php
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/partials.php';
boot();

$config = get_config();
$actoFiltro = isset($_GET['acto']) ? (string) $_GET['acto'] : '';
if ($actoFiltro !== '' && !valid_acto($actoFiltro)) {
    $actoFiltro = '';
}

$fotos = public_photos($actoFiltro !== '' ? $actoFiltro : null);

// Foto de portada (elegida en el panel) o, si no hay, la más reciente.
$coverFile = '';
if ($config['cover_photo'] !== '') {
    $cover = find_photo(get_photos(), $config['cover_photo']);
    if ($cover && empty($cover['hidden']) && !empty($cover['approved'])) {
        $coverFile = 'uploads/' . $cover['file'];
    }
}
if ($coverFile === '') {
    $todas = public_photos();
    if (!empty($todas)) {
        $coverFile = 'uploads/' . $todas[0]['file'];
    }
}

// ¿Qué actos tienen fotos? Para mostrar solo filtros útiles.
$actosConFotos = [];
foreach (public_photos() as $p) {
    $actosConFotos[$p['acto'] ?? 'otros'] = true;
}

page_head('');
?>

<!-- PORTADA -->
<header class="hero<?= $coverFile ? ' has-photo' : '' ?>"<?= $coverFile ? ' style="background-image:url(\'' . e($coverFile) . '\')"' : '' ?>>
  <div class="hero-inner">
    <p class="kicker">Caleta de Caballo · Teguise</p>
    <h1><?= e($config['title']) ?></h1>
    <p class="sub"><?= e($config['subtitle']) ?></p>
    <a class="btn btn-primary" href="#galeria">📷 Ver fotos</a>
  </div>
</header>
<svg class="wave" viewBox="0 0 1440 40" preserveAspectRatio="none" xmlns="http://www.w3.org/2000/svg">
  <path d="M0 20 C 180 40 360 0 540 16 C 720 32 900 4 1080 18 C 1260 32 1350 24 1440 16 L1440 40 L0 40 Z" fill="currentColor"/>
</svg>

<main class="wrap" id="galeria">

  <?php $wa = 'https://wa.me/?text=' . rawurlencode('¡Mira las fotos de las Fiestas de La Lapa! ' . gallery_url()); ?>
  <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;justify-content:space-between;margin-top:18px">
    <h2 class="section-title" style="margin:0"><?= lapa_icon() ?> Galería de las fiestas</h2>
    <a class="btn btn-wa btn-sm" href="<?= e($wa) ?>" target="_blank" rel="noopener"
       data-share="<?= e(gallery_url()) ?>" data-share-text="¡Mira las fotos de las Fiestas de La Lapa!" data-native>
      Compartir por WhatsApp
    </a>
  </div>

  <!-- Filtros por acto -->
  <?php if (!empty($actosConFotos)): ?>
  <nav class="filtros" aria-label="Filtrar por acto">
    <a class="chip<?= $actoFiltro === '' ? ' active' : '' ?>" href="index.php#galeria">Todas</a>
    <?php foreach ($config['actos'] as $acto): ?>
      <?php if (!empty($actosConFotos[$acto['slug']])): ?>
        <a class="chip<?= $actoFiltro === $acto['slug'] ? ' active' : '' ?>"
           href="index.php?acto=<?= e($acto['slug']) ?>#galeria"><?= e($acto['label']) ?></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </nav>
  <?php endif; ?>

  <?php if (empty($fotos)): ?>
    <div class="empty">
      <div class="shell">🐚</div>
      <h3>Todavía no hay fotos aquí</h3>
      <p class="muted">Durante las fiestas iremos colgando las mejores imágenes. ¡Vuelve pronto!</p>
      <?php if ($config['allow_public_uploads']): ?>
        <a class="btn btn-mar" href="enviar.php">Enviar tus fotos</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="grid">
      <?php foreach ($fotos as $p): ?>
        <?php
          $thumb = is_file(THUMB_DIR . '/' . $p['file']) ? 'uploads/thumbs/' . $p['file'] : 'uploads/' . $p['file'];
          $cap = trim(($p['caption'] ?? '') !== '' ? $p['caption'] : acto_label($p['acto'] ?? 'otros'));
        ?>
        <figure class="card">
          <img src="<?= e($thumb) ?>" alt="<?= e($cap) ?>" loading="lazy"
               data-full="uploads/<?= e($p['file']) ?>" data-cap="<?= e($cap) ?>">
          <span class="tag"><?= e(acto_label($p['acto'] ?? 'otros')) ?></span>
        </figure>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="aviso">
    <strong>Aviso de privacidad.</strong> Estas fotos se publican con fines de difusión de las fiestas.
    Si apareces (o aparece un menor a tu cargo) y no deseas que una imagen siga publicada,
    escríbenos y la retiraremos. Consulta el <a href="privacidad.php">aviso completo</a>.
  </div>

  <?php if ($config['allow_public_uploads']): ?>
    <p style="text-align:center;margin:24px 0">
      <a class="btn btn-outline" href="enviar.php">¿Tienes fotos? Envíalas aquí</a>
    </p>
  <?php endif; ?>

</main>

<?php lightbox_markup(); ?>
<?php page_footer(); ?>
