(function () {
  'use strict';

  function initHotspotImage(section) {
    const stage  = section.querySelector('.aistea-hi__stage');
    const dots   = Array.from(section.querySelectorAll('.aistea-hi__dot'));
    const panels = Array.from(section.querySelectorAll('.aistea-hi__panel'));

    if (!stage || dots.length === 0) return;

    var activeDot = null;

    function closeAll() {
      dots.forEach(function (d) {
        d.setAttribute('aria-expanded', 'false');
        d.classList.remove('is-active');
      });
      panels.forEach(function (p) {
        p.hidden = true;
        p.setAttribute('aria-hidden', 'true');
      });
      activeDot = null;
    }

    function openPanel(dot) {
      if (activeDot === dot) {
        closeAll();
        return;
      }
      closeAll();

      var panelId = dot.getAttribute('aria-controls');
      var panel   = panelId ? section.querySelector('#' + panelId) : null;
      if (!panel) return;

      dot.setAttribute('aria-expanded', 'true');
      dot.classList.add('is-active');
      panel.hidden = false;
      panel.setAttribute('aria-hidden', 'false');
      activeDot = dot;

      positionPanel(panel, dot);
    }

    function positionPanel(panel, dot) {
      panel.style.left      = '';
      panel.style.right     = '';
      panel.style.top       = '';
      panel.style.transform = '';

      var stageRect = stage.getBoundingClientRect();
      var dotRect   = dot.getBoundingClientRect();

      var dotXPct = ((dotRect.left + dotRect.width  / 2 - stageRect.left) / stageRect.width)  * 100;
      var dotYPct = ((dotRect.top  + dotRect.height / 2 - stageRect.top)  / stageRect.height) * 100;

      panel.style.top       = dotYPct.toFixed(2) + '%';
      panel.style.transform = 'translateY(-50%)';

      if (dotXPct > 60) {
        panel.style.right = (100 - dotXPct + 3).toFixed(2) + '%';
        panel.style.left  = 'auto';
      } else {
        panel.style.left  = (dotXPct + 3).toFixed(2) + '%';
        panel.style.right = 'auto';
      }
    }

    dots.forEach(function (dot) {
      dot.addEventListener('click', function () { openPanel(dot); });
      dot.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openPanel(dot); }
        if (e.key === 'Escape') closeAll();
      });
    });

    panels.forEach(function (panel) {
      var closeBtn = panel.querySelector('.aistea-hi__panel-close');
      if (closeBtn) {
        closeBtn.addEventListener('click', closeAll);
      }
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && activeDot) closeAll();
    });

    section.addEventListener('click', function (e) {
      var target = e.target;
      if (!target.closest('.aistea-hi__dot') && !target.closest('.aistea-hi__panel')) {
        closeAll();
      }
    });
  }

  document.querySelectorAll('.aistea-hi').forEach(initHotspotImage);
})();
