import * as THREE_NS from './three.module.js';

const LEGACY_SRC = new URL('./GLTFLoader.legacy.js', import.meta.url).toString();

if (!globalThis.THREE?.GLTFLoader) {
  await new Promise((resolve, reject) => {
    const existing = document.querySelector('script[data-aistea-lib="gltfloader-legacy-bridge"]');
    if (existing) {
      existing.addEventListener('load', () => resolve(), { once: true });
      existing.addEventListener('error', () => reject(new Error('Failed loading GLTFLoader legacy bridge')), { once: true });
      return;
    }

    const script = document.createElement('script');
    script.src = LEGACY_SRC;
    script.async = true;
    script.dataset.aisteaLib = 'gltfloader-legacy-bridge';
    script.onload = () => resolve();
    script.onerror = () => reject(new Error(`Failed loading ${LEGACY_SRC}`));
    document.head.appendChild(script);
  });
}

const GLTFLoader = globalThis.THREE?.GLTFLoader;
if (!GLTFLoader) {
  throw new Error('GLTFLoader global is not available');
}

export { GLTFLoader, THREE_NS };
