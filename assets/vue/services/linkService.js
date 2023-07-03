import {ENTRYPOINT} from "../config/entrypoint";
import axios from "axios";


export default {
  /**
   * @param {Number|String} linkId
   */
  getLink: async(linkId) => {
    const response = await axios.get(ENTRYPOINT + 'links/' + linkId)
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
    data.id = linkId;

    const response = await axios.put(endpoint, data)
    return response.data
  },

  getCategories: async () => {
    const response = await axios.get(ENTRYPOINT + 'link_categories')
    return response.data['hydra:member']
  },

  /**
   * @param {Number|String} categoryId
   */
  getCategory: async (categoryId) => {
    const response = await axios.get(ENTRYPOINT + 'link_categories/' + categoryId)
    return response.data
  },

  /**
   * @param {Object} data
   */
  createCategory: async(data) => {
    const endpoint = `${ENTRYPOINT}link_categories`
    const response = await axios.post(endpoint, data)
    return response.data
  },

  /**
   * @param {Number|String} categoryId
   * @param {Object} data
   */
  updateCategory: async(categoryId, data) => {
    const endpoint = `${ENTRYPOINT}link_categories/${categoryId}`
    const response = await axios.put(endpoint, data)
    return response.data
  },
}
