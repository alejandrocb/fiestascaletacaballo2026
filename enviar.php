<?php
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/partials.php';
boot();

$config = get_config();

// Si el envío público está desactivado, no se muestra el formulario.
if (!$config['allow_public_uploads']) {
    page_head('Enviar fotos');
    public_topbar();
    echo '<main class="wrap"><div class="box"><h1>Envío de fotos no disponible</h1>'
       . '<p class="muted">En este momento no se admiten envíos de fotos. Puedes ver la galería.</p>'
       . '<a class="btn btn-mar" href="index.php">Ver galería</a></div></main>';
    page_footer();
    exit;
}

$errores = [];
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $autor   = trim((string) ($_POST['autor'] ?? ''));
    $acto    = (string) ($_POST['acto'] ?? 'otros');
    $caption = trim((string) ($_POST['caption'] ?? ''));
    $consent = !empty($_POST['consent']);

    if (!valid_acto($acto)) {
        $acto = 'otros';
    }
    if (!$consent) {
        $errores[] = 'Debes confirmar que tienes permiso para compartir las fotos.';
    }

    $files = $_FILES['fotos'] ?? null;
    $tieneFicheros = $files && is_array($files['name']) && array_filter($files['name']);
    if (!$tieneFicheros) {
        $errores[] = 'Selecciona al menos una foto.';
    }

    if (empty($errores)) {
        $photos = get_photos();
        $subidas = 0;
        $total = count($files['name']);
        for ($i = 0; $i < min($total, 10); $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $one = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];
            // Se guardan en "pending" a la espera de revisión del administrador.
            $name = process_upload($one, PENDING_DIR);
            if ($name === null) {
                continue;
            }
            $photos[] = [
                'id'       => bin2hex(random_bytes(8)),
                'file'     => $name,
                'acto'     => $acto,
                'caption'  => mb_substr($caption, 0, 140),
                'author'   => mb_substr($autor, 0, 60),
                'hidden'   => false,
                'approved' => false,   // pendiente de revisión
                'source'   => 'public',
                'created'  => time(),
            ];
            $subidas++;
        }
        save_photos($photos);

        if ($subidas > 0) {
            $ok = true;
        } else {
            $errores[] = 'No se pudo procesar ninguna foto. Asegúrate de que son imágenes válidas (JPG, PNG…).';
        }
    }
}

page_head('Enviar fotos');
public_topbar();
?>
<main class="wrap">
  <div class="box">
    <h1 class="section-title"><?= lapa_icon() ?> Envía tus fotos de las fiestas</h1>
    <p class="muted">Comparte tus mejores momentos. Las fotos se revisan antes de publicarse en la galería.</p>

    <?php if ($ok): ?>
      <div class="msg msg-ok">¡Gracias! Hemos recibido tus fotos. Las revisaremos y, si todo está correcto, aparecerán en la galería.</div>
      <a class="btn btn-mar" href="index.php">Volver a la galería</a>
      <a class="btn btn-ghost" href="enviar.php">Enviar más</a>
    <?php else: ?>

      <?php foreach ($errores as $err): ?>
        <div class="msg msg-err"><?= e($err) ?></div>
      <?php endforeach; ?>

      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <label for="fotos">Fotos (puedes elegir varias)</label>
        <input type="file" id="fotos" name="fotos[]" accept="image/*" multiple required>
        <p class="field-hint">Máximo 10 fotos por envío. Formatos: JPG, PNG, WEBP.</p>

        <label for="acto">¿De qué acto son?</label>
        <select id="acto" name="acto">
          <?php foreach ($config['actos'] as $acto): ?>
            <option value="<?= e($acto['slug']) ?>"><?= e($acto['label']) ?></option>
          <?php endforeach; ?>
        </select>

        <label for="autor">Tu nombre (opcional)</label>
        <input type="text" id="autor" name="autor" maxlength="60" placeholder="Para dar las gracias 🙂">

        <label for="caption">Comentario (opcional)</label>
        <input type="text" id="caption" name="caption" maxlength="140" placeholder="Ej.: La procesión saliendo del muelle">

        <label class="check">
          <input type="checkbox" name="consent" value="1" required>
          <span>Confirmo que tengo permiso para compartir estas fotos y que cuento con el consentimiento
          de las personas que aparecen en ellas (o de sus tutores, si son menores). He leído el
          <a href="privacidad.php" target="_blank">aviso de privacidad</a>.</span>
        </label>

        <button type="submit" class="btn btn-primary btn-block">Enviar fotos</button>
      </form>
    <?php endif; ?>
  </div>
</main>
<?php page_footer(); ?>
