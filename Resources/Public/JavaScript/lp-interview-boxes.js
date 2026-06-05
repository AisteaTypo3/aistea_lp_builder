(function () {
  'use strict';

  var SELECTOR = '.aistea-ib';

  function reveal(section) {
    section.classList.add('is-visible');
  }

  function init(section) {
    if (section.dataset.aisteaInterviewBoxesReady === '1') {
      return;
    }
    section.dataset.aisteaInterviewBoxesReady = '1';

    if (!('IntersectionObserver' in window)) {
      reveal(section);
      return;
    }

    var observer = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) {
          return;
        }
        reveal(section);
        observer.unobserve(section);
      });
    }, {
      rootMargin: '0px 0px -12% 0px',
      threshold: 0.2
    });

    observer.observe(section);
  }

  function boot() {
    document.querySelectorAll(SELECTOR).forEach(init);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
