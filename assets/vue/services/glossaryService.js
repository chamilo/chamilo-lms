import baseService from "./baseService"

export default {
  // ---------------------------
  // Glossary CRUD (API entrypoint)
  // ---------------------------
  getGlossaryTerms: async (params) => {
    return await baseService.get("/api/glossaries", params)
  },

  getGlossaryTerm: async (termId) => {
    return await baseService.get(`/api/glossaries/${termId}`)
  },

  createGlossaryTerm: async (data) => {
    return await baseService.post("/api/glossaries", data)
  },

  updateGlossaryTerm: async (termId, data) => {
    return await baseService.put(`/api/glossaries/${termId}`, data)
  },

  export: async (formData) => {
    const response = await baseService.postRaw("/api/glossaries/export", formData, { responseType: "blob" })

    return {
      data: response.data,
      headers: response.headers,
    }
  },

  import: async (formData) => {
    return await baseService.post("/api/glossaries/import", formData)
  },

  exportToDocuments: async (data) => {
    return await baseService.post("/api/glossaries/export_to_documents", data)
  },

  deleteTerm: async (termId) => {
    return await baseService.delete(`/api/glossaries/${termId}`)
  },

  // ---------------------------
  // AI helpers (non-API entrypoint)
  // ---------------------------
  getTextProviders: async () => {
    return await baseService.get("/ai/text_providers")
  },

  getDefaultPrompt: async (params) => {
    return await baseService.get("/ai/glossary_default_prompt", params)
  },

  getAiCapabilities: async () => {
    return await baseService.get("/ai/capabilities")
  },

  getDocumentSources: async (params) => {
    return await baseService.get("/ai/glossary_document_sources", params)
  },

  generateGlossaryTerms: async (data) => {
    return await baseService.post("/ai/generate_glossary_terms", data)
  },
}
