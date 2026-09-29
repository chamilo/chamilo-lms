import makeService from "./api"
import baseService from "./baseService"

const legalExtensions = {
  async findAllByLanguage(languageId) {
    return baseService.getCollection("/api/legals", {
      languageId,
      "order[version]": "desc",
    })
  },

  async findLatestByLanguage(languageId) {
    return baseService.getCollection("/api/legals", {
      languageId,
      "order[version]": "desc",
      itemsPerPage: 1,
    })
  },

  async findByLanguageAndVersion(languageId, version) {
    return baseService.getCollection("/api/legals", {
      languageId,
      version,
      "order[type]": "asc",
      itemsPerPage: 50,
    })
  },

  async findAllType0() {
    return baseService.getCollection("/api/legals", {
      type: 0,
      "order[version]": "desc",
      itemsPerPage: 50,
    })
  },

  // /legal/save is a controller endpoint (not API Platform); baseService routes
  // it through the shared axios instance, which sets Accept: application/json
  // automatically for non-/api paths.
  async saveOrUpdateLegal(payload) {
    return baseService.post("/legal/save", payload)
  },

  async getAiTranslationConfiguration() {
    return baseService.get("/api/terms_and_conditions_translation")
  },

  async translateTermsWithAi(payload) {
    return baseService.post("/api/terms_and_conditions_translation", payload)
  },
}

export default makeService("legals", legalExtensions)
