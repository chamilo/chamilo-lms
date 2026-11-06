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
  return await baseService.patch(iri, {
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

const packageUrl = (id) => `/api/color-theme-packages/${Number(id)}`

function exportUrl(id) {
  return `${packageUrl(id)}/export`
}

async function importPackage(archive, name) {
  const data = new FormData()
  data.append("archive", archive)
  data.append("name", name)
  return await baseService.post("/api/color-theme-packages/import", data)
}

async function renamePackage(id, name) {
  return await baseService.patch(`${packageUrl(id)}/rename`, { name })
}

async function deletePackage(id) {
  return await baseService.delete(packageUrl(id))
}

export default {
  list,
  create,
  update,
  findAllByCurrentUrl,
  changePlatformColorTheme,
  exportUrl,
  importPackage,
  renamePackage,
  deletePackage,
}
