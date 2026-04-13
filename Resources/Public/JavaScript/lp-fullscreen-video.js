(() => {
  'use strict';

  const SELECTOR = '.aistea-fsv';

  class FullScreenVideo {
    constructor(root) {
      this.root = root;
      this.shortVideo = root.querySelector('.aistea-fsv__shortVideo');
      this.cta = root.querySelector('.aistea-fsv__cta');
      this.modal = root.querySelector('.aistea-fsv__modal');
      this.closeButton = root.querySelector('.aistea-fsv__close');
      this.iframe = root.querySelector('.aistea-fsv__iframe');
      this.embedUrl = (root.dataset.vimeoEmbedUrl || '').trim();

      this.init();
    }

    init() {
      if (!this.cta || !this.modal || !this.iframe || this.embedUrl === '') {
        return;
      }

      this.cta.addEventListener('click', () => this.openModal());
      this.closeButton?.addEventListener('click', () => this.closeModal());
      this.modal.addEventListener('click', (event) => {
        if (event.target === this.modal) {
          this.closeModal();
        }
      });
      document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
          this.closeModal();
        }
      });
    }

    openModal() {
      if (!this.modal || !this.iframe) {
        return;
      }

      this.modal.hidden = false;
      this.modal.setAttribute('aria-hidden', 'false');
      this.iframe.src = this.embedUrl;
      document.body.style.overflow = 'hidden';
      this.shortVideo?.pause();
    }

    closeModal() {
      if (!this.modal || !this.iframe) {
        return;
      }
      this.modal.hidden = true;
      this.modal.setAttribute('aria-hidden', 'true');
      this.iframe.src = '';
      document.body.style.overflow = '';
      this.shortVideo?.play().catch(() => {});
    }
  }

  function bootstrap() {
    document.querySelectorAll(SELECTOR).forEach((element) => {
      if (element.dataset.initialized === '1') {
        return;
      }
      element.dataset.initialized = '1';
      new FullScreenVideo(element);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
  } else {
    bootstrap();
  }
})();
