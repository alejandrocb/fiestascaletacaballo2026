<?php
/**
 * Fiestas de La Lapa · Caleta de Caballo
 * Núcleo común: configuración, sesión, almacenamiento en JSON,
 * autenticación, CSRF y tratamiento de imágenes.
 *
 * Todo se guarda en ficheros JSON (sin base de datos) para que la web
 * sea fácil de instalar en cualquier hosting con PHP.
 */

declare(strict_types=1);

// --- Rutas base -------------------------------------------------------------
define('APP_ROOT', dirname(__DIR__));
define('DATA_DIR', APP_ROOT . '/data');
define('UPLOAD_DIR', APP_ROOT . '/uploads');
define('THUMB_DIR', UPLOAD_DIR . '/thumbs');
define('PENDING_DIR', UPLOAD_DIR . '/pending');

define('CONFIG_FILE', DATA_DIR . '/config.json');
define('PHOTOS_FILE', DATA_DIR . '/photos.json');

// Límites y formatos admitidos
define('MAX_UPLOAD_BYTES', 20 * 1024 * 1024); // 20 MB por foto
define('MAX_IMAGE_SIDE', 2000);               // lado máximo de la foto publicada
define('THUMB_SIDE', 700);                     // lado máximo de la miniatura
const ALLOWED_MIME = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

// --- Configuración por defecto ---------------------------------------------
/**
 * Se crea automáticamente en el primer arranque si no existe config.json.
 * La contraseña NO viene fijada: el panel pedirá crearla la primera vez.
 */
function default_config(): array
{
    return [
        'title'         => 'Fiestas de La Lapa',
        'subtitle'      => 'Caleta de Caballo · 2026',
        'municipio'     => 'Teguise · Lanzarote',
        'password_hash' => '',      // se rellena al configurar el panel
        'cover_photo'   => '',      // id de la foto usada como portada
        'allow_public_uploads' => true, // permitir que los vecinos envíen fotos
        // Actos / días de las fiestas. Editable a mano si hace falta.
        'actos' => [
            ['slug' => 'pregon',      'label' => 'Pregón'],
            ['slug' => 'bolas',       'label' => 'Torneo de bolas'],
            ['slug' => 'comida',      'label' => 'Comida popular'],
            ['slug' => 'infantiles',  'label' => 'Juegos infantiles'],
            ['slug' => 'talleres',    'label' => 'Talleres'],
            ['slug' => 'parrandas',   'label' => 'Noche de parrandas'],
            ['slug' => 'peluca',      'label' => 'Carrera de La Peluca'],
            ['slug' => 'playback',    'label' => 'Playback'],
            ['slug' => 'bingo',       'label' => 'Bingo'],
            ['slug' => 'conciertos',  'label' => 'Conciertos'],
            ['slug' => 'verbena',     'label' => 'Verbena'],
            ['slug' => 'procesion',   'label' => 'Procesión marítima de La Lapa'],
            ['slug' => 'otros',       'label' => 'Otros momentos'],
        ],
    ];
}

