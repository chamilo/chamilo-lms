import baseService from "./baseService"

/**
 * @param {string} courseIri
 * @param {number} urlId
 * @param {number} sessionId
 * @param {number} totalScore
 * @returns {Promise<Object>}
 */
export async function saveRanking({ courseIri, urlId, sessionId, totalScore }) {
  return await baseService.post("/api/track_course_rankings", {
    totalScore,
    course: courseIri,
    urlId,
    sessionId,
  })
}

/**
 * @param {string} iri
 * @param {number} totalScore
 * @returns {Promise<Object>}
 */
export async function updateRanking({ iri, totalScore }) {
  return await baseService.put(iri, { totalScore })
}
