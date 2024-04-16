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
 * @param {string|null} iri
 * @param {string} title
 * @param {Object} colors
 * @returns {Promise<Object>}
 */
async function updateTheme({ iri = null, title, colors }) {
  if (iri) {
    return await baseService.put(iri, {
      title,
      variables: colors,
    })
  }

  return await baseService.post(url, {
    title,
    variables: colors,
  })
}

export default {
  getThemes,
  updateTheme,
}
