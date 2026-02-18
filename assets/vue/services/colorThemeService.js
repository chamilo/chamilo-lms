import baseService from "./baseService"

/**
 * List all color themes (works with API Platform/Hydra or plain arrays)
 * @returns {Promise<Object[]>}
 */
async function list() {
  const { items } = await baseService.getCollection("/api/color_themes")

  return items
}

/**
 * Themes associated with the current Access URL.
 * @returns {Promise<Object[]>}
 */
async function findAllByCurrentUrl() {
  const { items } = await baseService.getCollection("/api/access_url_rel_color_themes")

  return items
}

/**
 * Create a color theme
 */
async function create({ title, colors }) {
  return await baseService.post("/api/color_themes", {
    title,
    variables: colors,
  })
}

/**
 * Update a color theme
 */
async function update({ iri, title, colors }) {
  return await baseService.put(iri, {
    title,
    variables: colors,
  })
}

/**
 * Mark a color theme as current for the Access URL
 */
async function changePlatformColorTheme(iri) {
  return baseService.post("/api/access_url_rel_color_themes", {
    colorTheme: iri,
  })
}

export default {
  list,
  create,
  update,
  findAllByCurrentUrl,
  changePlatformColorTheme,
}
