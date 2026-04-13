(function () {
  'use strict';

  var playedCtes = new Set();

  function decodeImage(image) {
    if (!image || typeof image.decode !== 'function') {
      return Promise.resolve();
    }
    return image.decode().catch(function () {
      return undefined;
    });
  }

  function ensureFrameLoaded(src, frameCache, framePromises) {
    if (!src) {
      return Promise.resolve(null);
    }
    if (frameCache.has(src)) {
      return Promise.resolve(frameCache.get(src));
    }
    if (framePromises.has(src)) {
      return framePromises.get(src);
    }

    var promise = new Promise(function (resolve) {
      var preload = new Image();
      preload.onload = function () {
        decodeImage(preload).then(function () {
          frameCache.set(src, preload);
          resolve(preload);
        });
      };
      preload.onerror = function () {
        resolve(null);
      };
      preload.src = src;
    }).finally(function () {
      framePromises.delete(src);
    });

    framePromises.set(src, promise);
    return promise;
  }

  async function preloadFrames(urls, concurrency, frameCache, framePromises) {
    if (!Array.isArray(urls) || urls.length === 0) {
      return;
    }

    var maxConcurrency = Math.max(1, Number(concurrency || 6));
    var queue = urls.slice();
    var workers = [];

    function worker() {
      return new Promise(async function (resolve) {
        while (queue.length > 0) {
          var next = queue.shift();
          if (!next) {
            continue;
          }
          await ensureFrameLoaded(next, frameCache, framePromises);
        }
        resolve();
      });
    }

    for (var i = 0; i < Math.min(maxConcurrency, queue.length); i += 1) {
      workers.push(worker());
    }

    await Promise.all(workers);
  }

  function uniqueUrls(urls) {
    return Array.from(new Set(urls.filter(Boolean)));
  }

  function normalizeFrameUrl(url) {
    if (!url) {
      return '';
    }
    if (/^(?:[a-z]+:)?\/\//i.test(url) || url.charAt(0) === '/') {
      return url;
    }
    return '/' + String(url).replace(/^\.?\//, '');
  }

  function playFrames(img, frames, fps, loop, frameCache, framePromises) {
    return new Promise(function (resolve) {
      if (!img || !Array.isArray(frames) || frames.length === 0) {
        resolve();
        return;
      }

      var frameDuration = 1000 / Math.max(1, Number(fps || 24));
      var frameIndex = 0;
      var nextFrameAt = performance.now();

      img.style.willChange = 'contents';

      async function tick() {
        var currentUrl = frames[frameIndex];
        if (!frameCache.has(currentUrl)) {
          await ensureFrameLoaded(currentUrl, frameCache, framePromises);
        }
        if (frameCache.has(currentUrl) && img.src !== currentUrl) {
          img.src = currentUrl;
        }
        frameIndex += 1;

        if (frameIndex >= frames.length) {
          if (loop) {
            frameIndex = 0;
          } else {
            img.style.willChange = 'auto';
            resolve();
            return;
          }
        }

        nextFrameAt += frameDuration;
        var delay = Math.max(0, nextFrameAt - performance.now());
        window.setTimeout(tick, delay);
      }

      window.setTimeout(tick, 0);
    });
  }

  async function runSequence(element) {
    var ceUid = String(element.dataset.ceUid || '');
    if (!ceUid || element.dataset.played === '1' || playedCtes.has(ceUid)) {
      return;
    }

    var endpoint = element.dataset.endpoint || '';
    var preloadMode = element.dataset.preload || 'smart';
    var fps = Number(element.dataset.fps || 24);
    var loop = String(element.dataset.loop || '') === '1' || String(element.dataset.loop || '').toLowerCase() === 'true';
    var img = element.querySelector('.aistea-hero-sequenz__image');
    var frameCache = new Map();
    var framePromises = new Map();

    if (!endpoint || !img) {
      return;
    }

    try {
      var response = await fetch(endpoint, { credentials: 'same-origin' });
      if (!response.ok) {
        return;
      }

      var data = await response.json();
      var frames = Array.isArray(data.frames)
        ? data.frames.map(normalizeFrameUrl).filter(Boolean)
        : [];
      if (frames.length === 0) {
        return;
      }

      if (preloadMode === 'all') {
        await preloadFrames(frames, 6, frameCache, framePromises);
      } else {
        var eager = uniqueUrls([frames[0], normalizeFrameUrl(data.last)].concat(frames.slice(1, 4)));
        await preloadFrames(eager, 4, frameCache, framePromises);

        var eagerSet = new Set(eager);
        var background = frames.filter(function (url) {
          return !eagerSet.has(url);
        });
        void preloadFrames(background, 3, frameCache, framePromises);
      }

      element.dataset.played = '1';
      playedCtes.add(ceUid);
      await playFrames(img, frames, fps, loop, frameCache, framePromises);
    } catch (error) {
      window.console.warn('Hero sequence failed', error);
    }
  }

  function initElement(element) {
    var breakpoint = Number(element.dataset.breakpoint || 768);
    var fallback = element.dataset.fallback || '';
    var lastFrame = element.dataset.last || fallback;
    var img = element.querySelector('.aistea-hero-sequenz__image');

    if (!img) {
      return;
    }

    if (fallback) {
      img.src = fallback;
    }

    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reducedMotion) {
      if (lastFrame) {
        img.src = lastFrame;
      }
      element.dataset.played = '1';
      return;
    }

    if (window.innerWidth <= breakpoint) {
      return;
    }

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (!entry.isIntersecting) {
            return;
          }
          observer.unobserve(element);
          void runSequence(element);
        });
      },
      { threshold: 0.25 }
    );

    observer.observe(element);
  }

  function boot() {
    var elements = document.querySelectorAll('.aistea-hero-sequenz');
    elements.forEach(initElement);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
