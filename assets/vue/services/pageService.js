import api from "../config/api"
import baseService from "./baseService"

/**
 * @param {string} slug
 * @returns {Promise<Object|null>}
 */
async function getPublicPageBySlug(slug) {
  const { items } = await baseService.getCollection("/api/pages", { slug, "category.title": "public" })

  if (items.length) {
    return items[0]
  }

  return null
}

export default {
  getPublicPageBySlug,

  /**
   * @param {Object} params
   * @returns {Promise<Object>}
   */
  async post(params) {
    const { data } = await api.post("/api/pages", params)

    return data
  },

  /**
   * @param {string} iri
   * @param {Object} params
   * @returns {Promise<Object>}
   */
  async update(iri, params) {
    const { data } = await api.put(iri, params)

    return data
  },

  /**
   * @param {string} iri
   * @returns {Promise<void>}
   */
  async delete(iri) {
    await api.delete(iri)
  },
}
