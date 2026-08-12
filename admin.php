<?php
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/partials.php';
boot();

$config = get_config();

/* ---------------------------------------------------------------------------
 * 1) Acciones POST
 * ------------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    // -- Configurar contraseña la primera vez (no requiere sesión) --
    if ($action === 'setup' && !password_is_set()) {
        check_csrf();
        $p1 = (string) ($_POST['password'] ?? '');
        $p2 = (string) ($_POST['password2'] ?? '');
        if (strlen($p1) < 6) {
            flash('La contraseña debe tener al menos 6 caracteres.');
        } elseif ($p1 !== $p2) {
            flash('Las contraseñas no coinciden.');
        } else {
            set_password($p1);
            $_SESSION['is_admin'] = true;
            session_regenerate_id(true);
            flash('Contraseña creada. ¡Ya puedes gestionar la galería!');
        }
        header('Location: admin.php');
        exit;
    }

    // -- Iniciar sesión --
    if ($action === 'login') {
        check_csrf();
        if (attempt_login((string) ($_POST['password'] ?? ''))) {
            header('Location: admin.php');
        } else {
            flash('Contraseña incorrecta.');
            header('Location: admin.php');
        }
        exit;
    }

    // -- Cerrar sesión --
    if ($action === 'logout') {
        check_csrf();
        logout();
        header('Location: admin.php');
        exit;
    }

    // A partir de aquí, todas las acciones requieren sesión de administrador.
    require_admin();
    check_csrf();

    switch ($action) {
        case 'upload':
            $acto = (string) ($_POST['acto'] ?? 'otros');
            if (!valid_acto($acto)) { $acto = 'otros'; }
            $caption = trim((string) ($_POST['caption'] ?? ''));
            $files = $_FILES['fotos'] ?? null;
            $photos = get_photos();
            $count = 0;
            if ($files && is_array($files['name'])) {
                $total = count($files['name']);
                for ($i = 0; $i < min($total, 40); $i++) {
                    if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) { continue; }
                    $one = [
                        'name'     => $files['name'][$i],
                        'type'     => $files['type'][$i],
                        'tmp_name' => $files['tmp_name'][$i],
                        'error'    => $files['error'][$i],
                        'size'     => $files['size'][$i],
                    ];
                    $name = process_upload($one, UPLOAD_DIR);
                    if ($name === null) { continue; }
                    $photos[] = [
                        'id'       => bin2hex(random_bytes(8)),
                        'file'     => $name,
                        'acto'     => $acto,
                        'caption'  => mb_substr($caption, 0, 140),
                        'author'   => '',
                        'hidden'   => false,
                        'approved' => true,
                        'source'   => 'admin',
                        'created'  => time(),
                    ];
                    $count++;
                }
            }
            save_photos($photos);
            flash($count > 0 ? "Se han subido $count foto(s)." : 'No se pudo subir ninguna foto. Comprueba el formato y el tamaño.');
            header('Location: admin.php');
            exit;

        case 'set_acto':
            $id = (string) ($_POST['id'] ?? '');
            $acto = (string) ($_POST['acto'] ?? '');
            if (valid_acto($acto)) {
                $photos = get_photos();
                foreach ($photos as &$p) {
                    if ($p['id'] === $id) { $p['acto'] = $acto; break; }
                }
                unset($p);
                save_photos($photos);
            }
            header('Location: admin.php#foto-' . urlencode($id));
            exit;

        case 'toggle_hidden':
            $id = (string) ($_POST['id'] ?? '');
            $photos = get_photos();
            foreach ($photos as &$p) {
                if ($p['id'] === $id) { $p['hidden'] = empty($p['hidden']); break; }
            }
            unset($p);
            save_photos($photos);
            header('Location: admin.php#foto-' . urlencode($id));
            exit;

        case 'set_cover':
            $id = (string) ($_POST['id'] ?? '');
            $config['cover_photo'] = $id;
            save_config($config);
            flash('Portada actualizada.');
            header('Location: admin.php#foto-' . urlencode($id));
            exit;

        case 'delete':
            $id = (string) ($_POST['id'] ?? '');
            $photos = get_photos();
            $photo = find_photo($photos, $id);
            if ($photo) {
                delete_photo_files($photo);
                $photos = array_filter($photos, fn($p) => $p['id'] !== $id);
                save_photos($photos);
                if (($config['cover_photo'] ?? '') === $id) {
                    $config['cover_photo'] = '';
                    save_config($config);
                }
                flash('Foto eliminada.');
            }
            header('Location: admin.php');
            exit;

        case 'approve':
            $id = (string) ($_POST['id'] ?? '');
            $photos = get_photos();
            foreach ($photos as &$p) {
                if ($p['id'] === $id) {
                    // Mueve el fichero de "pending" a "uploads" y genera miniatura.
                    $src = PENDING_DIR . '/' . $p['file'];
                    $dst = UPLOAD_DIR . '/' . $p['file'];
                    if (is_file($src)) {
                        rename($src, $dst);
                        $img = @imagecreatefromjpeg($dst);
                        if ($img !== false) {
                            save_resized($img, THUMB_DIR . '/' . $p['file'], THUMB_SIDE);
                            imagedestroy($img);
                        }
                    }
                    $p['approved'] = true;
                    break;
                }
            }
            unset($p);
            save_photos($photos);
            flash('Foto publicada.');
            header('Location: admin.php#pendientes');
            exit;

        case 'reject':
            $id = (string) ($_POST['id'] ?? '');
            $photos = get_photos();
            $photo = find_photo($photos, $id);
            if ($photo && empty($photo['approved'])) {
                delete_photo_files($photo);
                $photos = array_filter($photos, fn($p) => $p['id'] !== $id);
                save_photos($photos);
                flash('Envío descartado.');
            }
            header('Location: admin.php#pendientes');
            exit;

        case 'toggle_public_uploads':
            $config['allow_public_uploads'] = empty($config['allow_public_uploads']);
            save_config($config);
            flash($config['allow_public_uploads'] ? 'Envío de fotos por vecinos: ACTIVADO.' : 'Envío de fotos por vecinos: DESACTIVADO.');
            header('Location: admin.php');
            exit;
    }

    header('Location: admin.php');
    exit;
}

/* ---------------------------------------------------------------------------
 * 2) Vistas GET
 * ------------------------------------------------------------------------- */

