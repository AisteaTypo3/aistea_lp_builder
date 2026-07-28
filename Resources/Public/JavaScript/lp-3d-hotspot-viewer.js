(() => {
  'use strict';

  const SELECTOR = '[data-aistea-3dhv]';

  class ThreeDHotspotViewer {
    constructor(root) {
      this.root = root;
      this.canvas = root.querySelector('[data-aistea-3dhv-canvas]');
      this.overlay = root.querySelector('[data-aistea-3dhv-overlay]');
      this.poster = root.querySelector('[data-aistea-3dhv-poster]');
      this.status = root.querySelector('[data-aistea-3dhv-status]');
      this.copy = root.querySelector('[data-aistea-3dhv-copy]');
      this.debugPanel = root.querySelector('[data-aistea-3dhv-debug]');
      this.debugCoords = root.querySelector('[data-aistea-3dhv-debug-coords]');
      this.debugCopyButtons = Array.from(root.querySelectorAll('[data-aistea-3dhv-debug-copy]'));
      this.resetButton = root.querySelector('[data-aistea-3dhv-reset]');
      this.navButtons = Array.from(root.querySelectorAll('[data-aistea-3dhv-nav-item]'));
      this.hotspotButtons = Array.from(root.querySelectorAll('.aistea-3dhv__hotspot'));
      this.hotspots = this.parseHotspots(root.dataset.hotspots || '[]');
      this.modelUrl = root.dataset.modelUrl || '';
      this.posterUrl = root.dataset.posterUrl || '';
      this.envMapUrl = root.dataset.envMapUrl || '';
      this.threeUrl = root.dataset.threeUrl || '';
      this.gltfLoaderUrl = root.dataset.gltfLoaderUrl || '';
      this.dracoLoaderUrl = root.dataset.dracoLoaderUrl || this.getDracoLoaderUrl(this.gltfLoaderUrl);
      this.backgroundColor = root.dataset.backgroundColor || '#0f1014';
      this.debugPickerEnabled = root.dataset.debugPicker === '1';
      this.glassBoneEnabled = root.dataset.glassBone === '1';

      this.runtime = null;
      this.renderer = null;
      this.scene = null;
      this.camera = null;
      this.model = null;
      this.materials = null;
      this.meshMaterialMap = new Map();
      this.raycaster = null;
      this.pointerNdc = null;
      this.lastPickedCoords = null;
      this.pickMarker = null;
      this.cameraTarget = null;
      this.focusAnimation = null;
      this.defaultCameraPosition = null;
      this.defaultCameraTarget = null;
      this.rafId = 0;
      this.activeIndex = this.hotspots.length > 0 ? 0 : -1;
      this.resizeObserver = null;
      this.visibilityObserver = null;
      this.booted = false;
      this.viewportVisible = false;
      this.handleDocumentVisibilityChange = () => this.maybeToggleRenderLoop();

      this.init();
    }

    parseHotspots(raw) {
      try {
        const value = JSON.parse(raw);
        return Array.isArray(value) ? value : [];
      } catch (_) {
        return [];
      }
    }

    getDracoLoaderUrl(gltfLoaderUrl) {
      if (!gltfLoaderUrl) return '';
      try {
        return new URL('DRACOLoader.js', new URL(gltfLoaderUrl, document.baseURI)).toString();
      } catch (_) {
        return '';
      }
    }

    getDracoDecoderPath(dracoLoaderUrl) {
      if (!dracoLoaderUrl) return '';
      try {
        return new URL('./draco/', new URL(dracoLoaderUrl, document.baseURI)).toString();
      } catch (_) {
        return '';
      }
    }

    init() {
      if (!this.canvas || !this.overlay || !this.copy) {
        return;
      }

      this.bindHotspots();

      if (!this.modelUrl) {
        this.setStatus('No 3D model configured.');
        return;
      }

      document.addEventListener('visibilitychange', this.handleDocumentVisibilityChange);
      this.observeVisibility();
    }

    observeVisibility() {
      if (!('IntersectionObserver' in window)) {
        this.handleEnterViewport();
        return;
      }

      this.visibilityObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            this.handleEnterViewport();
          } else {
            this.viewportVisible = false;
            this.maybeToggleRenderLoop();
          }
        });
      }, { rootMargin: '400px 0px', threshold: 0.01 });

      this.visibilityObserver.observe(this.root);
    }

    handleEnterViewport() {
      this.viewportVisible = true;

      if (this.booted) {
        this.maybeToggleRenderLoop();
        return;
      }

      this.booted = true;
      this.boot();
    }

    async boot() {
      try {
        this.runtime = await this.getThreeRuntime();
        this.setupScene();
        await this.loadModel();
        this.observeResize();
        this.maybeToggleRenderLoop();
      } catch (error) {
        const message = error && error.message ? error.message : 'Unknown 3D error';
        this.setStatus(`3D load failed: ${message}`);
      }
    }

    async getThreeRuntime() {
      if (!this.threeUrl || !this.gltfLoaderUrl) {
        throw new Error('Viewer dependencies are missing');
      }

      const [threeMod, loaderMod, dracoMod] = await Promise.all([
        import(this.threeUrl),
        import(this.gltfLoaderUrl),
        this.dracoLoaderUrl ? import(this.dracoLoaderUrl) : Promise.resolve(null),
      ]);
      const THREE = (threeMod && (threeMod.default || threeMod)) || null;
      const GLTFLoader = loaderMod && loaderMod.GLTFLoader ? loaderMod.GLTFLoader : null;

      if (!THREE || !GLTFLoader) {
        throw new Error('Viewer dependencies could not be loaded');
      }

      return {
        THREE,
        GLTFLoader,
        DRACOLoader: dracoMod && dracoMod.DRACOLoader ? dracoMod.DRACOLoader : null,
      };
    }

    setupScene() {
      const THREE = this.runtime.THREE;
      const width = this.canvas.clientWidth || this.canvas.parentElement.clientWidth || 800;
      const height = this.canvas.clientHeight || this.canvas.parentElement.clientHeight || 520;

      this.scene = new THREE.Scene();
      this.scene.background = new THREE.Color(this.backgroundColor);

      this.camera = new THREE.PerspectiveCamera(45, width / height, 0.01, 2000);
      this.camera.position.set(0, 2, 5);

      this.renderer = new THREE.WebGLRenderer({
        canvas: this.canvas,
        antialias: true,
        alpha: true,
      });
      this.renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
      this.renderer.setSize(width, height, false);
      this.renderer.shadowMap.enabled = true;
      this.renderer.shadowMap.type = THREE.PCFSoftShadowMap;
      // Scene and lights never move after load, so recomputing the shadow map every
      // frame is wasted GPU work. Recompute once, manually, when it actually changes.
      this.renderer.shadowMap.autoUpdate = false;

      if ('outputColorSpace' in this.renderer && THREE.SRGBColorSpace) {
        this.renderer.outputColorSpace = THREE.SRGBColorSpace;
      } else if ('outputEncoding' in this.renderer && THREE.sRGBEncoding) {
        this.renderer.outputEncoding = THREE.sRGBEncoding;
      }

      if ('toneMapping' in this.renderer && THREE.ACESFilmicToneMapping) {
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.renderer.toneMappingExposure = 1;
      }

      this.setupLights(THREE);
      this.raycaster = new THREE.Raycaster();
      this.pointerNdc = new THREE.Vector2();
      this.cameraTarget = new THREE.Vector3(0, 0, 0);
      this.defaultCameraPosition = this.camera.position.clone();
      this.defaultCameraTarget = this.cameraTarget.clone();
      this.materials = this.createMaterialLibrary(THREE);
    }

    setupLights(THREE) {
      const ambient = new THREE.AmbientLight(0xffffff, 0.1);
      const keyLight = new THREE.DirectionalLight(0xfff0e0, 0.25);
      const fillLight = new THREE.DirectionalLight(0xe0e8ff, 0.08);
      const rimLight = new THREE.DirectionalLight(0xffffff, 0.1);

      keyLight.position.set(5, 8, 4);
      keyLight.castShadow = true;
      keyLight.shadow.mapSize.width = 2048;
      keyLight.shadow.mapSize.height = 2048;
      keyLight.shadow.camera.near = 0.1;
      keyLight.shadow.camera.far = 40;
      keyLight.shadow.camera.left = -10;
      keyLight.shadow.camera.right = 10;
      keyLight.shadow.camera.top = 10;
      keyLight.shadow.camera.bottom = -10;
      keyLight.shadow.bias = -0.001;

      fillLight.position.set(-2, 3, -2);
      rimLight.position.set(0, 10, 0);

      this.scene.add(ambient, keyLight, fillLight, rimLight);
    }

    createMaterialLibrary(THREE) {
      const materials = {
        titanium: new THREE.MeshStandardMaterial({ color: 0x2a3b4d, metalness: 1.0, roughness: 0.25, envMapIntensity: 4.5, side: THREE.DoubleSide }),
        titanBlue: new THREE.MeshStandardMaterial({ color: 0x35548c, metalness: 1.0, roughness: 0.25, envMapIntensity: 4.0, side: THREE.DoubleSide }),
        titanDarkblue: new THREE.MeshStandardMaterial({ color: 0x142850, metalness: 1.0, roughness: 0.28, envMapIntensity: 4.0, side: THREE.DoubleSide }),
        redBone: new THREE.MeshStandardMaterial({ color: 0x660000, metalness: 0.1, roughness: 0.6, envMapIntensity: 0.7, side: THREE.DoubleSide }),
        whiteBone: new THREE.MeshStandardMaterial({ color: 0xdcdce0, metalness: 0.1, roughness: 0.65, envMapIntensity: 0.6, side: THREE.DoubleSide }),
        purpleMetal: new THREE.MeshStandardMaterial({ color: 0x2a1558, metalness: 1.0, roughness: 0.3, envMapIntensity: 3.0, side: THREE.DoubleSide }),
        gold: new THREE.MeshStandardMaterial({ color: 0x3d2800, metalness: 1.0, roughness: 0.3, envMapIntensity: 3.0, side: THREE.DoubleSide }),
        greenMetal: new THREE.MeshStandardMaterial({ color: 0x0b4d1c, metalness: 1.0, roughness: 0.3, envMapIntensity: 3.0, side: THREE.DoubleSide }),
        cartilage: new THREE.MeshStandardMaterial({ color: 0xe0e0e0, metalness: 0.0, roughness: 0.9, envMapIntensity: 0.5, side: THREE.DoubleSide }),
        light_metal: new THREE.MeshStandardMaterial({ color: 0xc0c0c0, metalness: 0.9, roughness: 0.3, envMapIntensity: 3.0, side: THREE.DoubleSide }),
        teeth: new THREE.MeshStandardMaterial({ color: 0xffffff, metalness: 0.0, roughness: 0.3, envMapIntensity: 1.5, side: THREE.DoubleSide }),
        thread: new THREE.MeshStandardMaterial({ color: 0x303030, metalness: 0.6, roughness: 0.7, envMapIntensity: 1.0, side: THREE.DoubleSide }),
      };

      if (this.glassBoneEnabled) {
        [materials.redBone, materials.whiteBone, materials.cartilage].forEach((material) => {
          material.transparent = true;
          material.opacity = 0.28;
          material.depthWrite = false;
          material.roughness = 0.15;
          material.metalness = 0.0;
          material.envMapIntensity = 1.2;
        });
      }

      return materials;
    }

    async loadModel() {
      const { THREE, GLTFLoader, DRACOLoader } = this.runtime;
      const loader = new GLTFLoader();
      if (DRACOLoader) {
        const dracoLoader = new DRACOLoader();
        dracoLoader.setDecoderPath(this.getDracoDecoderPath(this.dracoLoaderUrl));
        loader.setDRACOLoader(dracoLoader);
      }
      const gltf = await loader.loadAsync(this.modelUrl);

      this.model = gltf.scene;
      this.scene.add(this.model);
      await this.applyRenderingEnhancements(THREE);
      this.fitModelIntoView(THREE);
      this.enableModelInteraction(THREE);
      this.bindDebugPicker();
      this.renderer.shadowMap.needsUpdate = true;

      if (this.poster) {
        this.poster.classList.add('is-hidden');
      }

      this.setStatus('');
      this.updateHotspotPositions();
      if (this.activeIndex >= 0) {
        this.activateHotspot(this.activeIndex);
      }
    }

    async applyRenderingEnhancements(THREE) {
      const applyMaterials = () => {
        if (!this.model || !this.materials) {
          return;
        }

        this.model.traverse((child) => {
          if (!child.isMesh || !child.material) {
            return;
          }

          child.castShadow = true;
          child.receiveShadow = true;

          const originalName = String(child.material.name || child.name || '').toLowerCase();
          this.meshMaterialMap.set(child.uuid, originalName);

          const materialMap = {
            blau_titanium: this.materials.titanium,
            blue_titanium: this.materials.titanium,
            a_5850: this.materials.titanium,
            titan_blue: this.materials.titanBlue,
            titan_darkblue: this.materials.titanDarkblue,
            red_bone: this.materials.redBone,
            red: this.materials.redBone,
            bone: this.materials.whiteBone,
            purple_titanium: this.materials.purpleMetal,
            purple: this.materials.purpleMetal,
            pink: this.materials.purpleMetal,
            gold: this.materials.gold,
            green: this.materials.greenMetal,
            cartilage: this.materials.cartilage,
            light_metal: this.materials.light_metal,
            teeth: this.materials.teeth,
            thread: this.materials.thread,
          };

          for (const [needle, material] of Object.entries(materialMap)) {
            if (originalName.includes(needle) && !(needle === 'bone' && originalName.includes('red'))) {
              const replacement = material.clone();
              replacement.name = child.material.name;
              child.material = replacement;
              break;
            }
          }
        });
      };

      if (!this.envMapUrl) {
        applyMaterials();
        return;
      }

      await new Promise((resolve) => {
        const textureLoader = new THREE.TextureLoader();
        textureLoader.load(
          this.envMapUrl,
          (texture) => {
            texture.mapping = THREE.EquirectangularReflectionMapping;
            if ('colorSpace' in texture && THREE.SRGBColorSpace) {
              texture.colorSpace = THREE.SRGBColorSpace;
            } else if ('encoding' in texture && THREE.sRGBEncoding) {
              texture.encoding = THREE.sRGBEncoding;
            }
            texture.flipY = true;

            const pmremGenerator = new THREE.PMREMGenerator(this.renderer);
            const envMap = pmremGenerator.fromEquirectangular(texture).texture;

            this.scene.environment = envMap;
            Object.values(this.materials).forEach((material) => {
              material.envMap = envMap;
              material.needsUpdate = true;
            });

            applyMaterials();
            texture.dispose();
            pmremGenerator.dispose();
            resolve();
          },
          undefined,
          () => {
            applyMaterials();
            resolve();
          }
        );
      });
    }

    fitModelIntoView(THREE) {
      if (!this.model || !this.camera) {
        return;
      }

      const box = new THREE.Box3().setFromObject(this.model);
      const center = box.getCenter(new THREE.Vector3());
      const size = box.getSize(new THREE.Vector3());
      const maxDim = Math.max(size.x, size.y, size.z) || 1;
      const scale = 6 / maxDim;

      this.model.scale.multiplyScalar(scale);
      this.model.position.sub(center.multiplyScalar(scale));

      this.camera.position.set(0, 2, 5);
      this.cameraTarget.set(0, 0, 0);
      this.defaultCameraPosition = this.camera.position.clone();
      this.defaultCameraTarget = this.cameraTarget.clone();
      this.camera.lookAt(this.cameraTarget);
      this.camera.updateProjectionMatrix();
    }

    enableModelInteraction(THREE) {
      if (!this.canvas || !this.camera || !this.model) {
        return;
      }

      let isDragging = false;
      let pointerId = null;
      let lastX = 0;
      let lastY = 0;
      let distance = this.camera.position.distanceTo(this.cameraTarget) || 5;
      const minDistance = 3;
      const maxDistance = 10;

      const updateCameraDistance = (nextDistance) => {
        distance = THREE.MathUtils.clamp(nextDistance, minDistance, maxDistance);
        const direction = this.camera.position.clone().sub(this.cameraTarget).normalize();
        this.camera.position.copy(this.cameraTarget.clone().add(direction.multiplyScalar(distance)));
        this.camera.lookAt(this.cameraTarget);
        this.camera.updateProjectionMatrix();
      };

      this.canvas.style.touchAction = 'none';

      this.canvas.addEventListener('pointerdown', (event) => {
        isDragging = true;
        pointerId = event.pointerId;
        lastX = event.clientX;
        lastY = event.clientY;
        if (this.canvas.setPointerCapture) {
          this.canvas.setPointerCapture(pointerId);
        }
      }, { passive: true });

      this.canvas.addEventListener('pointermove', (event) => {
        if (!isDragging || event.pointerId !== pointerId || !this.model) {
          return;
        }

        const dx = event.clientX - lastX;
        const dy = event.clientY - lastY;
        lastX = event.clientX;
        lastY = event.clientY;

        this.model.rotation.y += dx * 0.01;
        this.model.rotation.x += dy * 0.006;
        this.model.rotation.x = THREE.MathUtils.clamp(this.model.rotation.x, -Math.PI / 3, Math.PI / 3);
      }, { passive: true });

      const stopDragging = (event) => {
        if (event.pointerId !== pointerId) {
          return;
        }
        isDragging = false;
        if (this.canvas.releasePointerCapture) {
          this.canvas.releasePointerCapture(pointerId);
        }
        pointerId = null;
      };

      this.canvas.addEventListener('pointerup', stopDragging, { passive: true });
      this.canvas.addEventListener('pointercancel', stopDragging, { passive: true });
      this.canvas.addEventListener('wheel', (event) => {
        event.preventDefault();
        const normalized = THREE.MathUtils.clamp(Number(event.deltaY || 0), -120, 120);
        const zoomFactor = Math.exp(normalized * 0.0018);
        updateCameraDistance(distance * zoomFactor);
      }, { passive: false });

      if (this.resetButton) {
        this.resetButton.addEventListener('click', () => this.resetView());
      }
    }

    bindHotspots() {
      this.hotspotButtons.forEach((button, index) => {
        button.addEventListener('click', () => this.activateHotspot(index));
      });
      this.navButtons.forEach((button, index) => {
        button.addEventListener('click', () => this.activateHotspot(index));
      });
    }

    activateHotspot(index) {
      const hotspot = this.hotspots[index];
      if (!hotspot || !this.copy) {
        return;
      }

      this.activeIndex = index;
      this.hotspotButtons.forEach((button, buttonIndex) => {
        button.classList.toggle('is-active', buttonIndex === index);
      });
      this.navButtons.forEach((button, buttonIndex) => {
        button.classList.toggle('is-active', buttonIndex === index);
      });
      this.focusOnHotspot(hotspot);

      this.copy.classList.add('is-changing');
      window.setTimeout(() => {
        const titleNode = this.copy.querySelector('.aistea-3dhv__copyTitle');
        const bodyNode = this.copy.querySelector('.aistea-3dhv__copyBody');
        if (titleNode) {
          titleNode.textContent = hotspot.title || `Hotspot ${index + 1}`;
        }
        if (bodyNode) {
          bodyNode.textContent = hotspot.description || '';
        }
        this.copy.classList.remove('is-changing');
      }, 120);
    }

    bindDebugPicker() {
      if (!this.debugPickerEnabled || !this.debugPanel || !this.debugCoords || this.debugCopyButtons.length === 0 || !this.model) {
        return;
      }

      this.debugPanel.hidden = false;
      this.addDebugPickerGizmos();

      this.canvas.addEventListener('click', (event) => {
        if (!this.raycaster || !this.pointerNdc || !this.camera || !this.model) {
          return;
        }

        const rect = this.canvas.getBoundingClientRect();
        this.pointerNdc.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
        this.pointerNdc.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;

        this.raycaster.setFromCamera(this.pointerNdc, this.camera);
        const intersections = this.raycaster.intersectObject(this.model, true);
        if (!intersections.length) {
          return;
        }

        const localPoint = this.model.worldToLocal(intersections[0].point.clone());
        this.lastPickedCoords = {
          x: Number(localPoint.x.toFixed(6)),
          y: Number(localPoint.y.toFixed(6)),
          z: Number(localPoint.z.toFixed(6)),
        };
        this.debugCoords.textContent = JSON.stringify(this.lastPickedCoords, null, 2);

        if (this.pickMarker) {
          this.pickMarker.position.copy(localPoint);
          this.pickMarker.visible = true;
        }
      });

      this.debugCopyButtons.forEach((button) => {
        button.addEventListener('click', async () => {
          if (!this.lastPickedCoords || !navigator.clipboard || !navigator.clipboard.writeText) {
            return;
          }

          const mode = button.getAttribute('data-aistea-3dhv-debug-copy') || 'json';
          let payload = JSON.stringify(this.lastPickedCoords);
          if (mode === 'x') {
            payload = this.lastPickedCoords.x.toFixed(6);
          } else if (mode === 'y') {
            payload = this.lastPickedCoords.y.toFixed(6);
          } else if (mode === 'z') {
            payload = this.lastPickedCoords.z.toFixed(6);
          }

          try {
            await navigator.clipboard.writeText(payload);
          } catch (_) {
            // Ignore clipboard errors silently.
          }
        });
      });
    }

    addDebugPickerGizmos() {
      const THREE = this.runtime.THREE;
      const box = new THREE.Box3().setFromObject(this.model);
      const size = box.getSize(new THREE.Vector3());
      const extent = Math.max(size.x, size.y, size.z) || 2;

      const axesHelper = new THREE.AxesHelper(extent * 0.65);
      axesHelper.renderOrder = 998;
      this.model.add(axesHelper);

      this.pickMarker = new THREE.Mesh(
        new THREE.SphereGeometry(extent * 0.02, 16, 16),
        new THREE.MeshBasicMaterial({ color: 0xff2d55, depthTest: false })
      );
      this.pickMarker.visible = false;
      this.pickMarker.renderOrder = 999;
      this.model.add(this.pickMarker);
    }

    resetView() {
      if (!this.camera || !this.cameraTarget || !this.defaultCameraPosition || !this.defaultCameraTarget) {
        return;
      }

      this.focusAnimation = {
        startedAt: performance.now(),
        duration: 700,
        startPosition: this.camera.position.clone(),
        endPosition: this.defaultCameraPosition.clone(),
        startTarget: this.cameraTarget.clone(),
        endTarget: this.defaultCameraTarget.clone(),
      };

      this.hotspotButtons.forEach((button) => button.classList.remove('is-active'));
      this.navButtons.forEach((button) => button.classList.remove('is-active'));
      this.activeIndex = -1;
    }

    focusOnHotspot(hotspot) {
      if (!hotspot || !hotspot.position || !this.runtime || !this.model || !this.camera || !this.cameraTarget) {
        return;
      }

      const THREE = this.runtime.THREE;
      const worldTarget = new THREE.Vector3(
        Number(hotspot.position.x || 0),
        Number(hotspot.position.y || 0),
        Number(hotspot.position.z || 0)
      );
      this.model.localToWorld(worldTarget);

      const startPosition = this.camera.position.clone();
      const startTarget = this.cameraTarget.clone();
      const offset = this.camera.position.clone().sub(this.cameraTarget);
      const currentDistance = Math.max(offset.length(), 0.0001);
      const direction = offset.normalize();
      const desiredDistance = THREE.MathUtils.clamp(currentDistance * 0.82, 2.4, 8);
      const endTarget = worldTarget;
      const endPosition = endTarget.clone().add(direction.multiplyScalar(desiredDistance));
      const duration = 650;
      const startedAt = performance.now();

      this.focusAnimation = {
        startedAt,
        duration,
        startPosition,
        endPosition,
        startTarget,
        endTarget,
      };
    }

    updateFocusAnimation(now) {
      if (!this.focusAnimation || !this.camera || !this.cameraTarget) {
        return;
      }

      const THREE = this.runtime.THREE;
      const elapsed = now - this.focusAnimation.startedAt;
      const rawProgress = Math.min(1, Math.max(0, elapsed / this.focusAnimation.duration));
      const eased = rawProgress < 0.5
        ? 4 * rawProgress * rawProgress * rawProgress
        : 1 - Math.pow(-2 * rawProgress + 2, 3) / 2;

      this.camera.position.lerpVectors(
        this.focusAnimation.startPosition,
        this.focusAnimation.endPosition,
        eased
      );
      this.cameraTarget.lerpVectors(
        this.focusAnimation.startTarget,
        this.focusAnimation.endTarget,
        eased
      );
      this.camera.lookAt(this.cameraTarget);

      if (rawProgress >= 1) {
        this.camera.position.copy(this.focusAnimation.endPosition);
        this.cameraTarget.copy(this.focusAnimation.endTarget);
        this.focusAnimation = null;
      }
    }

    observeResize() {
      this.resizeObserver = new ResizeObserver(() => this.resize());
      this.resizeObserver.observe(this.root);
      window.addEventListener('resize', () => this.resize(), { passive: true });
    }

    resize() {
      if (!this.renderer || !this.camera || !this.canvas) {
        return;
      }

      const width = this.canvas.clientWidth || this.canvas.parentElement.clientWidth || 1;
      const height = this.canvas.clientHeight || this.canvas.parentElement.clientHeight || 1;
      this.camera.aspect = width / height;
      this.camera.updateProjectionMatrix();
      this.renderer.setSize(width, height, false);
      this.updateHotspotPositions();
    }

    updateHotspotPositions() {
      if (!this.camera || !this.overlay || !this.runtime || !this.model) {
        return;
      }

      const THREE = this.runtime.THREE;
      const overlayRect = this.overlay.getBoundingClientRect();
      if (overlayRect.width <= 0 || overlayRect.height <= 0) {
        return;
      }

      this.hotspots.forEach((hotspot, index) => {
        const button = this.hotspotButtons[index];
        if (!button || !hotspot.position) {
          return;
        }

        const vector = new THREE.Vector3(
          Number(hotspot.position.x || 0),
          Number(hotspot.position.y || 0),
          Number(hotspot.position.z || 0)
        );
        this.model.localToWorld(vector);
        vector.project(this.camera);

        const visible = vector.z >= -1 && vector.z <= 1;
        if (!visible) {
          button.hidden = true;
          return;
        }

        const x = ((vector.x + 1) / 2) * overlayRect.width;
        const y = ((-vector.y + 1) / 2) * overlayRect.height;
        const inFrame = x >= 0 && x <= overlayRect.width && y >= 0 && y <= overlayRect.height;
        button.hidden = !inFrame;
        if (!inFrame) {
          return;
        }
        button.style.left = `${x}px`;
        button.style.top = `${y}px`;
      });
    }

    maybeToggleRenderLoop() {
      const shouldRender = this.viewportVisible && document.visibilityState !== 'hidden' && !!this.renderer;
      if (shouldRender) {
        this.startRenderLoop();
      } else {
        this.stopRenderLoop();
      }
    }

    startRenderLoop() {
      if (this.rafId || !this.renderer || !this.scene || !this.camera) {
        return;
      }

      const frame = (now) => {
        if (this.focusAnimation) {
          this.updateFocusAnimation(now || performance.now());
        } else if (this.cameraTarget) {
          this.camera.lookAt(this.cameraTarget);
        }
        this.renderer.render(this.scene, this.camera);
        this.updateHotspotPositions();
        this.rafId = window.requestAnimationFrame(frame);
      };

      this.rafId = window.requestAnimationFrame(frame);
    }

    stopRenderLoop() {
      if (this.rafId) {
        window.cancelAnimationFrame(this.rafId);
        this.rafId = 0;
      }
    }

    setStatus(message) {
      if (!this.status) {
        return;
      }
      this.status.textContent = message;
      this.status.hidden = message === '';
    }
  }

  document.querySelectorAll(SELECTOR).forEach((root) => {
    new ThreeDHotspotViewer(root);
  });
})();
