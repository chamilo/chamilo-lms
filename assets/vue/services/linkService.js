import { ENTRYPOINT } from "../config/entrypoint"
import axios from "axios"
import baseService from "./baseService"

export default {
  /**
   * @param {Number|String} linkId
   * @param {FormData} imageData
   */
  uploadImage: async (linkId, imageData) => {
    const endpoint = `${ENTRYPOINT}links/${linkId}/upload-image`
    const response = await axios.post(endpoint, imageData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    })
    return response.data
  },

  /**
   * @param {Object} params
   */
  getLinks: async (params) => {
    const response = await axios.get(ENTRYPOINT + "links/", { params })

    return response.data
  },

  /**
   * @param {Number|String} linkId
   */
  getLink: async (linkId) => {
    const response = await axios.get(ENTRYPOINT + "links/" + linkId + "/details/")

    return response.data
  },

  /**
   * @param {Object} data
   */
  createLink: async (data) => {
    const endpoint = `${ENTRYPOINT}links`

    const response = await axios.post(endpoint, data)

    return response.data
  },

  /**
   * @param {Number|String} linkId
   * @param {Object} data
   */
  updateLink: async (linkId, data) => {
    const endpoint = `${ENTRYPOINT}links/${linkId}`
    data.id = linkId

    const response = await axios.put(endpoint, data)

    return response.data
  },

  /**
   * @param {number} linkId
   * @param {boolean} visible
   * @param {number} cid
   * @param {number} sid
   * @returns {Promise<Object>}
   */
  toggleLinkVisibility: async (linkId, visible, cid, sid) => {
    const endpoint = `${ENTRYPOINT}links/${linkId}/toggle_visibility?cid=${cid}&sid=${sid}`

    return baseService.put(endpoint, { visible })
  },

  /**
   * @param {Number|String} linkId
   * @param {Number} position
   */
  moveLink: async (linkId, position) => {
    const endpoint = `${ENTRYPOINT}links/${linkId}/move`
    const response = await axios.put(endpoint, { position })

    return response.data
  },

  /**
   * @param {Number|String} linkId
   */
  deleteLink: async (linkId) => {
    const endpoint = `${ENTRYPOINT}links/${linkId}`
    const response = await axios.delete(endpoint)

    return response.data
  },

  getCategories: async (parentId) => {
    const response = await axios.get(`${ENTRYPOINT}link_categories?resourceNode.parent=${parentId}`)

    return response.data["hydra:member"]
  },

  /**
   * @param {Number|String} categoryId
   */
  getCategory: async (categoryId) => {
    const response = await axios.get(ENTRYPOINT + "link_categories/" + categoryId)

    return response.data
  },

  /**
   * @param {Object} data
   */
  createCategory: async (data) => {
    const endpoint = `${ENTRYPOINT}link_categories`
    const response = await axios.post(endpoint, data)

    return response.data
  },

  /**
   * @param {Number|String} categoryId
   * @param {Object} data
   */
  updateCategory: async (categoryId, data) => {
    const endpoint = `${ENTRYPOINT}link_categories/${categoryId}`
    const response = await axios.put(endpoint, data)

    return response.data
  },

  /**
   * @param {Number|String} categoryId
   */
  deleteCategory: async (categoryId) => {
    const endpoint = `${ENTRYPOINT}link_categories/${categoryId}`
    const response = await axios.delete(endpoint)

    return response.data
  },

  /**
   * @param {number} categoryId
   * @param {boolean} visible
   * @param {number} cid
   * @param {number} sid
   */
  toggleCategoryVisibility: async (categoryId, visible, cid, sid) => {
    const endpoint = `${ENTRYPOINT}link_categories/${categoryId}/toggle_visibility?cid=${cid}&sid=${sid}`
    const response = await axios.put(endpoint, { visible })

    return response.data
  },

  /**
   * Checks if the URL is valid.
   * @param {String} url The URL to be checked.
   * @param linkId
   */
  checkLink: async (url, linkId) => {
    const endpoint = `${ENTRYPOINT}links/${linkId}/check`
    const response = await axios.get(endpoint, { params: { url } })

    return response.data
  },
}
