(function () {
  'use strict';

  function initBeforeAfter(section) {
    const stage = section.querySelector('.aistea-ba__stage');
    const handle = section.querySelector('.aistea-ba__handle');
    const beforePane = section.querySelector('.aistea-ba__pane--before');
    const afterPane = section.querySelector('.aistea-ba__pane--after');

    if (!stage || !handle || !beforePane || !afterPane) return;

    const initialPos = Math.max(0, Math.min(100, parseFloat(section.dataset.initialPosition ?? '50')));
    let isDragging = false;

    function setPosition(pos) {
      pos = Math.max(0, Math.min(100, pos));
      stage.style.setProperty('--ba-position', pos.toFixed(2) + '%');
      handle.style.left = pos.toFixed(2) + '%';
      handle.setAttribute('aria-valuenow', String(Math.round(pos)));
    }

    function getPosFromPointer(clientX) {
      const rect = section.getBoundingClientRect();
      return ((clientX - rect.left) / rect.width) * 100;
    }

    setPosition(initialPos);

    // Mouse
    section.addEventListener('mousedown', function (e) {
      isDragging = true;
      setPosition(getPosFromPointer(e.clientX));
      e.preventDefault();
    });

    window.addEventListener('mousemove', function (e) {
      if (isDragging) setPosition(getPosFromPointer(e.clientX));
    });

    window.addEventListener('mouseup', function () {
      isDragging = false;
    });

    // Touch
    section.addEventListener('touchstart', function (e) {
      isDragging = true;
      setPosition(getPosFromPointer(e.touches[0].clientX));
    }, { passive: true });

    window.addEventListener('touchmove', function (e) {
      if (isDragging) setPosition(getPosFromPointer(e.touches[0].clientX));
    }, { passive: true });

    window.addEventListener('touchend', function () {
      isDragging = false;
    });

    // Keyboard (on handle)
    handle.addEventListener('keydown', function (e) {
      const step    = e.shiftKey ? 10 : 1;
      const current = parseFloat(handle.getAttribute('aria-valuenow') ?? '50');
      if (e.key === 'ArrowLeft')  { e.preventDefault(); setPosition(current - step); }
      if (e.key === 'ArrowRight') { e.preventDefault(); setPosition(current + step); }
      if (e.key === 'Home')       { e.preventDefault(); setPosition(0); }
      if (e.key === 'End')        { e.preventDefault(); setPosition(100); }
    });
  }

  document.querySelectorAll('.aistea-ba').forEach(initBeforeAfter);
})();
