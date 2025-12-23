import makeService from "./api"
import { ENTRYPOINT } from "../config/entrypoint"

const legalExtensions = {
  async findAllByLanguage(languageId) {
    const params = new URLSearchParams({
      languageId: languageId,
      "order[version]": "desc",
    })
    return fetch(`${ENTRYPOINT}legals?${params.toString()}`)
  },

  async findLatestByLanguage(languageId) {
    const params = new URLSearchParams({
      languageId: languageId,
      "order[version]": "desc",
      itemsPerPage: "1",
    })
    return fetch(`${ENTRYPOINT}legals?${params.toString()}`)
  },

  async findByLanguageAndVersion(languageId, version) {
    const params = new URLSearchParams({
      languageId: languageId,
      version: version,
      "order[type]": "asc",
      itemsPerPage: "50",
    })
    return fetch(`${ENTRYPOINT}legals?${params.toString()}`)
  },

  async findAllType0() {
    const params = new URLSearchParams({
      type: "0",
      "order[version]": "desc",
      itemsPerPage: "50",
    })
    return fetch(`${ENTRYPOINT}legals?${params.toString()}`)
  },

  async saveOrUpdateLegal(payload) {
    return fetch(`/legal/save`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    })
  },
}

export default makeService("legals", legalExtensions)
