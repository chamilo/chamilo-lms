import { ENTRYPOINT } from "../config/entrypoint"
import axios from "axios"

export default {
  // ---------------------------
  // Glossary CRUD (API entrypoint)
  // ---------------------------
  getGlossaryTerms: async (params) => {
    const response = await axios.get(ENTRYPOINT + "glossaries", { params })
    return response.data
  },

  getGlossaryTerm: async (termId) => {
    const response = await axios.get(ENTRYPOINT + `glossaries/${termId}`)
    return response.data
  },

  createGlossaryTerm: async (data) => {
    const response = await axios.post(ENTRYPOINT + "glossaries", data)
    return response.data
  },

  updateGlossaryTerm: async (termId, data) => {
    const response = await axios.put(ENTRYPOINT + `glossaries/${termId}`, data)
    return response.data
  },

  export: async (formData) => {
    const endpoint = `${ENTRYPOINT}glossaries/export`
    const response = await axios.post(endpoint, formData, { responseType: "blob" })
    return response.data
  },

  import: async (formData) => {
    const endpoint = `${ENTRYPOINT}glossaries/import`
    const response = await axios.post(endpoint, formData, {
      headers: { "Content-Type": "multipart/form-data" },
    })
    return response.data
  },

  exportToDocuments: async (data) => {
    const endpoint = `${ENTRYPOINT}glossaries/export_to_documents`
    const response = await axios.post(endpoint, data)
    return response.data
  },

  deleteTerm: async (termId) => {
    const endpoint = `${ENTRYPOINT}glossaries/${termId}`
    const response = await axios.delete(endpoint)
    return response.data
  },

  // ---------------------------
  // AI helpers (non-API entrypoint)
  // ---------------------------
  getTextProviders: async () => {
    const response = await axios.get("/ai/text_providers")
    return response.data
  },

  getDefaultPrompt: async (params) => {
    const response = await axios.get("/ai/glossary_default_prompt", { params })
    return response.data
  },

  generateGlossaryTerms: async (data) => {
    const response = await axios.post("/ai/generate_glossary_terms", data)
    return response.data
  },
}
