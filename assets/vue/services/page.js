import api from "../config/api"
import makeService, { asResponse, toServiceError } from "./api"
import { normalize } from "../utils/hydra"

/**
 * Updates a page via HTTP PATCH using the JSON merge-patch content type.
 *
 * Overrides the generic makeService.update (which still uses PUT) because Page is a
 * Group B resource that already exposes a Patch operation; merge-patch keeps partial
 * payloads safe under the API Platform 4 migration (#8723). Returns the same
 * Response-like object as the generic service so the CRUD store (store/modules/crud.js)
 * keeps working unchanged.
 *
 * @param {Object} payload
 * @returns {Promise<Object>}
 */
async function update(payload) {
  try {
    const response = await api.patch(payload["@id"], normalize(payload), {
      headers: { "Content-Type": "application/merge-patch+json" },
    })

    return asResponse(response)
  } catch (error) {
    throw toServiceError(error)
  }
}

export default makeService("pages", { update })
