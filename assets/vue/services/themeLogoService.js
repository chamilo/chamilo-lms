import baseService from "./baseService"

/**
 * Builds an Error from an axios error, preferring the server-provided message.
 * @param {any} error
 * @param {string} fallback
 * @returns {Error}
 */
function toError(error, fallback) {
  const data = error?.response?.data

  if (typeof data === "string" && data) {
    return new Error(data)
  }

  if (data && typeof data === "object") {
    return new Error(data.message || data.error || fallback)
  }

  return new Error(error?.message || fallback)
}

/**
 * Uploads theme logo files (header/email, SVG/PNG) for a given theme slug.
 * @param {string} slug
 * @param {{headerSvg?: File, headerPng?: File, emailSvg?: File, emailPng?: File}} files
 * @returns {Promise<Object>}
 */
async function upload(slug, { headerSvg, headerPng, emailSvg, emailPng }) {
  const fd = new FormData()
  if (headerSvg) fd.append("header_svg", headerSvg)
  if (headerPng) fd.append("header_png", headerPng)
  if (emailSvg) fd.append("email_svg", emailSvg)
  if (emailPng) fd.append("email_png", emailPng)

  try {
    return await baseService.post(`/themes/${encodeURIComponent(slug)}/logos`, fd)
  } catch (error) {
    throw toError(error, `Upload failed (${error?.response?.status ?? ""})`)
  }
}

/**
 * Removes a theme logo of the given type for a theme slug.
 * @param {string} slug
 * @param {string} type
 * @returns {Promise<any>}
 */
async function remove(slug, type) {
  try {
    return await baseService.delete(`/themes/${encodeURIComponent(slug)}/logos/${type}`)
  } catch (error) {
    throw toError(error, `Delete failed (${error?.response?.status ?? ""})`)
  }
}

export default { upload, remove }
