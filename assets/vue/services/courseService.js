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
}

export default courseService
