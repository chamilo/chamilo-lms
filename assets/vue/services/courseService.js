import { ENTRYPOINT } from "../config/entrypoint"
import axios from "axios"

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
      `/course/${courseId}/checkLegal.json`,
      {
        params: {
          sid: sessionId,
        },
      }
    )

    return data
  },
}

export default courseService
