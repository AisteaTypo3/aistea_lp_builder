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

  function playFrames(canvas, ctx, frames, fps, loop, frameCache) {
    return new Promise(function (resolve) {
      if (!canvas || !ctx || !Array.isArray(frames) || frames.length === 0) {
        resolve();
        return;
      }

      var frameDuration = 1000 / Math.max(1, Number(fps || 24));
      var frameIndex = 0;
      var previousTimestamp = 0;
      var rafId = 0;

      function finish() {
        canvas.style.willChange = 'auto';
        if (rafId) {
          window.cancelAnimationFrame(rafId);
        }
        resolve();
      }

      function tick(timestamp) {
        if (previousTimestamp === 0) {
          previousTimestamp = timestamp;
        }

        var elapsed = timestamp - previousTimestamp;
        if (elapsed >= frameDuration) {
          var steps = Math.max(1, Math.floor(elapsed / frameDuration));
          previousTimestamp += steps * frameDuration;
          frameIndex += steps;

          if (frameIndex >= frames.length) {
            if (loop) {
              frameIndex = frameIndex % frames.length;
            } else {
              finish();
              return;
            }
          }

          var cachedFrame = frameCache.get(frames[frameIndex]);
          if (cachedFrame) {
            ctx.drawImage(cachedFrame, 0, 0, canvas.width, canvas.height);
          }
        }

        rafId = window.requestAnimationFrame(tick);
      }

      canvas.style.willChange = 'transform';
      var firstCached = frameCache.get(frames[0]);
      if (firstCached) {
        ctx.drawImage(firstCached, 0, 0, canvas.width, canvas.height);
      }
      rafId = window.requestAnimationFrame(tick);
    });
  }

  function drawCover(ctx, img, canvasW, canvasH) {
    var iw = img.naturalWidth || img.width;
    var ih = img.naturalHeight || img.height;
    var imgAspect = iw / ih;
    var canvasAspect = canvasW / canvasH;
    var sx, sy, sw, sh;
    if (imgAspect > canvasAspect) {
      sh = ih;
      sw = sh * canvasAspect;
      sx = (iw - sw) / 2;
      sy = 0;
    } else {
      sw = iw;
      sh = sw / canvasAspect;
      sx = 0;
      sy = (ih - sh) / 2;
    }
    ctx.drawImage(img, sx, sy, sw, sh, 0, 0, canvasW, canvasH);
  }

  function attachScrollDriven(element, canvas, ctx, frames, frameCache) {
    var wrapper = element.parentElement;
    var scrollRange = window.innerHeight;

    // Pin the element and add extra wrapper height so the user "stays" in
    // the hero while scrolling through all frames.
    element.classList.add('aistea-hero-sequenz--scroll-driven');
    wrapper.style.height = (window.innerHeight + scrollRange) + 'px';

    var lastDrawnIndex = -1;

    function drawFrame(frameIndex) {
      var cachedFrame = frameCache.get(frames[frameIndex]);
      if (cachedFrame) {
        drawCover(ctx, cachedFrame, canvas.width, canvas.height);
      }
    }

    function onScroll() {
      // progress 0→1 as the wrapper scrolls past the viewport top
      var scrolled = Math.max(0, -wrapper.getBoundingClientRect().top);
      var progress = Math.min(1, scrolled / scrollRange);
      var frameIndex = Math.min(frames.length - 1, Math.floor(progress * frames.length));
      if (frameIndex === lastDrawnIndex) {
        return;
      }
      lastDrawnIndex = frameIndex;
      drawFrame(frameIndex);
    }

    window.addEventListener('scroll', onScroll, { passive: true });
    drawFrame(0);
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
    var scrollDriven = element.dataset.scrollDriven === '1' || element.dataset.scrollDriven === 'true';
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

      await preloadFrames(frames, preloadMode === 'all' ? 8 : 4, frameCache, framePromises);

      // Create canvas for jank-free playback — ctx.drawImage() reads directly from
      // in-memory ImageElements in frameCache, bypassing the HTTP cache entirely.
      // img.src swapping would re-validate every frame against the server (no-cache headers).
      var canvas = document.createElement('canvas');
      var firstFrame = frameCache.get(frames[0]);
      if (scrollDriven) {
        // Buffer matches the viewport so the cover-crop calculation is pixel-exact.
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
      } else {
        canvas.width = firstFrame ? firstFrame.naturalWidth : (img.naturalWidth || 1920);
        canvas.height = firstFrame ? firstFrame.naturalHeight : (img.naturalHeight || 1080);
      }
      canvas.className = img.className;
      canvas.setAttribute('aria-hidden', 'true');
      img.parentNode.insertBefore(canvas, img);
      img.style.display = 'none';

      var ctx = canvas.getContext('2d');

      element.dataset.played = '1';
      playedCtes.add(ceUid);

      if (scrollDriven) {
        attachScrollDriven(element, canvas, ctx, frames, frameCache);
      } else {
        await playFrames(canvas, ctx, frames, fps, loop, frameCache);
      }
    } catch (error) {
      window.console.warn('Hero sequence failed', error);
      img.style.display = '';
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
    try {
      img.decoding = 'async';
      img.loading = 'eager';
      img.fetchPriority = 'high';
    } catch (error) {
      // Not supported in every browser.
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
