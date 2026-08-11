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
  const payload = {
    user: `/api/users/${userId}`,
    vote,
    url: `/api/access_urls/${urlId}`,
  }

  if (courseIri) payload.course = courseIri
  if (sessionId) payload.session = `/api/sessions/${sessionId}`

  return await baseService.post("/api/user_rel_course_votes", payload)
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

    return await baseService.patch(iri, payload)
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
    const searchParams = { "user.id": userId }

    if (urlId) searchParams["url.id"] = urlId

    if (courseId && courseId !== 0) {
      searchParams["course.id"] = courseId
    } else if (sessionId) {
      searchParams["session.id"] = sessionId
      // Kept as a string: axios drops null values, and the API expects the literal.
      searchParams.course = "null"
    }

    const { items } = await baseService.getCollection("/api/user_rel_course_votes", searchParams)

    if (items?.length > 0) {
      return items[0]
    }

    return null
    // eslint-disable-next-line no-unused-vars
  } catch (error) {
    return null
  }
}

/**
 * Adds a course to the user's favourites, or removes it when already there.
 *
 * @param {number} courseId - ID of the course
 * @param {number} userId - ID of the user
 * @returns {Promise<boolean>} - Whether the course is a favourite after the call
 */
export async function toggleFavorite(courseId, userId) {
  // Check if the vote already exists
  const { totalItems, items } = await baseService.getCollection("/api/user_rel_course_votes", {
    "user.id": userId,
    "course.id": courseId,
  })

  if (totalItems > 0) {
    // Already favorite → remove
    await baseService.delete(items[0]["@id"])

    return false
  }

  // Not favorite → create
  await baseService.post("/api/user_rel_course_votes", {
    user: `/api/users/${userId}`,
    course: `/api/courses/${courseId}`,
    vote: 1,
    url: `/api/access_urls/${window.access_url_id ?? 1}`,
  })

  return true
}

/**
 * Lists the courses the user marked as favourite.
 *
 * @param {number} userId - ID of the user
 * @returns {Promise<Array>} - List of courses
 */
export async function listFavoriteCourses(userId) {
  const { items } = await baseService.getCollection("/api/user_rel_course_votes", {
    "user.id": userId,
    vote: 1,
    pagination: false,
  })

  return items.map((vote) => vote.course)
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
