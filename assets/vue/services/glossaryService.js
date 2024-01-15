import { ENTRYPOINT } from "../config/entrypoint"
import axios from "axios"

export default {
  /**
   * @param {Object} params
   */
  getGlossaryTerms: async (params) => {
    const response = await axios.get(ENTRYPOINT + "glossaries", { params })
    return response.data
  },

  /**
   * @param {String|Number} termId
   */
  getGlossaryTerm: async (termId) => {
    const response = await axios.get(ENTRYPOINT + `glossaries/${termId}`)
    return response.data
  },

  /**
   * @param {Object} data
   */
  createGlossaryTerm: async (data) => {
    const response = await axios.post(ENTRYPOINT + `glossaries`, data)
    return response.data
  },

  /**
   * @param {String|Number} termId
   * @param {Object} data
   */
  updateGlossaryTerm: async (termId, data) => {
    const response = await axios.put(ENTRYPOINT + `glossaries/${termId}`, data)
    return response.data
  },

  /**
   * @param {FormData} formData
   */
  export: async (formData) => {
    const endpoint = `${ENTRYPOINT}glossaries/export`
    const response = await axios.post(endpoint, formData, { responsetype: "blob " })
    return response.data
  },

  /**
   * @param {FormData} formData
   */
  import: async (formData) => {
    const endpoint = `${ENTRYPOINT}glossaries/import`
    const response = await axios.post(endpoint, formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    })
    return response.data
  },

  /**
   * @param {Object} data
   */
  exportToDocuments: async (data) => {
    const endpoint = `${ENTRYPOINT}glossaries/export_to_documents`
    const response = await axios.post(endpoint, data)
    return response.data
  },

  /**
   * @param {String|Number} termId
   */
  deleteTerm: async (termId) => {
    const endpoint = `${ENTRYPOINT}glossaries/${termId}`
    const response = await axios.delete(endpoint)
    return response.data
  },
}
