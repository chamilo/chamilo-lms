import { onMounted, ref, watch } from "vue"

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
    const colorVariable = getComputedStyle(document.body).getPropertyValue(variableName).split(", ")
    return {
      r: parseInt(colorVariable[0]),
      g: parseInt(colorVariable[1]),
      b: parseInt(colorVariable[2]),
    }
  }

  const setCssVariableValue = (variableName, color) => {
    document.documentElement.style.setProperty(variableName, `${color.r}, ${color.g}, ${color.b}`)
  }

  const getColors = () => {
    let colorsPlainObject = {}
    for (const [key, value] of Object.entries(colors)) {
      colorsPlainObject[key] = `${value.value.r}, ${value.value.g}, ${value.value.b}`
    }
    return colorsPlainObject
  }

  return {
    getColorTheme,
    getColors,
  }
}
