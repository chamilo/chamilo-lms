import baseService from "./baseService"

const url = "/api/color_themes"

// Normalize a ColorTheme object: parse variables if string and ensure an object
function normalizeTheme(theme) {
  if (!theme) return theme
  if (typeof theme.variables === "string") {
    try {
      theme.variables = JSON.parse(theme.variables)
    } catch {
      /* noop */
    }
  }
  if (!theme.variables || typeof theme.variables !== "object") {
    theme.variables = {}
  }
  return theme
}

/**
 * List all color themes (works with API Platform/Hydra or plain arrays)
 * @returns {Promise<Array>}
 */
async function list({ pagination = false } = {}) {
  const qs = pagination === false ? "?pagination=false" : ""
  const data = await baseService.getCollection(`${url}${qs}`)
  const arr = Array.isArray(data?.items)
    ? data.items
    : Array.isArray(data?.["hydra:member"])
      ? data["hydra:member"]
      : Array.isArray(data)
        ? data
        : []
  return arr.map(normalizeTheme)
}

/**
 * Themes associated with the current Access URL.
 * Always returns { items: [{ colorTheme, active }, ...] }
 */
async function findAllByCurrentUrl() {
  const data = await baseService.getCollection("/api/access_url_rel_color_themes?pagination=false")
  const raw = Array.isArray(data?.items)
    ? data.items
    : Array.isArray(data?.["hydra:member"])
      ? data["hydra:member"]
      : Array.isArray(data)
        ? data
        : []

  const items = raw.map((r) => ({
    colorTheme: normalizeTheme(r.colorTheme || r["color_theme"] || r),
    active: !!(r.active ?? r.isActive ?? r["active"]),
  }))
  return { items }
}

/**
 * Create a color theme
 */
async function create({ title, colors }) {
  return await baseService.post(url, {
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