// -- Primer arranque: crear contraseña --
if (!password_is_set()) {
    page_head('Configurar panel');
    ?>
    <div class="admin-head"><span class="brand">🐚 Panel · La Lapa</span></div>
    <main class="wrap">
      <div class="box" style="max-width:440px;margin:34px auto">
        <h1 class="section-title"><?= lapa_icon() ?> Bienvenido/a</h1>
        <p class="muted">Es la primera vez que entras. Crea una contraseña para gestionar la galería.
           Guárdala bien: será la única forma de acceder al panel.</p>
        <?php render_flashes(); ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="setup">
          <label for="password">Nueva contraseña</label>
          <input type="password" id="password" name="password" minlength="6" required autocomplete="new-password">
          <label for="password2">Repite la contraseña</label>
          <input type="password" id="password2" name="password2" minlength="6" required autocomplete="new-password">
          <button type="submit" class="btn btn-primary btn-block" style="margin-top:16px">Crear contraseña y entrar</button>
        </form>
      </div>
    </main>
    <?php
    page_footer();
    exit;
}

// -- No autenticado: login --
if (!is_admin()) {
    page_head('Acceso al panel');
    ?>
    <div class="admin-head"><span class="brand">🐚 Panel · La Lapa</span></div>
    <main class="wrap">
      <div class="box" style="max-width:400px;margin:44px auto">
        <h1 class="section-title"><?= lapa_icon() ?> Acceso</h1>
        <?php render_flashes(); ?>
        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="login">
          <label for="password">Contraseña</label>
          <input type="password" id="password" name="password" required autocomplete="current-password" autofocus>
          <button type="submit" class="btn btn-primary btn-block" style="margin-top:16px">Entrar</button>
        </form>
        <p style="text-align:center;margin-top:16px"><a href="index.php">← Ver la galería pública</a></p>
      </div>
    </main>
    <?php
    page_footer();
    exit;
}

// -- Panel de administración --
$photos = get_photos();
$publicadas = array_filter($photos, fn($p) => !empty($p['approved']));
$pendientes = array_values(array_filter($photos, fn($p) => empty($p['approved'])));
$ocultas = array_filter($publicadas, fn($p) => !empty($p['hidden']));

