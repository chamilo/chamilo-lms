import baseService from "./baseService"

const endpoint = "/api/resource_files"

const post = async (formData) => {
  return await baseService.postForm(endpoint, formData)
}

/**
 * @param {number|string} resourceFileId
 * @returns {Promise<Object>}
 */
const findById = async (resourceFileId) => {
  return await baseService.get(`${endpoint}/${resourceFileId}`)
}

/**
 * @param {number|string} resourceNodeId
 * @returns {Promise<Object[]>}
 */
const getVariants = async (resourceNodeId) => {
  return await baseService.get(`/r/resource_files/${resourceNodeId}/variants`)
}

/**
 * @param {FormData} formData
 * @returns {Promise<Object>}
 */
const addVariant = async (formData) => {
  return await baseService.post(`${endpoint}/add_variant`, formData)
}

/**
 * @param {number|string} variantId
 * @returns {Promise<any>}
 */
const deleteVariant = async (variantId) => {
  return await baseService.delete(`/r/resource_files/${variantId}/delete_variant`)
}

export default {
  endpoint,
  post,
  findById,
  getVariants,
  addVariant,
  deleteVariant,
}
