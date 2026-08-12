# Fiestas de La Lapa · Caleta de Caballo

Web sencilla para las **Fiestas de La Lapa de Caleta de Caballo** (Teguise, Lanzarote).

- **Página pública**: portada con foto del pueblo/mar, botón grande *Ver fotos* y galería
  filtrable por actos (Pregón, Parrandas, Juegos infantiles, Playback, Verbena,
  Procesión marítima…). Botón de compartir por WhatsApp.
- **Panel privado** (una sola contraseña): subir fotos, elegir la portada, asignarlas a un
  acto, ocultarlas o borrarlas, y revisar las fotos que envían los vecinos.
- **Envío de fotos por vecinos** con revisión previa del administrador.
- **Aviso de privacidad** y protección de menores: las imágenes se publican solo con
  autorización y se les eliminan los metadatos (incluida la geolocalización).

No usa base de datos: todo se guarda en ficheros JSON. Solo necesita **PHP 8+ con la
extensión GD** (habitual en cualquier hosting).

## Estructura

```
fiestas-lapa/
├── index.php         Portada + galería pública
├── admin.php         Panel privado (login, subir, gestionar, revisar)
├── enviar.php        Formulario para que los vecinos envíen fotos
├── privacidad.php    Aviso de privacidad y uso de imágenes
├── assets/           CSS y JavaScript (visor de fotos, compartir)
├── lib/              Código común (config, almacenamiento, imágenes, HTML)
├── data/             config.json y photos.json (se crean solos; NO se versionan)
└── uploads/          Fotos publicadas, miniaturas (thumbs) y envíos por revisar (pending)
```

## Puesta en marcha

### Opción rápida (para probar en tu ordenador)

```bash
cd fiestas-lapa
php -S localhost:8000
```

Abre `http://localhost:8000/` (galería) y `http://localhost:8000/admin.php` (panel).

### En un hosting

1. Sube la carpeta `fiestas-lapa/` a tu hosting con PHP 8 y GD.
2. Asegúrate de que la carpeta `data/` y `uploads/` tienen permisos de escritura.
3. Entra en `.../admin.php`: la **primera vez** te pedirá crear la contraseña del panel.

> La contraseña se guarda cifrada (hash bcrypt) en `data/config.json`. Ese fichero está
> protegido con un `.htaccess` para que no sea accesible por web y **no se sube al
> repositorio**. Si olvidas la contraseña, borra `data/config.json` y el panel volverá a
> pedirte que crees una nueva.

## Uso del panel

- **Subir**: selecciona una o varias fotos, elige el acto y súbelas. Se redimensionan y se
  les quitan los metadatos automáticamente.
- **Revisar**: aprueba o descarta las fotos que envían los vecinos.
- **Gestionar**: cambia el acto de una foto, márcala como **portada**, ocúltala o bórrala.
- **Compartir / QR**: copia el enlace de la galería o compártelo por WhatsApp. Para el QR
  del programa, genera un código QR que apunte a ese enlace e imprímelo en los carteles.

## Configuración (opcional)

Puedes editar `data/config.json` a mano para cambiar el título, el subtítulo o la lista de
actos. Si el fichero no existe, se crea con valores por defecto al arrancar.

```json
{
  "title": "Fiestas de La Lapa",
  "subtitle": "Caleta de Caballo · 2026",
  "allow_public_uploads": true,
  "actos": [
    { "slug": "pregon", "label": "Pregón" },
    { "slug": "procesion", "label": "Procesión marítima de La Lapa" }
  ]
}
```

## Privacidad

Publica únicamente imágenes para las que tengas autorización. Con menores, solo con permiso
de sus padres, madres o tutores. La web incluye un aviso de privacidad y permite retirar
cualquier foto a petición de las personas que aparezcan en ella.
