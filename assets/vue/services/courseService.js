import { ENTRYPOINT } from "../config/entrypoint"
import axios from "axios"

const API_URL = '/course';
const courseService = {
  /**
   * @param {number} courseId
   * @param {number=} sessionId
   * @returns {Promise<Object>}
   */
  loadTools: async (courseId, sessionId = 0) => {
    const { data } = await axios.get(ENTRYPOINT + `../course/${courseId}/home.json?sid=${sessionId}`)

    return data
  },

  loadCTools: async (courseId, sessionId = 0) => {
    const { data } = await axios.get(ENTRYPOINT + 'c_tools', {
      params: {
        cid: courseId,
        sid: sessionId,
        order: {
          position: 'asc'
        }
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
    const { data } = await axios.post(
      ENTRYPOINT + `../course/${courseId}/home.json?sid=${sessionId}`,
      {
        index: newIndex,
        toolItem: tool,
      }
    )

    return data
  },

  /**
   * @param {number} courseId
   * @param {number=} sessionId
   * @returns {Promise<{Object}>}
   */
  loadHomeIntro: async (courseId, sessionId = 0) => {
    const { data } = await axios.get(
      ENTRYPOINT + `../course/${courseId}/getToolIntro`,
      {
        params: {
          sid: sessionId,
        },
      }
    )

    return data
  },

  /**
   * @param {number} courseId
   * @param {number=} sessionId
   * @returns {Promise<Object>}
   */
  checkLegal: async (courseId, sessionId = 0) => {
    const { data } = await axios.get(
      `${API_URL}/${courseId}/checkLegal.json`,
      {
        params: {
          sid: sessionId,
        },
      }
    )

    return data
  },

  /**
   * Creates a new course with the provided data.
   * @param {Object} courseData - The data for the course to be created.
   * @returns {Promise<Object>} The server response after creating the course.
   */
  createCourse: async (courseData) => {
    const response = await axios.post(`${API_URL}/create`, courseData);
    console.log('response create ::', response);
    return response.data;
  },

  /**
   * Fetches available categories for courses.
   * @returns {Promise<Array>} A list of available categories.
   */
  getCategories: async () => {
    const response = await axios.get(`${API_URL}/categories`);
    return response.data;
  },

  /**
   * Searches for templates based on a provided search term.
   * @param {string} searchTerm - The search term for the templates.
   * @returns {Promise<Array>} A list of templates matching the search term.
   */
  searchTemplates: async (searchTerm) => {
    const response = await axios.get(`${API_URL}/search_templates`, {
      params: { search: searchTerm }
    });
    return response.data.items.map(item => ({
      name: item.name,
      value: item.id
    }));
  },
}

export default courseService
