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
 * Create a color theme
 *
 * @param {string} title
 * @param {Object} colors
 * @returns {Promise<Object>}
 */
async function create({ title, colors }) {
  return await baseService.post(url, {
    title,
    variables: colors,
  })
}

/**
 * Update a color theme
 *
 * @param {string} iri
 * @param {string} title
 * @param {Object} colors
 * @returns {Promise<Object>}
 */
async function update({ iri, title, colors }) {
  return await baseService.put(iri, {
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
  create,
  update,
  findAllByCurrentUrl,
  changePlatformColorTheme,
}
