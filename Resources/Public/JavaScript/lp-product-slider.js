(() => {
  'use strict';

  const COMPONENT_SELECTOR = '.aistea-pv';

  class ProductSlider {
    constructor(root) {
      this.root = root;
      this.stage = root.querySelector('.aistea-pv__stageMedia');
      this.loader = root.querySelector('.aistea-pv__loader');
      this.explainer = root.querySelector('.aistea-pv__explainer');
      this.tabs = Array.from(root.querySelectorAll('[role="tab"]'));
      this.tabList = root.querySelector('.aistea-pv__tablist');
      this.endpoint = root.dataset.endpoint || '';
      this.breakpoint = Number(root.dataset.breakpoint || 768);
      this.reducedMotionBehavior = root.dataset.reducedMotionBehavior || 'static';
      this.videoAutoplayDesktop = root.dataset.videoAutoplayDesktop === '1';
      this.videoAutoplayMobile = root.dataset.videoAutoplayMobile === '1';
      this.preloadStrategy = root.dataset.preloadStrategy || 'smart';
      this.threeUrl = root.dataset.threeUrl || '';
      this.gltfLoaderUrl = root.dataset.gltfLoaderUrl || '';
      this.ceUid = root.dataset.ceUid || '';
      this.prevButton = root.querySelector('.aistea-pv__nav--prev');
      this.nextButton = root.querySelector('.aistea-pv__nav--next');

      this.slidesLoaded = false;
      this.slides = [];
      this.activeSlideUid = null;
      this.playedVideos = new Set();
      this.videoNodes = new Map();
      this.modelInstances = new Map();
      this.modelInitialized = new Set();
      this.stageNodes = new Map();
      this.loaderVisible = false;
      this.loaderShownAt = 0;
      this.loaderHideTimer = null;
      this.tabAnimationPlayed = false;
      this.tabSnapTimer = null;

      this.prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
      this.isMobile = () => window.innerWidth <= this.breakpoint;

      this.init();
    }

    init() {
      if (!this.stage || this.tabs.length === 0 || !this.endpoint) {
        return;
      }

      this.root.style.setProperty('--pv-ratio', this.root.dataset.stageAspectRatio || '3/2');
      this.root.classList.add('is-booting');
      this.bindTabEvents();
      this.bindMobileTabSnap();
      this.bindStageNavigation();
      this.observeViewport();
    }

    bindTabEvents() {
      this.tabs.forEach((tab, index) => {
        tab.style.setProperty('--pv-tab-index', String(index));
        tab.addEventListener('click', () => this.activateByIndex(index, true));

        tab.addEventListener('keydown', (event) => {
          const key = event.key;
          if (key === 'ArrowDown' || key === 'ArrowRight') {
            event.preventDefault();
            const nextIndex = (index + 1) % this.tabs.length;
            this.tabs[nextIndex].focus();
            return;
          }

          if (key === 'ArrowUp' || key === 'ArrowLeft') {
            event.preventDefault();
            const nextIndex = (index - 1 + this.tabs.length) % this.tabs.length;
            this.tabs[nextIndex].focus();
            return;
          }

          if (key === 'Enter' || key === ' ') {
            event.preventDefault();
            this.activateByIndex(index, true);
            return;
          }

          if (key === 'Escape') {
            event.preventDefault();
            this.tabs[0].focus();
          }
        });
      });
    }

    observeViewport() {
      const observer = new IntersectionObserver((entries, obs) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting || this.slidesLoaded) {
            return;
          }

          this.fetchSlides()
            .then(() => {
              this.slidesLoaded = true;
              this.activateByIndex(0, false);
              this.animateTabsOnFirstLoad();
            })
            .catch(() => {
              this.stage.classList.add('has-error');
            });
          obs.disconnect();
        });
      }, { threshold: 0.2 });

      observer.observe(this.root);
    }

    bindStageNavigation() {
      if (this.prevButton) {
        this.prevButton.addEventListener('click', () => this.activateRelative(-1));
      }
      if (this.nextButton) {
        this.nextButton.addEventListener('click', () => this.activateRelative(1));
      }
    }

    async fetchSlides() {
      const response = await fetch(this.endpoint, {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error(`Slide endpoint failed for CE ${this.ceUid}`);
      }

      const payload = await response.json();
      this.slides = Array.isArray(payload.slides) ? payload.slides : [];

      if (this.preloadStrategy === 'aggressive') {
        this.preloadFirstAssets();
      }
    }

    preloadFirstAssets() {
      this.slides.forEach((slide) => {
        const url = slide.imageUrl || slide.posterUrl || slide.fallbackImage;
        if (!url) {
          return;
        }
        const img = new Image();
        img.src = url;
      });
    }

    activateByIndex(index, userInitiated) {
      const tab = this.tabs[index];
      if (!tab) {
        return;
      }

      const slideUid = Number(tab.dataset.slideUid || 0);
      if (!slideUid || slideUid === this.activeSlideUid && !userInitiated) {
        return;
      }

      this.tabs.forEach((entryTab, tabIndex) => {
        const selected = tabIndex === index;
        entryTab.classList.toggle('is-active', selected);
        entryTab.setAttribute('aria-selected', selected ? 'true' : 'false');
        entryTab.setAttribute('tabindex', selected ? '0' : '-1');
      });
      this.centerActiveTab(tab);

      this.switchToSlide(slideUid);
    }

    centerActiveTab(tab) {
      if (!tab || !this.isMobile()) {
        return;
      }
      if (typeof tab.scrollIntoView === 'function') {
        tab.scrollIntoView({
          behavior: 'smooth',
          block: 'nearest',
          inline: 'center',
        });
      }
    }

    bindMobileTabSnap() {
      if (!this.tabList) {
        return;
      }

      this.tabList.addEventListener('scroll', () => {
        if (!this.isMobile()) {
          return;
        }
        if (this.tabSnapTimer) {
          window.clearTimeout(this.tabSnapTimer);
        }
        this.tabSnapTimer = window.setTimeout(() => {
          const index = this.getNearestTabIndexToCenter();
          if (index < 0) {
            return;
          }
          const tab = this.tabs[index];
          if (!tab) {
            return;
          }

          tab.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
            inline: 'center',
          });
          this.activateByIndex(index, true);
        }, 90);
      }, { passive: true });
    }

    getNearestTabIndexToCenter() {
      if (!this.tabList || this.tabs.length === 0) {
        return -1;
      }
      const center = this.tabList.scrollLeft + (this.tabList.clientWidth / 2);
      let nearestIndex = -1;
      let nearestDistance = Number.POSITIVE_INFINITY;

      this.tabs.forEach((tab, index) => {
        const tabCenter = tab.offsetLeft + (tab.offsetWidth / 2);
        const distance = Math.abs(tabCenter - center);
        if (distance < nearestDistance) {
          nearestDistance = distance;
          nearestIndex = index;
        }
      });

      return nearestIndex;
    }

    activateRelative(delta) {
      if (!this.tabs.length) {
        return;
      }
      const currentIndex = this.tabs.findIndex((tab) => tab.classList.contains('is-active'));
      const safeIndex = currentIndex >= 0 ? currentIndex : 0;
      const nextIndex = (safeIndex + delta + this.tabs.length) % this.tabs.length;
      this.activateByIndex(nextIndex, true);
      this.tabs[nextIndex].focus();
    }

    switchToSlide(slideUid) {
      const slide = this.slides.find((entry) => Number(entry.slideUid) === Number(slideUid));
      const fallbackTab = this.tabs.find((tab) => Number(tab.dataset.slideUid) === Number(slideUid));
      if (!slide && !fallbackTab) {
        return;
      }

      this.deactivateCurrentMedia();
      this.activeSlideUid = slideUid;

      const title = slide?.title || fallbackTab?.dataset.slideTitle || '';
      const body = slide?.body || fallbackTab?.dataset.slideBody || '';
      const ariaLabel = slide?.ariaLabel || fallbackTab?.dataset.slideAriaLabel || title;
      const type = slide?.type || fallbackTab?.dataset.slideType || 'image';

      this.renderExplainer(title, body);
      this.renderSlideMedia(slide || {
        slideUid,
        type,
        fallbackImage: fallbackTab?.dataset.fallbackImage || ''
      }, ariaLabel);
    }

    renderExplainer(title, body) {
      if (!this.explainer) {
        return;
      }

      const safeTitle = this.escapeHtml(title);
      this.explainer.innerHTML = `<h3 class="aistea-pv__explainerTitle">${safeTitle}</h3><div class="aistea-pv__explainerBody">${body || ''}</div>`;
    }

    renderSlideMedia(slide, ariaLabel) {
      if (!this.stage) {
        return;
      }

      const type = slide.type || 'image';
      let node;
      if (type === 'video') {
        node = this.renderVideo(slide, ariaLabel);
      } else if (type === 'model3d') {
        node = this.renderModel3d(slide, ariaLabel);
      } else if (type === 'colorGallery') {
        node = this.renderColorGallery(slide, ariaLabel);
      } else {
        node = this.renderImage(slide.imageUrl || slide.fallbackImage, ariaLabel);
      }

      Array.from(this.stage.children).forEach((child) => {
        if (!(child instanceof HTMLElement)) {
          return;
        }
        if (!child.classList.contains('aistea-pv__stageLayer')) {
          child.remove();
        }
      });

      const currentLayer = this.stage.querySelector('.aistea-pv__stageLayer.is-current');
      if (currentLayer instanceof HTMLElement) {
        currentLayer.classList.remove('is-current');
        currentLayer.classList.add('is-leaving');
        window.setTimeout(() => {
          if (currentLayer.parentNode === this.stage) {
            currentLayer.remove();
          }
        }, 520);
      }

      const nextLayer = document.createElement('div');
      nextLayer.className = 'aistea-pv__stageLayer is-current';
      nextLayer.appendChild(node);
      this.stage.appendChild(nextLayer);
      requestAnimationFrame(() => nextLayer.classList.add('is-visible'));
      this.stageNodes.set(Number(slide.slideUid), node);
    }

    animateTabsOnFirstLoad() {
      if (this.tabAnimationPlayed || this.prefersReducedMotion) {
        this.root.classList.remove('is-booting');
        return;
      }
      this.tabAnimationPlayed = true;
      requestAnimationFrame(() => {
        this.root.classList.remove('is-booting');
        this.root.classList.add('is-ready');
      });
      window.setTimeout(() => this.root.classList.remove('is-ready'), 900);
    }

    renderImage(url, ariaLabel) {
      const img = document.createElement('img');
      img.className = 'aistea-pv__media aistea-pv__media--image';
      if (url) {
        img.src = url;
      } else {
        img.classList.add('is-empty');
      }
      img.alt = ariaLabel || '';
      img.loading = 'lazy';
      img.decoding = 'async';
      return img;
    }

    renderVideo(slide, ariaLabel) {
      if (this.prefersReducedMotion && this.reducedMotionBehavior !== 'allowManualPlay') {
        return this.renderImage(
          slide.endframeUrl || slide.posterUrl || slide.fallbackImage || '',
          ariaLabel
        );
      }

      const wrapper = document.createElement('div');
      wrapper.className = 'aistea-pv__media aistea-pv__media--video';

      const replayButton = document.createElement('button');
      replayButton.type = 'button';
      replayButton.className = 'aistea-pv__replay';
      replayButton.textContent = 'Replay';

      const video = document.createElement('video');
      video.muted = true;
      video.playsInline = true;
      video.preload = 'none';
      video.controls = false;
      video.setAttribute('controlsList', 'nodownload nofullscreen noplaybackrate');
      video.disablePictureInPicture = true;
      video.setAttribute('aria-label', ariaLabel || slide.title || 'Product video');

      const poster = slide.startframeUrl || slide.posterUrl || slide.fallbackImage || '';
      if (poster) {
        video.poster = poster;
      }

      const shouldAutoplay = this.shouldAutoplayVideo(slide);
      const alreadyPlayed = this.playedVideos.has(Number(slide.slideUid));

      const playVideo = () => {
        if (!slide.videoUrl) {
          return;
        }

        video.src = slide.videoUrl;
        video.load();

        if (shouldAutoplay && !alreadyPlayed) {
          this.playedVideos.add(Number(slide.slideUid));
          video.play().catch(() => {
            replayButton.hidden = false;
          });
        }
      };

      playVideo();

      video.addEventListener('ended', () => {
        if (slide.endframeUrl) {
          video.removeAttribute('src');
          video.load();
          const frame = this.renderImage(slide.endframeUrl, ariaLabel);
          wrapper.innerHTML = '';
          wrapper.appendChild(frame);
          wrapper.appendChild(replayButton);
          replayButton.hidden = false;
          return;
        }
        replayButton.hidden = false;
      }, { once: true });

      replayButton.addEventListener('click', () => {
        if (!slide.videoUrl) {
          return;
        }

        wrapper.innerHTML = '';
        wrapper.appendChild(video);
        wrapper.appendChild(replayButton);
        replayButton.hidden = true;

        video.src = slide.videoUrl;
        video.load();
        video.play().catch(() => {
          replayButton.hidden = false;
        });
      });

      if (alreadyPlayed && slide.endframeUrl) {
        wrapper.appendChild(this.renderImage(slide.endframeUrl, ariaLabel));
        replayButton.hidden = false;
      } else {
        wrapper.appendChild(video);
        replayButton.hidden = true;
      }

      wrapper.appendChild(replayButton);
      this.videoNodes.set(Number(slide.slideUid), video);
      return wrapper;
    }

    shouldAutoplayVideo(slide) {
      if (this.prefersReducedMotion) {
        return false;
      }

      if (this.isMobile()) {
        return this.videoAutoplayMobile;
      }

      return this.videoAutoplayDesktop;
    }

    renderModel3d(slide, ariaLabel) {
      if (this.isMobile()) {
        return this.renderImage(slide.posterUrl || slide.fallbackImage || '', ariaLabel);
      }

      const uid = Number(slide.slideUid);
      const existingNode = this.stageNodes.get(uid);
      if (existingNode) {
        const instance = this.modelInstances.get(uid);
        if (instance && typeof instance.onResume === 'function') {
          instance.onResume();
        } else if (!this.modelInitialized.has(uid)) {
          const canvas = existingNode.querySelector('.aistea-pv__modelCanvas');
          const poster = existingNode.querySelector('.aistea-pv__modelPoster');
          if (canvas instanceof HTMLCanvasElement) {
            this.initModel(slide, canvas, poster).catch((error) => {
              this.modelInitialized.delete(uid);
              this.showModelError(existingNode, error);
            });
          }
        }
        return existingNode;
      }

      const wrapper = document.createElement('div');
      wrapper.className = 'aistea-pv__media aistea-pv__media--model';
      const canvas = document.createElement('canvas');
      canvas.className = 'aistea-pv__modelCanvas';
      canvas.setAttribute('aria-label', ariaLabel || slide.title || '3D model');

      const poster = this.renderImage(slide.posterUrl || slide.fallbackImage || '', ariaLabel);
      poster.classList.add('aistea-pv__modelPoster');
      wrapper.appendChild(poster);
      wrapper.appendChild(canvas);

      if (!this.modelInitialized.has(uid)) {
        this.initModel(slide, canvas, poster).catch((error) => {
          this.modelInitialized.delete(uid);
          canvas.remove();
          this.showModelError(wrapper, error);
        });
      } else {
        const instance = this.modelInstances.get(uid);
        if (instance && typeof instance.onResume === 'function') {
          instance.onResume();
        }
      }

      return wrapper;
    }

    async initModel(slide, canvas, posterNode) {
      if (!slide.modelUrl) {
        throw new Error('No 3D model file configured for this slide');
      }

      const uid = Number(slide.slideUid);
      this.toggleLoader(true);

      try {
        this.setLoaderText('Loading 3D model…');
        const runtime = await this.getThreeRuntime();
        const THREE = runtime.THREE;
        const GLTFLoader = runtime.GLTFLoader;

        const scene = new THREE.Scene();
        const width = this.stage.clientWidth || 800;
        const height = this.stage.clientHeight || 800;
        const camera = new THREE.PerspectiveCamera(45, width / height, 0.1, 100);
        this.applyCameraPreset(camera, slide.modelOptions?.cameraPreset || 'front');

        const renderer = new THREE.WebGLRenderer({
          canvas,
          alpha: true,
          antialias: true,
        });
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
        renderer.setSize(width, height);
        if ('outputColorSpace' in renderer && THREE.SRGBColorSpace) {
          renderer.outputColorSpace = THREE.SRGBColorSpace;
        } else if ('outputEncoding' in renderer && THREE.sRGBEncoding) {
          renderer.outputEncoding = THREE.sRGBEncoding;
        }
        if ('toneMapping' in renderer && THREE.ACESFilmicToneMapping) {
          renderer.toneMapping = THREE.ACESFilmicToneMapping;
          renderer.toneMappingExposure = 1.0;
        }

        if (slide.modelOptions?.backgroundColor) {
          scene.background = new THREE.Color(slide.modelOptions.backgroundColor);
        }

        const hemi = new THREE.HemisphereLight(0xffffff, 0x1f2937, 1.2);
        const key = new THREE.DirectionalLight(0xffffff, 1.0);
        key.position.set(4, 5, 6);
        scene.add(hemi, key);

        const loader = new GLTFLoader();
        const gltf = await loader.loadAsync(slide.modelUrl);
        scene.add(gltf.scene);
        await this.applyMappedMaterials(gltf.scene, scene, renderer, slide.envMapUrl, THREE);
        const fitData = this.fitModelIntoView(gltf.scene, camera, THREE);
        this.enableModelInteraction(canvas, camera, gltf.scene, THREE, fitData);

        if (!posterNode.classList.contains('is-empty')) {
          posterNode.style.opacity = '0';
        }

        const autorotate = Boolean(slide.modelOptions?.autorotate) && !this.prefersReducedMotion;
        let rafId = 0;

        const animate = () => {
          if (autorotate) {
            gltf.scene.rotation.y += 0.005;
          }
          renderer.render(scene, camera);
          rafId = window.requestAnimationFrame(animate);
        };

        animate();

        const instance = {
          onPause: () => {
            window.cancelAnimationFrame(rafId);
          },
          onResume: () => {
            window.cancelAnimationFrame(rafId);
            animate();
          },
          destroy: () => {
            window.cancelAnimationFrame(rafId);
            renderer.dispose();
          },
        };

        this.modelInitialized.add(uid);
        this.modelInstances.set(uid, instance);
      } finally {
        this.toggleLoader(false);
      }
    }

    async getThreeRuntime() {
      const localThree = this.threeUrl || '';
      const localLoader = this.gltfLoaderUrl || '';
      if (localThree && localLoader) {
        const threeMod = await import(localThree);
        const loaderMod = await import(localLoader);
        if (threeMod && loaderMod && loaderMod.GLTFLoader) {
          return {
            THREE: threeMod,
            GLTFLoader: loaderMod.GLTFLoader,
          };
        }
      }

      throw new Error('Three runtime ESM modules could not be loaded');
    }

    enableModelInteraction(canvas, camera, model, THREE, fitData) {
      if (!canvas || !camera || !model) {
        return;
      }

      let isDragging = false;
      let pointerId = null;
      let lastX = 0;
      let lastY = 0;
      const modelRadius = Math.max(Number(fitData?.radius || 1), 0.5);
      const minDistance = Math.max(modelRadius * 1.6, 1.2);
      const maxDistance = Math.max(modelRadius * 12, minDistance + 2);
      let distance = THREE.MathUtils.clamp(
        camera.position.length() || Number(fitData?.distance || 3),
        minDistance,
        maxDistance
      );

      const updateCameraDistance = (nextDistance) => {
        distance = THREE.MathUtils.clamp(nextDistance, minDistance, maxDistance);
        const dir = camera.position.clone();
        if (dir.lengthSq() < 1e-6) {
          dir.set(0, 0, 1);
        } else {
          dir.normalize();
        }
        camera.position.copy(dir.multiplyScalar(distance));
        camera.near = Math.max(0.01, distance / 500);
        camera.far = Math.max(distance * 30, 50);
        camera.lookAt(0, 0, 0);
        camera.updateProjectionMatrix();
      };

      const onPointerDown = (event) => {
        isDragging = true;
        pointerId = event.pointerId;
        lastX = event.clientX;
        lastY = event.clientY;
        if (canvas.setPointerCapture) {
          canvas.setPointerCapture(pointerId);
        }
      };

      const onPointerMove = (event) => {
        if (!isDragging || event.pointerId !== pointerId) {
          return;
        }
        const dx = event.clientX - lastX;
        const dy = event.clientY - lastY;
        lastX = event.clientX;
        lastY = event.clientY;

        model.rotation.y += dx * 0.01;
        model.rotation.x += dy * 0.006;
        model.rotation.x = THREE.MathUtils.clamp(model.rotation.x, -Math.PI / 3, Math.PI / 3);
      };

      const onPointerUp = (event) => {
        if (event.pointerId !== pointerId) {
          return;
        }
        isDragging = false;
        if (canvas.releasePointerCapture) {
          canvas.releasePointerCapture(pointerId);
        }
        pointerId = null;
      };

      const onWheel = (event) => {
        event.preventDefault();
        const deltaY = Number(event.deltaY || 0);
        const normalized = THREE.MathUtils.clamp(deltaY, -120, 120);
        const zoomFactor = Math.exp(normalized * 0.0018);
        updateCameraDistance(distance * zoomFactor);
      };

      canvas.style.touchAction = 'none';
      canvas.addEventListener('pointerdown', onPointerDown, { passive: true });
      canvas.addEventListener('pointermove', onPointerMove, { passive: true });
      canvas.addEventListener('pointerup', onPointerUp, { passive: true });
      canvas.addEventListener('pointercancel', onPointerUp, { passive: true });
      canvas.addEventListener('wheel', onWheel, { passive: false });
    }

    showModelError(wrapper, error) {
      if (!wrapper) {
        return;
      }
      const message = (error && error.message) ? error.message : 'Unknown 3D error';
      const errorBox = document.createElement('div');
      errorBox.className = 'aistea-pv__modelError';
      errorBox.textContent = `3D load failed: ${message}`;
      wrapper.appendChild(errorBox);
    }

    fitModelIntoView(model, camera, THREE) {
      try {
        const box = new THREE.Box3().setFromObject(model);
        const size = box.getSize(new THREE.Vector3());
        const center = box.getCenter(new THREE.Vector3());
        model.position.sub(center);

        const maxSize = Math.max(size.x, size.y, size.z) || 1;
        const radius = Math.max(maxSize * 0.5, 0.5);
        const fitHeightDistance = maxSize / (2 * Math.tan((Math.PI * camera.fov) / 360));
        const fitDistance = fitHeightDistance * 1.25;
        camera.near = 0.01;
        camera.far = fitDistance * 20;
        camera.position.set(0, maxSize * 0.2, fitDistance);
        camera.lookAt(0, 0, 0);
        camera.updateProjectionMatrix();
        return { radius, distance: fitDistance };
      } catch (_) {
        // Keep preset camera as fallback.
        return { radius: 1, distance: camera.position.length() || 3 };
      }
    }

    async applyMappedMaterials(model, scene, renderer, envMapUrl, THREE) {
      const materials = this.createMaterialLibrary(THREE);

      let envMap = null;
      if (envMapUrl) {
        envMap = await this.loadEnvironmentMap(envMapUrl, renderer, THREE).catch(() => null);
      }

      if (envMap) {
        scene.environment = envMap;
      }

      const materialMap = {
        blau_titanium: 'titanium',
        blue_titanium: 'titanium',
        a_5850: 'titanium',
        red: 'redBone',
        red_bone: 'redBone',
        bone: 'whiteBone',
        purple: 'purpleMetal',
        pink: 'purpleMetal',
        purple_titanium: 'purpleMetal',
        gold: 'gold',
        green: 'greenMetal',
        cartilage: 'cartilage',
        light_metal: 'lightMetal',
        teeth: 'teeth',
        thread: 'thread',
      };

      model.traverse((child) => {
        if (!child.isMesh || !child.material) {
          return;
        }

        child.castShadow = true;
        child.receiveShadow = true;
        const sourceName = String(child.material.name || '').toLowerCase();
        let targetMaterial = null;

        for (const [key, materialKey] of Object.entries(materialMap)) {
          if (!sourceName.includes(key)) {
            continue;
          }
          if (key === 'bone' && sourceName.includes('red')) {
            continue;
          }
          targetMaterial = materials[materialKey] || null;
          break;
        }

        if (!targetMaterial) {
          return;
        }

        const replacement = targetMaterial.clone();
        replacement.name = child.material.name || replacement.name || '';
        if (envMap) {
          replacement.envMap = envMap;
          replacement.needsUpdate = true;
        }
        child.material = replacement;
      });
    }

    createMaterialLibrary(THREE) {
      return {
        titanium: new THREE.MeshStandardMaterial({
          color: 0x2a3b4d,
          metalness: 1.0,
          roughness: 0.25,
          envMapIntensity: 4.5,
          side: THREE.DoubleSide,
        }),
        redBone: new THREE.MeshStandardMaterial({
          color: 0x660000,
          metalness: 0.1,
          roughness: 0.6,
          envMapIntensity: 0.7,
          side: THREE.DoubleSide,
        }),
        whiteBone: new THREE.MeshStandardMaterial({
          color: 0xdcdce0,
          metalness: 0.1,
          roughness: 0.65,
          envMapIntensity: 0.6,
          side: THREE.DoubleSide,
        }),
        purpleMetal: new THREE.MeshStandardMaterial({
          color: 0x2a1558,
          metalness: 1.0,
          roughness: 0.3,
          envMapIntensity: 3.0,
          side: THREE.DoubleSide,
        }),
        gold: new THREE.MeshStandardMaterial({
          color: 0x3d2800,
          metalness: 1.0,
          roughness: 0.3,
          envMapIntensity: 3.0,
          side: THREE.DoubleSide,
        }),
        greenMetal: new THREE.MeshStandardMaterial({
          color: 0x0b4d1c,
          metalness: 1.0,
          roughness: 0.3,
          envMapIntensity: 3.0,
          side: THREE.DoubleSide,
        }),
        cartilage: new THREE.MeshStandardMaterial({
          color: 0xe0e0e0,
          metalness: 0.0,
          roughness: 0.9,
          envMapIntensity: 0.5,
          side: THREE.DoubleSide,
        }),
        lightMetal: new THREE.MeshStandardMaterial({
          color: 0xc0c0c0,
          metalness: 0.9,
          roughness: 0.3,
          envMapIntensity: 3.0,
          side: THREE.DoubleSide,
        }),
        teeth: new THREE.MeshStandardMaterial({
          color: 0xffffff,
          metalness: 0.0,
          roughness: 0.3,
          envMapIntensity: 1.5,
          side: THREE.DoubleSide,
        }),
        thread: new THREE.MeshStandardMaterial({
          color: 0x303030,
          metalness: 0.6,
          roughness: 0.7,
          envMapIntensity: 1.0,
          side: THREE.DoubleSide,
        }),
      };
    }

    loadEnvironmentMap(url, renderer, THREE) {
      return new Promise((resolve, reject) => {
        const textureLoader = new THREE.TextureLoader();
        textureLoader.load(
          url,
          (texture) => {
            texture.mapping = THREE.EquirectangularReflectionMapping;
            if ('colorSpace' in texture && THREE.SRGBColorSpace) {
              texture.colorSpace = THREE.SRGBColorSpace;
            } else if ('encoding' in texture && THREE.sRGBEncoding) {
              texture.encoding = THREE.sRGBEncoding;
            }

            const pmremGenerator = new THREE.PMREMGenerator(renderer);
            const envMap = pmremGenerator.fromEquirectangular(texture).texture;
            texture.dispose();
            pmremGenerator.dispose();
            resolve(envMap);
          },
          undefined,
          () => reject(new Error('Environment map load failed'))
        );
      });
    }

    applyCameraPreset(camera, preset) {
      if (preset === 'back') {
        camera.position.set(0, 0.8, -3.2);
      } else if (preset === 'left') {
        camera.position.set(-3.2, 0.8, 0);
      } else if (preset === 'right') {
        camera.position.set(3.2, 0.8, 0);
      } else if (preset === 'custom') {
        camera.position.set(1.8, 1.4, 2.2);
      } else {
        camera.position.set(0, 0.8, 3.2);
      }
      camera.lookAt(0, 0.5, 0);
    }

    renderColorGallery(slide, ariaLabel) {
      const variants = Array.isArray(slide.colorGallery) ? slide.colorGallery : [];
      if (variants.length === 0) {
        return this.renderImage(slide.fallbackImage || '', ariaLabel);
      }

      const wrapper = document.createElement('div');
      wrapper.className = 'aistea-pv__media aistea-pv__media--colors';

      const controls = document.createElement('div');
      controls.className = 'aistea-pv__colorControls';

      const stageImage = this.renderImage(
        variants[0]?.images?.[0] || slide.fallbackImage || '',
        ariaLabel
      );
      stageImage.classList.add('aistea-pv__colorImage');

      variants.forEach((variant, index) => {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `aistea-pv__colorButton${index === 0 ? ' is-active' : ''}`;
        button.textContent = variant.name || `Color ${index + 1}`;
        button.addEventListener('click', () => {
          controls.querySelectorAll('button').forEach((entry) => entry.classList.remove('is-active'));
          button.classList.add('is-active');
          stageImage.src = variant.images?.[0] || slide.fallbackImage || '';
        });
        controls.appendChild(button);
      });

      wrapper.appendChild(stageImage);
      wrapper.appendChild(controls);
      return wrapper;
    }

    deactivateCurrentMedia() {
      if (this.activeSlideUid == null) {
        return;
      }

      const video = this.videoNodes.get(Number(this.activeSlideUid));
      if (video) {
        video.pause();
        if (this.preloadStrategy === 'none') {
          video.removeAttribute('src');
          video.load();
        }
      }

      const model = this.modelInstances.get(Number(this.activeSlideUid));
      if (model && typeof model.onPause === 'function') {
        model.onPause();
      }
    }

    toggleLoader(visible) {
      if (!this.loader) {
        return;
      }

      if (visible) {
        if (this.loaderHideTimer) {
          window.clearTimeout(this.loaderHideTimer);
          this.loaderHideTimer = null;
        }
        if (!this.loaderVisible) {
          this.loaderVisible = true;
          this.loaderShownAt = Date.now();
          this.loader.hidden = false;
          this.loader.setAttribute('aria-hidden', 'false');
          if (this.stage) {
            this.stage.classList.add('is-loading');
          }
        }
        return;
      }

      if (!this.loaderVisible) {
        return;
      }

      const elapsed = Date.now() - this.loaderShownAt;
      const delay = Math.max(0, 3000 - elapsed);
      if (this.loaderHideTimer) {
        window.clearTimeout(this.loaderHideTimer);
      }
      this.loaderHideTimer = window.setTimeout(() => {
        this.loaderVisible = false;
        this.loaderHideTimer = null;
        this.loader.hidden = true;
        this.loader.setAttribute('aria-hidden', 'true');
        if (this.stage) {
          this.stage.classList.remove('is-loading');
        }
      }, delay);
    }

    setLoaderText(text) {
      if (!this.loader) {
        return;
      }
      const textNode = this.loader.querySelector('.aistea-pv__loaderText');
      if (textNode) {
        textNode.textContent = text;
      }
    }

    escapeHtml(value) {
      return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }
  }

  function bootstrap() {
    document.querySelectorAll(COMPONENT_SELECTOR).forEach((element) => {
      if (element.dataset.initialized === '1') {
        return;
      }
      element.dataset.initialized = '1';
      new ProductSlider(element);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootstrap, { once: true });
  } else {
    bootstrap();
  }
})();
