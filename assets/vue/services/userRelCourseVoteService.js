import baseService from "./baseService"

/**
 * Saves a new vote for a course in the catalog.
 *
 * @param {string} courseIri - IRI of the course
 * @param {number} userId - ID of the user who votes
 * @param {number} vote - Rating given by the user (1-5)
 * @param {number} sessionId - Session ID (optional)
 * @param {number} urlId - Access URL ID
 * @returns {Promise<Object>}
 */
export async function saveVote({ courseIri, userId, vote, sessionId = null, urlId }) {
  return await baseService.post("/api/user_rel_course_votes", {
    course: courseIri,
    user: `/api/users/${userId}`,
    vote,
    session: sessionId ? `/api/sessions/${sessionId}` : null,
    url: `/api/access_urls/${urlId}`,
  })
}

/**
 * Updates an existing vote for a course.
 *
 * @param {string} iri - IRI of the vote to update
 * @param {number} vote - New rating from the user (1-5)
 * @param sessionId
 * @param urlId
 * @returns {Promise<Object>}
 */
export async function updateVote({ iri, vote, sessionId = null, urlId }) {
  try {
    if (!iri) {
      throw new Error("Cannot update vote because IRI is missing.")
    }

    let payload = { vote }
    if (sessionId) payload.session = `/api/sessions/${sessionId}`
    if (urlId) payload.url = `/api/access_urls/${urlId}`

    return await baseService.put(iri, payload)
  } catch (error) {
    console.error("Error updating user vote:", error)
    throw error
  }
}

/**
 * Retrieves the user's vote for a specific course.
 *
 * @param {number} userId - ID of the user
 * @param {number} courseId - ID of the course
 * @param sessionId
 * @param urlId
 * @returns {Promise<Object|null>} - Returns the vote object if found, otherwise null
 */
export async function getUserVote({ userId, courseId, sessionId = null, urlId }) {
  try {
    let query = `/api/user_rel_course_votes?user.id=${userId}&course.id=${courseId}`
    if (urlId) query += `&url.id=${urlId}`

    // Remove session.id if null
    if (sessionId) {
      query += `&session.id=${sessionId}`
    }

    const response = await baseService.get(query)

    if (response && response["hydra:member"] && response["hydra:member"].length > 0) {
      return response["hydra:member"][0]
    }

    return null
    // eslint-disable-next-line no-unused-vars
  } catch (error) {
    return null
  }
}

/**
 * Retrieves all votes of a user for different courses.
 *
 * @param {number} userId - User ID
 * @param {number} urlId - Access URL ID
 * @returns {Promise<Array>} - List of user votes
 */
export async function getUserVotes({ userId, urlId }) {
  try {
    let query = `/api/user_rel_course_votes?user.id=${userId}`
    if (urlId) query += `&url.id=${urlId}`

    const response = await baseService.get(query)

    return response && response["hydra:member"] ? response["hydra:member"] : []
  } catch (error) {
    console.error("Error retrieving user votes:", error)
    return []
  }
}
