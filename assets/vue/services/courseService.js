import api from "../config/api"
import baseService from "./baseService"

export default {
  find: baseService.get,

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
   * @param {Object} tool
   * @param {number} newIndex
   * @param {number} courseId
   * @param {number=} sessionId
   * @returns {Promise<Object>}
   */
  updateToolOrder: async (tool, newIndex, courseId, sessionId = 0) => {
    const { data } = await api.post(`/course/${courseId}/home.json?sid=${sessionId}`, {
      index: newIndex,
      toolItem: tool,
    })

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
    const { data } = await api.get(`/course/${courseId}/checkLegal.json`, {
      params: {
        sid: sessionId,
      },
    })

    return data
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
   * Fetches course details by course ID.
   *
   * @param {number} courseId - The ID of the course.
   * @returns {Promise<Object|null>} - The course details or null if an error occurs.
   */
  getCourseDetails: async (courseId) => {
    try {
      const response = await api.get(`/api/courses/${courseId}`)
      return response.data
    } catch (error) {
      console.error("Error fetching course details:", error)
      return null
    }
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
      });

      if (data && data.exerciseId) {
        return data.exerciseId;
      }

      return null;
    } catch (error) {
      console.error("Error fetching auto-launch exercise ID:", error);
      return null;
    }
  },
}
