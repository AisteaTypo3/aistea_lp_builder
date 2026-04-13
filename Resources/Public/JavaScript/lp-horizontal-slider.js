(() => {
  'use strict';

  const SELECTOR = '.aistea-hs';

  class HorizontalSlider {
    constructor(root) {
      this.root = root;
      this.stage = root.querySelector('.aistea-hs__stageMedia');
      this.headline = root.querySelector('.aistea-hs__headline');
      this.dotButtons = Array.from(root.querySelectorAll('.aistea-hs__dot'));
      this.playButton = root.querySelector('.aistea-hs__play');
      this.payloadNode = root.querySelector('.aistea-hs__payload');
      this.slides = this.parseSlides();
      this.activeIndex = 0;
      this.autoAdvance = true;
      this.advanceTimer = null;
      this.sequenceTimer = null;
      this.activeVideo = null;
      this.prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      this.init();
    }

    parseSlides() {
      if (!this.payloadNode) {
        return [];
      }
      try {
        const decoded = JSON.parse(this.payloadNode.textContent || '[]');
        return Array.isArray(decoded) ? decoded : [];
      } catch (_) {
        return [];
      }
    }

    init() {
      if (!this.stage || this.slides.length === 0) {
        return;
      }

      this.bindDots();
      this.bindPlayButton();
      this.bindPointerSwipe();
      this.showSlide(0, false);
    }

    bindDots() {
      this.dotButtons.forEach((button) => {
        button.addEventListener('click', () => {
          const index = Number(button.dataset.slideIndex || 0);
          this.showSlide(index, true);
        });
      });
    }

    bindPlayButton() {
      if (!this.playButton) {
        return;
      }
      this.playButton.addEventListener('click', () => {
        this.autoAdvance = !this.autoAdvance;
        this.updatePlayButton();
        if (this.autoAdvance) {
          this.showSlide(this.activeIndex, false);
        } else {
          this.clearAdvanceTimer();
        }
      });
      this.updatePlayButton();
    }

    bindPointerSwipe() {
      let startX = 0;
      let startY = 0;
      let dragging = false;

      this.stage.addEventListener('pointerdown', (event) => {
        startX = event.clientX;
        startY = event.clientY;
        dragging = true;
      });

      this.stage.addEventListener('pointerup', (event) => {
        if (!dragging) {
          return;
        }
        dragging = false;
        const deltaX = event.clientX - startX;
        const deltaY = event.clientY - startY;
        if (Math.abs(deltaX) < 40 || Math.abs(deltaX) <= Math.abs(deltaY)) {
          return;
        }
        if (deltaX < 0) {
          this.showSlide(this.activeIndex + 1, true);
        } else {
          this.showSlide(this.activeIndex - 1, true);
        }
      });
    }

    showSlide(index, userInitiated) {
      if (this.slides.length === 0) {
        return;
      }

      const total = this.slides.length;
      this.activeIndex = ((index % total) + total) % total;
      const slide = this.slides[this.activeIndex];
      this.stopActiveMedia();
      this.updateDots();
      this.updateHeadline(slide);
      this.renderSlide(slide);

      if (userInitiated && this.autoAdvance) {
        this.scheduleAdvance(4200);
      }
    }

    updateDots() {
      this.dotButtons.forEach((button, index) => {
        const isActive = index === this.activeIndex;
        button.classList.toggle('is-active', isActive);
        button.setAttribute('aria-selected', isActive ? 'true' : 'false');
      });
    }

    updateHeadline(slide) {
      if (!this.headline) {
        return;
      }
      this.headline.textContent = String(slide.headline || '');
    }

    renderSlide(slide) {
      const currentLayer = this.stage.querySelector('.aistea-hs__layer.is-current');
      if (currentLayer instanceof HTMLElement) {
        currentLayer.classList.remove('is-current');
        currentLayer.classList.add('is-leaving');
        window.setTimeout(() => {
          if (currentLayer.parentNode === this.stage) {
            currentLayer.remove();
          }
        }, 500);
      } else {
        this.stage.innerHTML = '';
      }

      const layer = document.createElement('div');
      layer.className = 'aistea-hs__layer is-current';

      const type = String(slide.type || 'image');
      if (type === 'video') {
        this.renderVideoSlide(slide, layer);
      } else if (type === 'sequence') {
        this.renderSequenceSlide(slide, layer);
      } else {
        this.renderImageSlide(slide, layer);
      }

      this.stage.appendChild(layer);
      requestAnimationFrame(() => layer.classList.add('is-visible'));
    }

    renderImageSlide(slide, layer) {
      const img = document.createElement('img');
      img.className = 'aistea-hs__media';
      img.src = slide.imageUrl || slide.fallbackImage || '';
      img.alt = slide.headline || slide.title || '';
      img.loading = 'eager';
      layer.appendChild(img);
      this.scheduleAdvance(3800);
    }

    renderSequenceSlide(slide, layer) {
      const frames = Array.isArray(slide.sequenceFrames) ? slide.sequenceFrames.filter(Boolean) : [];
      const img = document.createElement('img');
      img.className = 'aistea-hs__media';
      img.alt = slide.headline || slide.title || '';
      layer.appendChild(img);

      if (frames.length === 0) {
        img.src = slide.fallbackImage || '';
        this.scheduleAdvance(3800);
        return;
      }

      img.src = frames[0];
      if (frames.length === 1 || this.prefersReducedMotion) {
        if (frames.length > 1) {
          img.src = frames[frames.length - 1];
        }
        this.scheduleAdvance(3800);
        return;
      }

      const fps = Math.max(1, Math.min(60, Number(slide.sequenceFps || 12)));
      const frameDuration = Math.round(1000 / fps);
      let frameIndex = 0;
      this.sequenceTimer = window.setInterval(() => {
        frameIndex += 1;
        if (frameIndex >= frames.length) {
          window.clearInterval(this.sequenceTimer);
          this.sequenceTimer = null;
          img.src = frames[frames.length - 1];
          this.scheduleAdvance(1200);
          return;
        }
        img.src = frames[frameIndex];
      }, frameDuration);
    }

    renderVideoSlide(slide, layer) {
      const video = document.createElement('video');
      video.className = 'aistea-hs__media';
      video.muted = true;
      video.playsInline = true;
      video.preload = 'metadata';
      video.autoplay = true;
      video.loop = false;
      video.controls = false;
      video.setAttribute('controlsList', 'nodownload nofullscreen noplaybackrate');
      video.disablePictureInPicture = true;
      video.src = slide.videoUrl || '';
      video.setAttribute('aria-label', slide.headline || slide.title || 'Slide video');
      layer.appendChild(video);
      this.activeVideo = video;

      video.addEventListener('ended', () => {
        this.scheduleAdvance(600);
      }, { once: true });

      video.play().catch(() => {
        this.scheduleAdvance(3000);
      });
    }

    scheduleAdvance(delayMs) {
      if (!this.autoAdvance || this.slides.length < 2) {
        return;
      }
      this.clearAdvanceTimer();
      this.advanceTimer = window.setTimeout(() => {
        this.showSlide(this.activeIndex + 1, false);
      }, delayMs);
    }

    stopActiveMedia() {
      this.clearAdvanceTimer();
      if (this.sequenceTimer) {
        window.clearInterval(this.sequenceTimer);
        this.sequenceTimer = null;
      }
      if (this.activeVideo) {
        this.activeVideo.pause();
        this.activeVideo.removeAttribute('src');
        this.activeVideo.load();
        this.activeVideo = null;
      }
    }

    clearAdvanceTimer() {
      if (this.advanceTimer) {
        window.clearTimeout(this.advanceTimer);
        this.advanceTimer = null;
      }
    }

    updatePlayButton() {
      if (!this.playButton) {
        return;
      }
      this.playButton.textContent = this.autoAdvance ? '❚❚' : '▶';
      this.playButton.setAttribute('aria-label', this.autoAdvance ? 'Pause autoplay' : 'Start autoplay');
    }
  }

  function bootstrap() {
    document.querySelectorAll(SELECTOR).forEach((element) => {
      if (element.dataset.initialized === '1') {
        return;
      }
      element.dataset.initialized = '1';
      new HorizontalSlider(element);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
  } else {
    bootstrap();
  }
})();
