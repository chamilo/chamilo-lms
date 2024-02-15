import api from "../config/api"

/**
 * @param {string} iri
 * @returns {Promise<axios.AxiosResponse<any>>}
 */
async function find(iri) {
  return await api.get(iri)
}

export {
  find
}
