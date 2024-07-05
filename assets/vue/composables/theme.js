import { onMounted, ref, watch } from "vue"
import Color from "colorjs.io"
import { usePlatformConfig } from "../store/platformConfig"

export const useTheme = () => {
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
    return colorFromCSSVariable(colorVariable)
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
      colors[key].value = colorFromCSSVariable(colorsObj[key])
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

  const colorFromCSSVariable = (variable) => {
    return new Color(`rgb(${variable})`)
  }

  return {
    getColorTheme,
    getColors,
    setColors,
  }
}

export function useVisualTheme() {
  const platformConfigStore = usePlatformConfig()

  const themeName = platformConfigStore.visualTheme

  function getThemeAssetUrl(path) {
    return `/themes/${platformConfigStore.visualTheme}/${path}`
  }

  return {
    themeName,
    getThemeAssetUrl,
  }
}
