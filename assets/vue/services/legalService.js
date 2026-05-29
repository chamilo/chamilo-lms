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

  // TODO: /legal/save is a controller endpoint (not API Platform); pending the
  // non-API fetch migration decision, it still uses native fetch.
  async saveOrUpdateLegal(payload) {
    return fetch(`/legal/save`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    })
  },
}

export default makeService("legals", legalExtensions)