// Orden: publicadas más recientes primero.
$publicadasOrd = array_values($publicadas);
usort($publicadasOrd, fn($a, $b) => ($b['created'] ?? 0) <=> ($a['created'] ?? 0));
usort($pendientes, fn($a, $b) => ($b['created'] ?? 0) <=> ($a['created'] ?? 0));

page_head('Panel de administración');
?>
<div class="admin-head">
  <span class="brand">🐚 Panel · La Lapa</span>
  <div style="display:flex;gap:8px">
    <a class="btn btn-ghost btn-sm" href="index.php" target="_blank">Ver galería</a>
    <form method="post" class="inline-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="logout">
      <button class="btn btn-ghost btn-sm" type="submit">Salir</button>
    </form>
  </div>
</div>

<main class="wrap">
  <?php render_flashes(); ?>

  <div class="stat-row" style="margin-top:18px">
    <div class="stat"><b><?= count($publicadas) ?></b><span>publicadas</span></div>
    <div class="stat"><b><?= count($ocultas) ?></b><span>ocultas</span></div>
    <div class="stat"><b><?= count($pendientes) ?></b><span>por revisar</span></div>
  </div>

  <div class="admin-nav">
    <a class="btn btn-outline btn-sm" href="#subir">⬆️ Subir</a>
    <a class="btn btn-outline btn-sm" href="#pendientes">🔎 Revisar (<?= count($pendientes) ?>)</a>
    <a class="btn btn-outline btn-sm" href="#gestionar">🗂️ Gestionar</a>
    <a class="btn btn-outline btn-sm" href="#compartir">🔗 Compartir / QR</a>
  </div>

  <!-- SUBIR -->
  <section id="subir" class="box">
    <h2 class="section-title"><?= lapa_icon() ?> Subir fotos</h2>
    <form method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="upload">
      <label for="fotos">Fotos (puedes seleccionar varias)</label>
      <input type="file" id="fotos" name="fotos[]" accept="image/*" multiple required>
      <p class="field-hint">Se redimensionan y se les quitan los metadatos automáticamente.</p>
      <label for="acto">Acto / día</label>
      <select id="acto" name="acto">
        <?php foreach ($config['actos'] as $acto): ?>
          <option value="<?= e($acto['slug']) ?>"><?= e($acto['label']) ?></option>
        <?php endforeach; ?>
      </select>
      <label for="caption">Comentario para todas (opcional)</label>
      <input type="text" id="caption" name="caption" maxlength="140" placeholder="Ej.: Procesión marítima 2026">
      <button type="submit" class="btn btn-primary btn-block" style="margin-top:16px">Subir y publicar</button>
    </form>
  </section>

  <!-- PENDIENTES -->
  <section id="pendientes" class="box">
    <h2 class="section-title">🔎 Fotos enviadas por vecinos <span class="muted" style="font-size:1rem">(<?= count($pendientes) ?>)</span></h2>
    <?php if (empty($pendientes)): ?>
      <p class="muted">No hay fotos pendientes de revisión.</p>
    <?php else: ?>
      <div class="admin-grid">
        <?php foreach ($pendientes as $p): ?>
          <div class="admin-card" id="foto-<?= e($p['id']) ?>">
            <div class="thumb">
              <img src="uploads/pending/<?= e($p['file']) ?>" alt="" loading="lazy">
            </div>
            <div class="meta">
              <span class="badge badge-public">Vecino</span>
              <?php if (!empty($p['author'])): ?><small class="muted"> · <?= e($p['author']) ?></small><?php endif; ?>
              <p style="margin:6px 0;font-size:.85rem"><strong><?= e(acto_label($p['acto'])) ?></strong>
                 <?php if (!empty($p['caption'])): ?><br><span class="muted"><?= e($p['caption']) ?></span><?php endif; ?></p>
              <div class="actions">
                <form method="post" class="inline-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="approve">
                  <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                  <button class="btn btn-mar btn-sm" type="submit">✓ Publicar</button>
                </form>
                <form method="post" class="inline-form" onsubmit="return confirm('¿Descartar esta foto?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="reject">
                  <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                  <button class="btn btn-danger btn-sm" type="submit">✕ Descartar</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- GESTIONAR -->
  <section id="gestionar" class="box">
    <h2 class="section-title">🗂️ Fotos publicadas <span class="muted" style="font-size:1rem">(<?= count($publicadasOrd) ?>)</span></h2>
    <?php if (empty($publicadasOrd)): ?>
      <p class="muted">Aún no has publicado ninguna foto. Sube las primeras arriba ⬆️</p>
    <?php else: ?>
      <div class="admin-grid">
        <?php foreach ($publicadasOrd as $p): ?>
          <?php
            $esPortada = ($config['cover_photo'] ?? '') === $p['id'];
            $thumb = is_file(THUMB_DIR . '/' . $p['file']) ? 'uploads/thumbs/' . $p['file'] : 'uploads/' . $p['file'];
          ?>
          <div class="admin-card<?= !empty($p['hidden']) ? ' is-hidden' : '' ?>" id="foto-<?= e($p['id']) ?>">
            <div class="thumb"><img src="<?= e($thumb) ?>" alt="" loading="lazy"></div>
            <div class="meta">
              <?php if ($esPortada): ?><span class="badge badge-cover">Portada</span><?php endif; ?>
              <?php if (!empty($p['hidden'])): ?><span class="badge badge-hidden">Oculta</span><?php endif; ?>
              <?php if (($p['source'] ?? '') === 'public'): ?><span class="badge badge-public">Vecino</span><?php endif; ?>

              <form method="post" style="margin-top:8px">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="set_acto">
                <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                <select name="acto" onchange="this.form.submit()" aria-label="Cambiar acto">
                  <?php foreach ($config['actos'] as $acto): ?>
                    <option value="<?= e($acto['slug']) ?>"<?= ($p['acto'] ?? '') === $acto['slug'] ? ' selected' : '' ?>><?= e($acto['label']) ?></option>
                  <?php endforeach; ?>
                </select>
              </form>

              <div class="actions">
                <form method="post" class="inline-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="set_cover">
                  <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                  <button class="btn btn-outline btn-sm" type="submit" <?= $esPortada ? 'disabled' : '' ?>>★ Portada</button>
                </form>
                <form method="post" class="inline-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="toggle_hidden">
                  <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                  <button class="btn btn-ghost btn-sm" type="submit"><?= !empty($p['hidden']) ? '👁 Mostrar' : '🚫 Ocultar' ?></button>
                </form>
                <form method="post" class="inline-form" onsubmit="return confirm('¿Eliminar esta foto definitivamente?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= e($p['id']) ?>">
                  <button class="btn btn-danger btn-sm" type="submit">🗑 Borrar</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- COMPARTIR / QR -->
  <section id="compartir" class="box">
    <h2 class="section-title">🔗 Compartir la galería</h2>
    <p>Enlace público de la galería:</p>
    <p><input type="text" readonly value="<?= e(gallery_url()) ?>" onclick="this.select()"></p>
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:8px">
      <button class="btn btn-outline btn-sm" type="button" data-copy="<?= e(gallery_url()) ?>">Copiar enlace</button>
      <a class="btn btn-wa btn-sm" target="_blank" rel="noopener"
         href="https://wa.me/?text=<?= rawurlencode('¡Mira las fotos de las Fiestas de La Lapa! ' . gallery_url()) ?>">Compartir por WhatsApp</a>
    </div>
    <p class="field-hint" style="margin-top:14px">
      Para el <strong>QR del programa</strong>: genera un código QR que apunte a la dirección de arriba
      (por ejemplo con un generador gratuito online) e imprímelo en los carteles. Al escanearlo, la gente
      llegará directamente a la galería.
    </p>

    <hr style="border:none;border-top:1px solid #e6eef1;margin:20px 0">
    <h3>Envío de fotos por vecinos</h3>
    <p class="muted" style="margin-top:4px">
      Actualmente está <strong><?= $config['allow_public_uploads'] ? 'ACTIVADO' : 'DESACTIVADO' ?></strong>.
      <?= $config['allow_public_uploads'] ? 'Los vecinos pueden enviarte fotos para que las revises.' : 'Los vecinos no pueden enviar fotos.' ?>
    </p>
    <form method="post" class="inline-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="toggle_public_uploads">
      <button class="btn btn-outline btn-sm" type="submit"><?= $config['allow_public_uploads'] ? 'Desactivar envíos' : 'Activar envíos' ?></button>
    </form>
  </section>

</main>
<?php page_footer(); ?>
