import api from "../config/api"
import baseService from "./baseService"

export default {
  find: baseService.get,

  /**
   * @param {Object} searchParams
   * @param {boolean} disablePagination
   * @returns {Promise<{totalItems, items}>}
   */
  listAll: async (searchParams = {}, disablePagination = false) => {
    const params = { ...searchParams }

    if (disablePagination) {
      params.pagination = false
    }

    return await baseService.getCollection("/api/courses", params)
  },

  /**
   * @param {number} cid
   * @param {object} params
   * @returns {Promise<Object>}
   */
  findById: async (cid, params) => baseService.get(`/api/courses/${cid}`, params),

  /**
   * @param {number} courseId
   * @param {number=} sessionId
   * @returns {Promise<Object>}
   */
  loadTools: async (courseId, sessionId = 0) => {
    const { data } = await api.get(`/course/${courseId}/home.json?sid=${sessionId}`)

    return data
  },

  loadCTools: async (courseId, sessionId = 0) => {
    const { data } = await api.get("/api/c_tools", {
      params: {
        cid: courseId,
        sid: sessionId,
        order: {
          position: "asc",
        },
      },
    })

    return data["hydra:member"]
  },

  /**
   * Loads thematic progress information for course home.
   * @param {number} courseId
   * @param {number=} sessionId
   * @returns {Promise<Object>}
   */
  loadThematicProgress: async (courseId, sessionId = 0) => {
    const { data } = await api.get(`/course/${courseId}/thematic_progress.json`, {
      params: { sid: sessionId },
    })

    return data
  },

  /**
   * @param {Object} tool
   * @param {number} newIndex
   * @param {number} courseId
   * @param {number=} sessionId
   * @returns {Promise<Object>}
   */
  updateToolOrder: async (tool, newIndex, courseId, sessionId = 0) => {
    const payload = {
      toolId: tool.iid,
      index: newIndex,
    }

    const { data } = await api.post(`/course/${courseId}/home.json?sid=${sessionId}`, payload)

    return data
  },

  /**
   * @param {number} courseId
   * @param {number=} sessionId
   * @returns {Promise<{Object}>}
   */
  loadHomeIntro: async (courseId, sessionId = 0) => {
    const { data } = await api.get(`/course/${courseId}/getToolIntro`, {
      params: {
        sid: sessionId,
      },
    })

    return data
  },

  /**
   * @param {number} courseId
   * @param {number=} sessionId
   * @returns {Promise<Object>}
   */
  checkLegal: async (courseId, sessionId = 0) => {
    return await baseService.get(`/course/${courseId}/checkLegal.json`, { sid: sessionId })
  },

  /**
   * Creates a new course with the provided data.
   * @param {Object} courseData - The data for the course to be created.
   * @returns {Promise<Object>} The server response after creating the course.
   */
  createCourse: async (courseData) => {
    const response = await api.post(`/course/create`, courseData)
    console.log("response create ::", response)

    return response.data
  },

  /**
   * Fetches available categories for courses.
   * @returns {Promise<Array>} A list of available categories.
   */
  getCategories: async () => {
    const response = await api.get(`/course/categories`)

    return response.data
  },

  /**
   * Searches for templates based on a provided search term.
   * @param {string} searchTerm - The search term for the templates.
   * @returns {Promise<Array>} A list of templates matching the search term.
   */
  searchTemplates: async (searchTerm) => {
    const response = await api.get(`/course/search_templates`, {
      params: { search: searchTerm },
    })

    return response.data.items.map((item) => ({
      name: item.name,
      value: item.id,
    }))
  },

  /**
   * Retrieves the ID of the auto-launchable exercise in a course, if configured.
   *
   * @param {number} courseId - The ID of the course.
   * @param {number=} sessionId - The ID of the session (optional).
   * @returns {Promise<number|null>} The ID of the auto-launchable exercise, or null if none exists.
   */
  getAutoLaunchExerciseId: async (courseId, sessionId = 0) => {
    try {
      const { data } = await api.get(`/course/${courseId}/getAutoLaunchExerciseId`, {
        params: {
          sid: sessionId,
        },
      })

      if (data && data.exerciseId) {
        return data.exerciseId
      }

      return null
    } catch (error) {
      console.error("Error fetching auto-launch exercise ID:", error)
      return null
    }
  },

  /**
   * Retrieves the ID of the auto-launchable learnpaths in a course, if configured.
   *
   * @param {number} courseId - The ID of the course.
   * @param {number=} sessionId - The ID of the session (optional).
   * @returns {Promise<number|null>} The ID of the auto-launchable learnpath, or null if none exists.
   */
  getAutoLaunchLPId: async (courseId, sessionId = 0) => {
    try {
      const { data } = await api.get(`/course/${courseId}/getAutoLaunchLPId`, {
        params: {
          sid: sessionId,
        },
      })

      if (data && data.lpId) {
        return data.lpId
      }

      return null
    } catch (error) {
      console.error("Error fetching auto-launch LP ID:", error)
      return null
    }
  },

  /**
   * Loads public catalogue courses filtered by access_url and usergroup rules.
   * @returns {Promise<{items: Array}>}
   */
  listCatalogueCourses: async () => {
    const response = await api.get("/catalogue/courses-list")
    return response.data
  },

  /**
   *
   * @param {Object} params
   * @returns {Promise<{totalItems: number, items: (*&{userVote: null, extra_fields})[], nextPageParams: {page: number, itemsPerPage: number}|null}>}
   */
  loadCourseCatalogue: async (params = {}) => {
    const { totalItems, items, nextPageParams } = await baseService.getCollection("/api/public_courses", params)

    const mappedItems = items.map((course) => ({
      ...course,
      userVote: null,
      extra_fields: course.extra_fields || {},
    }))

    return { totalItems, items: mappedItems, nextPageParams }
  },

  fetchDashboardCourses: async () => {
    const { data } = await api.get("/admin/sessionadmin/courses")
    console.log("[courseService] dashboard payload →", data)

    return data
  },

  toggleFavorite: async (courseId, userId) => {
    // Check if the vote already exists
    const { data } = await api.get("/api/user_rel_course_votes", {
      params: { "user.id": userId, "course.id": courseId },
    })

    if (data["hydra:totalItems"] > 0) {
      // Already favorite → remove
      await api.delete(data["hydra:member"][0]["@id"])

      return false
    }

    // Not favorite → create
    await api.post("/api/user_rel_course_votes", {
      user: `/api/users/${userId}`,
      course: `/api/courses/${courseId}`,
      vote: 1,
      url: `/api/access_urls/${window.access_url_id ?? 1}`,
    })

    return true
  },

  listFavoriteCourses: async (userId) => {
    const { data } = await api.get("/api/user_rel_course_votes", {
      params: { "user.id": userId, vote: 1, pagination: false },
    })

    return data["hydra:member"].map((vote) => vote.course)
  },

  getCompletedCourses: async (offset = 0, limit = 10) => {
    const res = await api.get("/admin/sessionadmin/courses/completed", {
      params: { offset, limit },
    })

    return res.data
  },

  getIncompleteCourses: async () => {
    const res = await api.get("/admin/sessionadmin/courses/incomplete")

    return res.data
  },

  getRestartableCourses: async (offset = 0, limit = 10) => {
    const { data } = await api.get("/admin/sessionadmin/courses/restartable", {
      params: { offset, limit },
    })

    return data
  },

  extendSessionByWeek: async (sessionId, userId, courseId) => {
    const { data } = await api.post("/admin/sessionadmin/courses/extend_week", {
      sessionId,
      userId,
      courseId,
    })

    return data
  },

  findCourseForSessionAdmin: async (cid) => {
    const response = await fetch(`/admin/sessionadmin/courses/${cid}`)
    if (!response.ok) {
      throw new Error("Failed to fetch course")
    }

    const data = await response.json()

    if (!data || typeof data !== "object" || !data.id) {
      throw new Error("Failed to load course for session admin")
    }

    return data
  },

  getNextCourse: async (courseId, sessionId = 0, dependents = false) => {
    const { data } = await api.get(`/course/${courseId}/next-course`, {
      params: {
        sid: sessionId,
        dependents: dependents ? 1 : 0,
      },
    })
    return data
  },
}
