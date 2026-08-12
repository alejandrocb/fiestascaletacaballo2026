/* Interacción sencilla: visor de fotos (lightbox) y compartir. */
(function () {
  'use strict';

  // --- Lightbox ---
  var lb = document.getElementById('lightbox');
  if (lb) {
    var lbImg = lb.querySelector('img');
    var lbCap = lb.querySelector('.lb-cap');
    var items = Array.prototype.slice.call(document.querySelectorAll('[data-full]'));
    var current = -1;

    function show(i) {
      if (i < 0 || i >= items.length) return;
      current = i;
      lbImg.src = items[i].getAttribute('data-full');
      lbCap.textContent = items[i].getAttribute('data-cap') || '';
      lb.classList.add('open');
      document.body.style.overflow = 'hidden';
    }
    function close() {
      lb.classList.remove('open');
      lbImg.src = '';
      document.body.style.overflow = '';
    }

    items.forEach(function (el, i) {
      el.addEventListener('click', function () { show(i); });
    });

    lb.querySelector('.lb-close').addEventListener('click', close);
    lb.querySelector('.lb-prev').addEventListener('click', function () { show((current - 1 + items.length) % items.length); });
    lb.querySelector('.lb-next').addEventListener('click', function () { show((current + 1) % items.length); });
    lb.addEventListener('click', function (e) { if (e.target === lb) close(); });
    document.addEventListener('keydown', function (e) {
      if (!lb.classList.contains('open')) return;
      if (e.key === 'Escape') close();
      if (e.key === 'ArrowLeft') show((current - 1 + items.length) % items.length);
      if (e.key === 'ArrowRight') show((current + 1) % items.length);
    });
  }

  // --- Compartir ---
  var shareBtns = document.querySelectorAll('[data-share]');
  Array.prototype.forEach.call(shareBtns, function (btn) {
    btn.addEventListener('click', function (e) {
      var url = btn.getAttribute('data-share');
      var text = btn.getAttribute('data-share-text') || '';
      // Si el móvil soporta el menú nativo de compartir, úsalo.
      if (navigator.share && btn.hasAttribute('data-native')) {
        e.preventDefault();
        navigator.share({ title: document.title, text: text, url: url }).catch(function () {});
      }
    });
  });

  // --- Copiar enlace ---
  var copyBtns = document.querySelectorAll('[data-copy]');
  Array.prototype.forEach.call(copyBtns, function (btn) {
    btn.addEventListener('click', function () {
      var text = btn.getAttribute('data-copy');
      if (navigator.clipboard) {
        navigator.clipboard.writeText(text).then(function () {
          var old = btn.textContent;
          btn.textContent = '¡Copiado!';
          setTimeout(function () { btn.textContent = old; }, 1600);
        });
      }
    });
  });
})();
