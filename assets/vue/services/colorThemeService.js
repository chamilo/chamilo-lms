import baseService from "./baseService"

const url = "/api/color_themes"

/**
 * Gets the color themes
 *
 * @returns {Promise<{totalItems, items}>}
 */
async function findAllByCurrentUrl() {
  return await baseService.getCollection("/api/access_url_rel_color_themes")
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

/**
 * @param {string} iri
 * @returns {Promise<Object>}
 */
async function changePlatformColorTheme(iri) {
  return baseService.post("/api/access_url_rel_color_themes", {
    colorTheme: iri,
  })
}

export default {
  updateTheme,
  findAllByCurrentUrl,
  changePlatformColorTheme,
}
