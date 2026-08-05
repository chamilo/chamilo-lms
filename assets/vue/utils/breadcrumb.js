/**
 * Strip HTML tags from a string and trim surrounding whitespace.
 *
 * @param {string} value - Raw string, potentially containing HTML markup.
 * @returns {string} Plain-text content with all HTML tags removed.
 */
export function stripHtml(value) {
  if (!value || typeof value !== "string") {
    return ""
  }

  return value.replace(/<[^>]*>?/gm, "").trim()
}

/**
 * Read a query parameter as a safe integer, treating "0" as a valid value (not falsy).
 *
 * @param {Record<string, string | string[]>} query - The route query object to read from.
 * @param {string} key - Query parameter name to look up.
 * @param {number} [fallback=0] - Returned when the key is absent, empty, or non-numeric.
 * @returns {number} The parsed integer, or `fallback` if parsing fails.
 */
export function getQueryInt(query, key, fallback = 0) {
  const raw = query?.[key]

  if (raw === undefined || raw === null || raw === "") {
    return fallback
  }

  const n = Number(Array.isArray(raw) ? raw[0] : raw)

  return Number.isFinite(n) ? n : fallback
}

/**
 * Convert a camelCase or snake_case route/tool name into a human-readable title.
 * Example: `"AssignmentDetail"` → `"Assignment Detail"`, `"my_tool"` → `"My Tool"`.
 *
 * @param {string} name - Raw route or tool name.
 * @returns {string} Title-cased, space-separated label.
 */
export function formatToolName(name) {
  if (!name) {
    return ""
  }

  return name
    .replace(/([a-z])([A-Z])/g, "$1 $2")
    .replace(/_/g, " ")
    .replace(/\b\w/g, (c) => c.toUpperCase())
}

/**
 * Normalize a legacy URL coming from window.breadcrumb.
 *
 * Why:
 * - Some legacy crumbs provide absolute URLs, some provide "/main/..." paths,
 *   and some provide relative URLs like "lp_controller.php?...".
 * - Previous implementation incorrectly used "main/" index from window.location.href
 *   to slice item.url, which can truncate URLs into "/php?..." or "/on=...".
 *
 * Strategy:
 * 1) If we can find "main/" inside the same string, slice using its own index.
 * 2) If it's a relative path (no leading "/" and no scheme), resolve against current location.
 * 3) If everything fails, return "#" to avoid broken navigation.
 */
export function normalizeLegacyUrl(rawUrl) {
  const input = (rawUrl || "").toString().trim()

  if (!input) {
    return "#"
  }

  // Keep anchors and javascript pseudo-links safe.
  if (input === "#" || input.startsWith("javascript:")) {
    return "#"
  }

  // If this is already a site-absolute path, normalize to start at "/main/..." when possible.
  if (input.startsWith("/")) {
    const idx = input.indexOf("main/")

    if (idx >= 0) {
      return "/" + input.substring(idx)
    }

    return input
  }

  const extractMainPath = (url) => {
    const full = url.pathname + url.search + url.hash
    const idx = full.indexOf("main/")

    return idx >= 0 ? "/" + full.substring(idx) : full || "#"
  }

  // If this is an absolute URL, normalize to "/main/..." when possible.
  if (/^https?:\/\//i.test(input)) {
    try {
      return extractMainPath(new URL(input))
    } catch {
      return "#"
    }
  }

  // Relative URL like "lp_controller.php?action=..." -> resolve against current page.
  try {
    return extractMainPath(new URL(input, window.location.href))
  } catch {
    return "#"
  }
}
