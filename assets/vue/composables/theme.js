import { onMounted, ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import Color from "colorjs.io"
import { usePlatformConfig } from "../store/platformConfig"

export const useTheme = () => {
  const { t } = useI18n()

  let colors = {}

  onMounted(() => {
    // getCssVariableValue return empty if called too early, refresh colors when html is mounted
    // to ensure all values are set correctly
    for (const [key, value] of Object.entries(colors)) {
      value.value = getCssVariableValue(key)
    }
  })

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

  const getCssVariableValue = (variableName) => {
    const colorVariable = getComputedStyle(document.body).getPropertyValue(variableName)
    return getColorFromCSSVariable(colorVariable)
  }

  const setCssVariableValue = (variableName, color) => {
    document.documentElement.style.setProperty(variableName, colorToCSSVariable(color))
  }

  const getColors = () => {
    let colorsPlainObject = {}
    for (const [key, value] of Object.entries(colors)) {
      colorsPlainObject[key] = colorToCSSVariable(value.value)
    }
    return colorsPlainObject
  }

  const setColors = (colorsObj) => {
    for (const key in colorsObj) {
      if (colors[key] === undefined || colors[key] === null) {
        console.error(`Color with key ${key} is on color set`)
        continue
      }
      colors[key].value = getColorFromCSSVariable(colorsObj[key])
    }
  }

  const colorToCSSVariable = (color) => {
    // according to documentation https://developer.mozilla.org/en-US/docs/Web/CSS/color_value/rgb#syntax
    // the format "r g b" should work but because how rules in css are defined does not
    // so, we need the format "r, g, b" for variables,
    const r = Math.round(color.r * 255)
    const g = Math.round(color.g * 255)
    const b = Math.round(color.b * 255)
    return `${r} ${g} ${b}`
  }

  const getColorFromCSSVariable = (variable) => {
    return new Color(`rgb(${variable})`)
  }

  /**
   * @param {Color} color
   * @returns {Color}
   */
  function makeGradient(color) {
    const light = color.clone().to("oklab").l
    // when color is light (lightness > 0.5), darken gradient color
    // when color is dark, lighten gradient color
    // The values 0.5 and 1.6 were chosen through experimentation, there could be a better way to do this
    if (light > 0.5) {
      return color
        .clone()
        .set({ "oklab.l": (l) => l * 0.8 })
        .to("srgb")
    }

    return color
      .clone()
      .set({ "oklab.l": (l) => l * 1.6 })
      .to("srgb")
  }

  /**
   * @param {Color} color
   * @returns {Color}
   */
  function makeTextWithContrast(color) {
    // according to colorjs library https://colorjs.io/docs/contrast#accessible-perceptual-contrast-algorithm-apca
    // this algorithm is better than WCAGG 2.1 to check for contrast
    // "APCA is being evaluated for use in version 3 of the W3C Web Content Accessibility Guidelines (WCAG)"
    let onWhite = Math.abs(color.contrast("white", "APCA"))
    let onBlack = Math.abs(color.contrast("black", "APCA"))

    return onWhite > onBlack ? new Color("white") : new Color("black")
  }

  function checkColorContrast(background, foreground) {
    // using APCA for text contrast in buttons. In chamilo buttons the text
    // has a font size of 16px and weight of 600
    // Lc 60 The minimum level recommended for content text that is not body, column, or block text
    // https://git.apcacontrast.com/documentation/APCA_in_a_Nutshell#use-case--size-ranges
    let contrast = Math.abs(background.contrast(foreground, "APCA"))

    if (contrast < 60) {
      return t("Does not have enough contrast against background")
    }

    return ""
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

export function useVisualTheme() {
  const platformConfigStore = usePlatformConfig()

  function getThemeAssetUrl(path) {
    return `/themes/${platformConfigStore.visualTheme}/${path}`
  }

  return {
    getThemeAssetUrl,
  }
}
