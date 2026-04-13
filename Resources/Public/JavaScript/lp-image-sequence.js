(() => {
  'use strict';

  const SELECTOR = '.aistea-is';

  class ImageSequence {
    constructor(root) {
      this.root = root;
      this.image = root.querySelector('.aistea-is__image');
      this.canvas = root.querySelector('.aistea-is__canvas');
      this.context = this.canvas ? this.canvas.getContext('2d', { alpha: false }) : null;
      this.payload = root.querySelector('.aistea-is__payload');
      this.config = this.parseConfig();
      this.playFrames = [];
      this.started = false;
      this.stopRequested = false;
      this.prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      this.init();
    }

    parseConfig() {
      if (!this.payload) {
        return { frames: [], fps: 12, loop: false };
      }
      try {
        const decoded = JSON.parse(this.payload.textContent || '{}');
        return {
          frames: Array.isArray(decoded.frames) ? decoded.frames : [],
          fps: Math.max(1, Math.min(60, Number(decoded.fps || 12))),
          loop: Boolean(decoded.loop),
        };
      } catch (_) {
        return { frames: [], fps: 12, loop: false };
      }
    }

    init() {
      if (!this.image || !this.canvas || !this.context || this.config.frames.length <= 1) {
        return;
      }
      this.image.loading = 'eager';
      this.image.decoding = 'async';
      try {
        this.image.fetchPriority = 'high';
      } catch (_) {
        // fetchPriority is not supported in all browsers.
      }

      const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting || this.started) {
            return;
          }
          this.started = true;
          this.preloadFrames().finally(() => this.play());
          obs.disconnect();
        });
      }, { threshold: 0.25 });

      observer.observe(this.root);
    }

    async preloadFrames() {
      const frameUrls = this.config.frames.filter(Boolean);
      if (frameUrls.length === 0) {
        return;
      }

      const concurrency = 8;
      const loaded = new Map();
      let cursor = 0;

      const loadUrl = (url) => new Promise((resolve) => {
        const img = new Image();
        img.decoding = 'async';
        img.onload = () => resolve(img);
        img.onerror = () => resolve(null);
        img.src = url;
      });

      const worker = async () => {
        while (cursor < frameUrls.length) {
          const index = cursor;
          cursor += 1;
          const url = frameUrls[index];
          const image = await loadUrl(url);
          if (image) {
            loaded.set(url, image);
          }
        }
      };

      const workers = [];
      for (let i = 0; i < concurrency; i += 1) {
        workers.push(worker());
      }
      await Promise.all(workers);

      this.playFrames = frameUrls
        .map((url) => loaded.get(url) || null)
        .filter((entry) => entry instanceof Image);

      if (this.playFrames.length > 0) {
        this.prepareCanvas(this.playFrames[0]);
      }
    }

    play() {
      if (!this.image || !this.canvas || !this.context || this.stopRequested) {
        return;
      }

      const frames = this.playFrames;
      if (frames.length <= 1) {
        return;
      }

      this.root.classList.add('is-ready');

      if (this.prefersReducedMotion) {
        this.drawFrame(frames[frames.length - 1]);
        return;
      }

      const frameDuration = 1000 / this.config.fps;
      let frameIndex = 0;
      let lastTime = 0;

      const tick = (timestamp) => {
        if (this.stopRequested) {
          return;
        }
        if (lastTime === 0) {
          lastTime = timestamp;
        }
        const elapsed = timestamp - lastTime;
        if (elapsed >= frameDuration) {
          lastTime = timestamp - (elapsed % frameDuration);
          frameIndex += 1;

          if (frameIndex >= frames.length) {
            if (this.config.loop) {
              frameIndex = 0;
            } else {
              this.drawFrame(frames[frames.length - 1]);
              return;
            }
          }

          this.drawFrame(frames[frameIndex]);
        }

        window.requestAnimationFrame(tick);
      };

      this.drawFrame(frames[0]);
      window.requestAnimationFrame(tick);
    }

    prepareCanvas(firstFrame) {
      if (!this.canvas || !firstFrame) {
        return;
      }
      const width = Math.max(1, Number(firstFrame.naturalWidth || firstFrame.width || 1));
      const height = Math.max(1, Number(firstFrame.naturalHeight || firstFrame.height || 1));
      this.canvas.width = width;
      this.canvas.height = height;
      this.canvas.setAttribute('width', String(width));
      this.canvas.setAttribute('height', String(height));
    }

    drawFrame(frame) {
      if (!this.canvas || !this.context || !frame) {
        return;
      }
      const width = this.canvas.width || frame.naturalWidth || frame.width;
      const height = this.canvas.height || frame.naturalHeight || frame.height;
      this.context.clearRect(0, 0, width, height);
      this.context.drawImage(frame, 0, 0, width, height);
    }
  }

  function bootstrap() {
    document.querySelectorAll(SELECTOR).forEach((element) => {
      if (element.dataset.initialized === '1') {
        return;
      }
      element.dataset.initialized = '1';
      new ImageSequence(element);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
  } else {
    bootstrap();
  }
})();
