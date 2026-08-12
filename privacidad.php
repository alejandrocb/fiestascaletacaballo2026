<?php
require __DIR__ . '/lib/bootstrap.php';
require __DIR__ . '/lib/partials.php';
boot();

page_head('Aviso de privacidad');
public_topbar();
?>
<main class="wrap">
  <div class="box">
    <h1 class="section-title"><?= lapa_icon() ?> Aviso de privacidad y uso de imágenes</h1>

    <p>Esta web recoge fotografías de las <strong>Fiestas de La Lapa de Caleta de Caballo</strong>
       (Teguise, Lanzarote) con la única finalidad de difundir y dejar memoria de las fiestas.</p>

    <h3>Publicación de imágenes</h3>
    <ul>
      <li>Solo se publican imágenes para las que se cuenta con autorización de quien las remite
          o de las personas responsables de quienes aparecen en ellas.</li>
      <li>Las fotografías enviadas por vecinos se revisan antes de publicarse.</li>
      <li>Al subir o enviar una foto, la persona confirma que tiene derecho a compartirla y que
          cuenta con el consentimiento de las personas identificables que aparezcan en ella.</li>
    </ul>

    <h3>Menores de edad</h3>
    <p>Se presta especial cuidado con las imágenes en las que aparecen menores. Solo se publicarán
       cuando exista <strong>autorización de sus padres, madres o tutores legales</strong>. Si detectas
       una imagen de un menor a tu cargo publicada sin tu consentimiento, contáctanos y la retiraremos
       de inmediato.</p>

    <h3>Retirada de fotos (derecho de oposición)</h3>
    <p>Cualquier persona que aparezca en una foto —o su representante legal— puede solicitar su retirada.
       Atenderemos la petición y eliminaremos la imagen lo antes posible.</p>

    <h3>Datos técnicos</h3>
    <p>Al publicar o enviar fotos, se eliminan automáticamente los metadatos de la imagen (incluida la
       geolocalización). No se utilizan cookies de seguimiento ni se ceden imágenes a terceros con fines
       comerciales.</p>

    <p class="muted" style="margin-top:22px">
      Para cualquier consulta o solicitud relacionada con las imágenes, ponte en contacto con la
      organización de las fiestas.
    </p>

    <p style="margin-top:20px"><a class="btn btn-mar" href="index.php">← Volver a la galería</a></p>
  </div>
</main>
<?php page_footer(); ?>
