import axios from "axios"
import baseService from "./baseService"

const API_URL = "/social-network"

async function createPost(params) {
  return baseService.post("/api/social_posts", params)
}

/**
 * @param {string} postIri
 * @returns {Promise<Object>}
 */
async function sendPostLike(postIri) {
  return baseService.post(`${postIri}/like`, {}, true)
}

/**
 * @param {string} postIri
 * @returns {Promise<Object>}
 */
async function sendPostDislike(postIri) {
  return baseService.post(`${postIri}/dislike`, {}, true)
}

/**
 * @param {FormData} formData
 * @returns {Promise<Object>}
 */
async function addAttachment(formData) {
  return await baseService.postForm("/api/social_post_attachments", formData)
}

export default {
  async fetchPersonalData(userId) {
    try {
      const response = await axios.get(`${API_URL}/personal-data/${userId}`)

      return response.data.personalData
    } catch (error) {
      console.error("Error fetching personal data:", error)
      throw error
    }
  },

  async fetchTermsAndConditions(userId) {
    try {
      const response = await axios.get(`${API_URL}/terms-and-conditions/${userId}`)

      return response.data.terms
    } catch (error) {
      console.error("Error fetching terms and conditions:", error)
      throw error
    }
  },

  async fetchLegalStatus(userId) {
    try {
      const response = await axios.get(`${API_URL}/legal-status/${userId}`)

      return response.data
    } catch (error) {
      console.error("Error fetching legal status:", error)
      throw error
    }
  },

  async submitPrivacyRequest({ userId, explanation, requestType }) {
    try {
      const response = await axios.post(`${API_URL}/handle-privacy-request`, {
        explanation,
        userId,
        requestType,
      })

      return response.data
    } catch (error) {
      console.error("Error submitting privacy request:", error)
      throw error
    }
  },

  async submitAcceptTerm(userId) {
    try {
      const response = await axios.post(`${API_URL}/send-legal-term`, {
        userId,
      })

      return response.data
    } catch (error) {
      console.error("Error accepting the term:", error)
      throw error
    }
  },

  async revokeAcceptTerm(userId) {
    try {
      const response = await axios.post(`${API_URL}/delete-legal`, {
        userId,
      })

      return response.data
    } catch (error) {
      console.error("Error revoking acceptance:", error)
      throw error
    }
  },

  async checkTermsRestrictions(userId) {
    try {

      return await baseService.get(`${API_URL}/terms-restrictions/${userId}`);
    } catch (error) {
      console.error("Error checking terms restrictions:", error)
      throw error
    }
  },

  async fetchInvitations(userId) {
    try {
      const response = await axios.get(`${API_URL}/invitations/${userId}`)

      return response.data
    } catch (error) {
      console.error("Error fetching invitations:", error)
      throw error
    }
  },

  async acceptInvitation(userId, targetUserId) {
    try {
      const response = await axios.post(`${API_URL}/user-action`, {
        userId,
        targetUserId,
        action: "add_friend",
        is_my_friend: true,
      })

      return response.data
    } catch (error) {
      console.error("Error accepting invitation:", error)
      throw error
    }
  },

  async denyInvitation(userId, targetUserId) {
    try {
      const response = await axios.post(`${API_URL}/user-action`, {
        userId,
        targetUserId,
        action: "deny_friend",
      })

      return response.data
    } catch (error) {
      console.error("Error denying invitation:", error)
      throw error
    }
  },

  async acceptGroupInvitation(userId, groupId) {
    try {
      const response = await axios.post(`${API_URL}/group-action`, {
        userId,
        groupId,
        action: "accept",
      })

      return response.data
    } catch (error) {
      console.error("Error accepting group invitation:", error)
      throw error
    }
  },

  async denyGroupInvitation(userId, groupId) {
    try {
      const response = await axios.post(`${API_URL}/group-action`, {
        userId,
        groupId,
        action: "deny",
      })

      return response.data
    } catch (error) {
      console.error("Error denying group invitation:", error)
      throw error
    }
  },

  async joinGroup(userId, groupId) {
    try {
      const response = await axios.post(`${API_URL}/group-action`, {
        userId,
        groupId,
        action: "join",
      })

      return response.data
    } catch (error) {
      console.error("Error joining the group:", error)
      throw error
    }
  },

  createPost,

  sendPostLike,

  sendPostDislike,

  addAttachment,

  delete: baseService.delete,
}
