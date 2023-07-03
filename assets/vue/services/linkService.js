import {ENTRYPOINT} from "../config/entrypoint";
import axios from "axios";


export default {
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
   * @param {Number} linkId
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
   * @param {Object} data
   */
  createCategory: async(data) => {
    const endpoint = `${ENTRYPOINT}link_categories`
    const response = axios.post(endpoint, data)
    return response.data
  },

  /**
   * @param {Number} categoryId
   * @param {Object} data
   */
  updateCategory: async(categoryId, data) => {
    const endpoint = `${ENTRYPOINT}link_categories/${categoryId}`
    const response = await axios.put(endpoint, data)
    return response.data
  },
}
