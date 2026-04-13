const LEGACY_SRC = new URL('./three.legacy.min.js', import.meta.url).toString();

if (!globalThis.THREE) {
  await new Promise((resolve, reject) => {
    const existing = document.querySelector('script[data-aistea-lib="three-legacy-bridge"]');
    if (existing) {
      existing.addEventListener('load', () => resolve(), { once: true });
      existing.addEventListener('error', () => reject(new Error('Failed loading three legacy bridge')), { once: true });
      return;
    }

    const script = document.createElement('script');
    script.src = LEGACY_SRC;
    script.async = true;
    script.dataset.aisteaLib = 'three-legacy-bridge';
    script.onload = () => resolve();
    script.onerror = () => reject(new Error(`Failed loading ${LEGACY_SRC}`));
    document.head.appendChild(script);
  });
}

const THREE = globalThis.THREE;
if (!THREE) {
  throw new Error('THREE global is not available');
}

export default THREE;
export const {
  ACESFilmicToneMapping,
  AmbientLight,
  Box3,
  Color,
  DirectionalLight,
  DoubleSide,
  EquirectangularReflectionMapping,
  Group,
  HemisphereLight,
  MathUtils,
  MeshPhysicalMaterial,
  MeshStandardMaterial,
  PMREMGenerator,
  PerspectiveCamera,
  Scene,
  SRGBColorSpace,
  TextureLoader,
  Vector3,
  WebGLRenderer,
  sRGBEncoding,
} = THREE;
