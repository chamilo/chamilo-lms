import baseService from "./baseService"

const url = "/api/color_themes"

/**
 * Gets the color themes
 *
 * @returns {Promise<Array>}
 */
async function getThemes() {
  const { items } = await baseService.getCollection(url)

  return items
}

/**
 * Update or create a theme with the title
 *
 * @param {string} title
 * @param {Object} colors
 * @returns {Promise<Object>}
 */
async function updateTheme(title, colors) {
  await baseService.post(url, {
    title: title,
    variables: colors,
    active: true,
  })
}

export default {
  getThemes,
  updateTheme,
}
