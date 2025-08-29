import { onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import Color from "colorjs.io"
import { usePlatformConfig } from "../store/platformConfig"

const ROOT = typeof document !== "undefined" ? document.documentElement : null

const DEFAULTS = {
  "--color-primary-base": "#0d6efd",
  "--color-primary-gradient": "#0a58ca",
  "--color-primary-button-text": "#ffffff",
  "--color-primary-button-alternative-text": "#000000",

  "--color-secondary-base": "#6c757d",
  "--color-secondary-gradient": "#5c636a",
  "--color-secondary-button-text": "#ffffff",

  "--color-tertiary-base": "#212529",
  "--color-tertiary-gradient": "#0f1112",
  "--color-tertiary-button-text": "#ffffff",

  "--color-success-base": "#198754",
  "--color-success-gradient": "#146c43",
  "--color-success-button-text": "#ffffff",

  "--color-info-base": "#0dcaf0",
  "--color-info-gradient": "#31d2f2",
  "--color-info-button-text": "#000000",

  "--color-warning-base": "#ffc107",
  "--color-warning-gradient": "#e0a800",
  "--color-warning-button-text": "#000000",

  "--color-danger-base": "#dc3545",
  "--color-danger-gradient": "#bb2d3b",
  "--color-danger-button-text": "#ffffff",

  "--color-form-base": "#ced4da",
}

// ---------- Safe utilities ----------
function isString(v) {
  return typeof v === "string" || v instanceof String
}

/** Accepts hex/rgb/hsl/oklch or triplets "r g b" / "r, g, b" and returns Color */
function toColorSafe(input, fallback = "#000") {
  try {
    if (input instanceof Color) return input
    if (!input && input !== 0) return new Color(fallback)

    const s = String(input).trim()
    if (!s) return new Color(fallback)

    // If it already looks like a CSS color, pass through
    if (s.startsWith("#") || s.includes("(")) {
      return new Color(s)
    }

    // Triplet "r g b" or "r, g, b" (optional alpha)
    const parts = s
      .split(/[,\s/]+/)
      .filter(Boolean)
      .map(Number)
    if (parts.length >= 3 && parts.every((n) => Number.isFinite(n))) {
      const [r, g, b, a] = parts
      const css = a != null ? `rgb(${r} ${g} ${b} / ${a})` : `rgb(${r} ${g} ${b})`
      return new Color(css)
    }

    // Last attempt: raw string (e.g. 3/4/5 or named)
    return new Color(s)
  } catch {
    try {
      return new Color(fallback)
    } catch {
      return new Color("#000")
    }
  }
}

function readCssVarRaw(name) {
  if (!ROOT) return ""
  // Reading on :root is more reliable than on body
  const v = getComputedStyle(ROOT).getPropertyValue(name)
  return (v || "").trim()
}

function ensureDefaultsPresent() {
  if (!ROOT) return
  for (const [k, def] of Object.entries(DEFAULTS)) {
    const cur = readCssVarRaw(k)
    if (!cur) {
      // Store as "r g b" in CSS vars
      const c = toColorSafe(def)
      ROOT.style.setProperty(k, colorToTriplet(c))
    }
  }
}

/** Converts Color (or string) to "r g b" for rgb(var(--...)) usage */
function colorToTriplet(color) {
  const c = toColorSafe(color).to("srgb")
  const clamp01 = (x) => Math.min(1, Math.max(0, x ?? 0))
  const r = Math.round(clamp01(c.r) * 255)
  const g = Math.round(clamp01(c.g) * 255)
  const b = Math.round(clamp01(c.b) * 255)
  return `${r} ${g} ${b}`
}

// ---------- Main composable ----------
export const useTheme = () => {
  const { t } = useI18n()
  const colors = {} // map: varName -> ref(Color)

  onMounted(() => {
    // If some var was empty initially, ensure defaults are present
    ensureDefaultsPresent()
    // Refresh existing refs from CSS
    for (const [key, r] of Object.entries(colors)) {
      r.value = getCssVariableValue(key)
    }
  })

  function getCssVariableValue(variableName) {
    const raw = readCssVarRaw(variableName)
    const fallback = DEFAULTS[variableName] || "#000"
    return toColorSafe(raw || fallback, fallback)
  }

  function setCssVariableValue(variableName, color) {
    if (!ROOT) return
    ROOT.style.setProperty(variableName, colorToTriplet(color))
  }

  /** Returns a ref(Color) bound to the CSS var */
  const getColorTheme = (variableName) => {
    if (Object.hasOwn(colors, variableName)) {
      return colors[variableName]
    }
    const colorRef = ref(getCssVariableValue(variableName))
    watch(colorRef, (newColor) => {
      setCssVariableValue(variableName, newColor)
    })
    colors[variableName] = colorRef
    return colorRef
  }

  /** Plain object { "--var": "r g b", ... } */
  const getColors = () => {
    const out = {}
    for (const [key, value] of Object.entries(colors)) {
      out[key] = colorToTriplet(value.value)
    }
    return out
  }

  /**
   * Apply colors from JSON (hex or any supported format).
   * Creates refs if they don't exist yet.
   */
  const setColors = (colorsObj) => {
    if (!colorsObj || typeof colorsObj !== "object") return
    for (const [key, val] of Object.entries(colorsObj)) {
      const col = toColorSafe(val, DEFAULTS[key] || "#000")
      if (!colors[key]) {
        colors[key] = getColorTheme(key)
      }
      colors[key].value = col
      // setCssVariableValue is triggered via watch()
    }
  }

  /**
   * @param {Color|any} color
   * @returns {Color}
   */
  function makeGradient(color) {
    const c = toColorSafe(color).to("oklab")
    const light = c.l
    const out = c.clone()
    // If light, darken a bit; if dark, lighten
    out.l = light > 0.5 ? Math.max(0, out.l * 0.8) : Math.min(1, out.l * 1.6)
    return out.to("srgb")
  }

  /**
   * @param {Color|any} color
   * @returns {Color}
   */
  function makeTextWithContrast(color) {
    const c = toColorSafe(color)
    const onWhite = Math.abs(c.contrast("white", "APCA"))
    const onBlack = Math.abs(c.contrast("black", "APCA"))
    return onWhite > onBlack ? new Color("white") : new Color("black")
  }

  function checkColorContrast(background, foreground) {
    const bg = toColorSafe(background)
    const fg = toColorSafe(foreground)
    const contrast = Math.abs(bg.contrast(fg, "APCA"))
    return contrast < 60 ? t("Does not have enough contrast against background") : ""
  }

  return {
    getColorTheme,
    getColors,
    setColors,
    makeGradient,
    makeTextWithContrast,
    checkColorContrast,
  }
}

// ---------- Visual Theme (assets) ----------
export function useVisualTheme() {
  const platformConfigStore = usePlatformConfig()
  function getThemeAssetUrl(path) {
    const slug = platformConfigStore.visualTheme || "chamilo"
    return `/themes/${encodeURIComponent(slug)}/${path}`
  }
  return { getThemeAssetUrl }
}