// --- Arranque ---------------------------------------------------------------
function boot(): void
{
    foreach ([DATA_DIR, UPLOAD_DIR, THUMB_DIR, PENDING_DIR] as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    if (!file_exists(CONFIG_FILE)) {
        save_json(CONFIG_FILE, default_config());
    }
    if (!file_exists(PHOTOS_FILE)) {
        save_json(PHOTOS_FILE, []);
    }

    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// --- Persistencia en JSON (con bloqueo) -------------------------------------
function load_json(string $file): array
{
    if (!file_exists($file)) {
        return [];
    }
    $raw = file_get_contents($file);
    if ($raw === false || $raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function save_json(string $file, array $data): void
{
    $tmp = $file . '.tmp';
    file_put_contents(
        $tmp,
        json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        LOCK_EX
    );
    rename($tmp, $file); // reemplazo atómico
}

function get_config(): array
{
    return array_merge(default_config(), load_json(CONFIG_FILE));
}

function save_config(array $config): void
{
    save_json(CONFIG_FILE, $config);
}

function get_photos(): array
{
    return load_json(PHOTOS_FILE);
}

function save_photos(array $photos): void
{
    save_json(PHOTOS_FILE, array_values($photos));
}

// --- Autenticación ----------------------------------------------------------
function password_is_set(): bool
{
    return get_config()['password_hash'] !== '';
}

function is_admin(): bool
{
    return !empty($_SESSION['is_admin']);
}

function require_admin(): void
{
    if (!is_admin()) {
        header('Location: admin.php');
        exit;
    }
}

function attempt_login(string $password): bool
{
    $config = get_config();
    if ($config['password_hash'] === '') {
        return false;
    }
    if (password_verify($password, $config['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['is_admin'] = true;
        return true;
    }
    return false;
}

function set_password(string $password): void
{
    $config = get_config();
    $config['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
    save_config($config);
}

function logout(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

// --- CSRF -------------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token()) . '">';
}

function check_csrf(): void
{
    $token = $_POST['csrf'] ?? '';
    if (!is_string($token) || $token === '' || !hash_equals(csrf_token(), $token)) {
        http_response_code(400);
        exit('Solicitud no válida (CSRF).');
    }
}

// --- Utilidades -------------------------------------------------------------
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

function base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $dir  = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    return $scheme . '://' . $host . $dir;
}

function gallery_url(): string
{
    return base_url() . '/index.php';
}

function acto_label(string $slug): string
{
    foreach (get_config()['actos'] as $acto) {
        if ($acto['slug'] === $slug) {
            return $acto['label'];
        }
    }
    return 'Otros momentos';
}

function valid_acto(string $slug): bool
{
    foreach (get_config()['actos'] as $acto) {
        if ($acto['slug'] === $slug) {
            return true;
        }
    }
    return false;
}

function find_photo(array $photos, string $id): ?array
{
    foreach ($photos as $p) {
        if ($p['id'] === $id) {
            return $p;
        }
    }
    return null;
}

function flash(string $msg): void
{
    $_SESSION['flash'][] = $msg;
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// --- Tratamiento de imágenes ------------------------------------------------
/**
 * Procesa una imagen subida: valida, corrige orientación EXIF,
 * redimensiona, elimina metadatos (incluida geolocalización) reguardándola
 * como JPEG y genera una miniatura.
 *
 * Devuelve el nombre de fichero generado o null si falla.
 */
function process_upload(array $file, string $destDir): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return null;
    }
    if ($file['size'] <= 0 || $file['size'] > MAX_UPLOAD_BYTES) {
        return null;
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime  = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ALLOWED_MIME, true)) {
        return null;
    }

    $data = file_get_contents($file['tmp_name']);
    if ($data === false) {
        return null;
    }
    $img = @imagecreatefromstring($data);
    if ($img === false) {
        return null;
    }

    // Corrige orientación según EXIF (solo JPEG) antes de perder los metadatos.
    if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        $orientation = $exif['Orientation'] ?? 0;
        if ($orientation === 3) {
            $img = imagerotate($img, 180, 0);
        } elseif ($orientation === 6) {
            $img = imagerotate($img, -90, 0);
        } elseif ($orientation === 8) {
            $img = imagerotate($img, 90, 0);
        }
    }

    $name = bin2hex(random_bytes(12)) . '.jpg';

    // Imagen principal (máx. MAX_IMAGE_SIDE) y miniatura (máx. THUMB_SIDE).
    if (!save_resized($img, $destDir . '/' . $name, MAX_IMAGE_SIDE)) {
        imagedestroy($img);
        return null;
    }
    // La miniatura solo se genera para las publicaciones definitivas.
    if ($destDir === UPLOAD_DIR) {
        save_resized($img, THUMB_DIR . '/' . $name, THUMB_SIDE);
    }

    imagedestroy($img);
    return $name;
}

function save_resized(\GdImage $img, string $path, int $maxSide): bool
{
    $w = imagesx($img);
    $h = imagesy($img);
    $scale = min(1.0, $maxSide / max($w, $h));
    $nw = max(1, (int) round($w * $scale));
    $nh = max(1, (int) round($h * $scale));

    if ($scale < 1.0) {
        $dst = imagecreatetruecolor($nw, $nh);
        // Fondo blanco por si la imagen original tenía transparencia.
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefill($dst, 0, 0, $white);
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);
        $ok = imagejpeg($dst, $path, 82);
        imagedestroy($dst);
        return $ok;
    }

    return imagejpeg($img, $path, 82);
}

function delete_photo_files(array $photo): void
{
    foreach ([
        UPLOAD_DIR . '/' . $photo['file'],
        THUMB_DIR . '/' . $photo['file'],
        PENDING_DIR . '/' . $photo['file'],
    ] as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
}

function public_photos(?string $acto = null): array
{
    $photos = array_filter(get_photos(), function ($p) use ($acto) {
        if (empty($p['approved']) || !empty($p['hidden'])) {
            return false;
        }
        if ($acto !== null && $acto !== '' && ($p['acto'] ?? '') !== $acto) {
            return false;
        }
        return true;
    });
    // Más recientes primero.
    usort($photos, fn($a, $b) => ($b['created'] ?? 0) <=> ($a['created'] ?? 0));
    return $photos;
}
