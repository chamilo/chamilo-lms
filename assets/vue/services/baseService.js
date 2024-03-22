import api from "../config/api"

/**
 * @param {string} iri
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
async function find(iri) {
  return await api.get(iri)
}

async function post(params) {
  return await api.post("/api/resource_links", params)
}

export { find, post }
