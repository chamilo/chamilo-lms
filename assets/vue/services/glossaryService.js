import axios from "axios"

export default {
  // ---------------------------
  // Glossary CRUD (API entrypoint)
  // ---------------------------
  getGlossaryTerms: async (params) => {
    const response = await axios.get("/api/glossaries", { params })
    return response.data
  },

  getGlossaryTerm: async (termId) => {
    const response = await axios.get(`/api/glossaries/${termId}`)
    return response.data
  },

  createGlossaryTerm: async (data) => {
    const response = await axios.post("/api/glossaries", data)
    return response.data
  },

  updateGlossaryTerm: async (termId, data) => {
    const response = await axios.put(`/api/glossaries/${termId}`, data)
    return response.data
  },

  export: async (formData) => {
    const response = await axios.post("/api/glossaries/export", formData, { responseType: "blob" })
    return response.data
  },

  import: async (formData) => {
    const response = await axios.post("/api/glossaries/import", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    })
    return response.data
  },

  exportToDocuments: async (data) => {
    const response = await axios.post("/api/glossaries/export_to_documents", data)
    return response.data
  },

  deleteTerm: async (termId) => {
    const response = await axios.delete(`/api/glossaries/${termId}`)
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
