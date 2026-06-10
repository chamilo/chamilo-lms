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
  return baseService.post(`${postIri}/like`, {})
}

/**
 * @param {string} postIri
 * @returns {Promise<Object>}
 */
async function sendPostDislike(postIri) {
  return baseService.post(`${postIri}/dislike`, {})
}

/**
 * @param {FormData} formData
 * @returns {Promise<Object>}
 */
async function addAttachment(formData) {
  return await baseService.postForm("/api/social_post_attachments", formData)
}

/**
 * Fetches Open Graph metadata for a given URL to build a link preview.
 * @param {string} url
 * @returns {Promise<Object>}
 */
async function fetchOpenGraphPreview(url) {
  return baseService.post(`${API_URL}/opengraph`, { url })
}

/**
 * Lists social wall posts matching the given query params (collection endpoint).
 * @param {Object} params
 * @returns {Promise<{totalItems: number, items: Object[], nextPageParams: Object|null}>}
 */
async function getPosts(params) {
  return baseService.getCollection("/api/social_posts", params)
}

/**
 * Fetches the attachments of a social post.
 * @param {string} postIri
 * @returns {Promise<Object[]>}
 */
async function getPostAttachments(postIri) {
  return baseService.get(`${postIri}/attachments`)
}

/**
 * Returns the online status map for the given user ids.
 * @param {number[]} userIds
 * @returns {Promise<Object>}
 */
async function getOnlineStatus(userIds) {
  return baseService.post(`${API_URL}/online-status`, { userIds })
}

/**
 * Fetches the social groups a user belongs to.
 * @param {number} userId
 * @returns {Promise<Object>}
 */
async function getGroups(userId) {
  return baseService.get(`${API_URL}/groups/${userId}`)
}

/**
 * Fetches the details of a social group.
 * @param {number|string} groupId
 * @returns {Promise<Object>}
 */
async function getGroupDetails(groupId) {
  return baseService.get(`${API_URL}/group-details/${groupId}`)
}

/**
 * Checks the relation between the current user and a profile user.
 * @param {number} currentUserId
 * @param {number} profileUserId
 * @returns {Promise<Object>}
 */
async function getUserRelation(currentUserId, profileUserId) {
  return baseService.get(`${API_URL}/user-relation/${currentUserId}/${profileUserId}`)
}

/**
 * Fetches the pending invitations count for a user.
 * @param {number} userId
 * @returns {Promise<Object>}
 */
async function getInvitationsCount(userId) {
  return baseService.get(`${API_URL}/invitations/count/${userId}`)
}

/**
 * Fetches the social forum link configuration.
 * @returns {Promise<Object>}
 */
async function getForumLink() {
  return baseService.get(`${API_URL}/get-forum-link`)
}

/**
 * Fetches a social user profile.
 * @param {number} userId
 * @returns {Promise<Object>}
 */
async function getUserProfile(userId) {
  return baseService.get(`${API_URL}/user-profile/${userId}`)
}

/**
 * Performs a group membership action (join, leave, accept, deny, ...).
 * @param {Object|FormData} payload
 * @returns {Promise<Object>}
 */
async function groupAction(payload) {
  return baseService.post(`${API_URL}/group-action`, payload)
}

/**
 * Fetches the friends that can be invited to a group.
 * @param {number} userId
 * @param {number|string} groupId
 * @returns {Promise<Object>}
 */
async function getInviteFriends(userId, groupId) {
  return baseService.get(`${API_URL}/invite-friends/${userId}/${groupId}`)
}

/**
 * Adds users to a group.
 * @param {number|string} groupId
 * @param {number[]} userIds
 * @returns {Promise<Object>}
 */
async function addUsersToGroup(groupId, userIds) {
  return baseService.post(`${API_URL}/add-users-to-group/${groupId}`, { userIds })
}

/**
 * Fetches the users already invited to a group.
 * @param {number|string} groupId
 * @returns {Promise<Object>}
 */
async function getInvitedUsers(groupId) {
  return baseService.get(`${API_URL}/group/${groupId}/invited-users`)
}

/**
 * Fetches the messages of a group discussion thread.
 * @param {number|string} groupId
 * @param {number|string} discussionId
 * @returns {Promise<Object[]>}
 */
async function getGroupDiscussionMessages(groupId, discussionId) {
  return baseService.get(`${API_URL}/group/${groupId}/discussion/${discussionId}/messages`)
}

export default {
  async fetchPersonalData(userId) {
    try {
      const data = await baseService.get(`${API_URL}/personal-data/${userId}`)

      return data.personalData
    } catch (error) {
      console.error("Error fetching personal data:", error)
      throw error
    }
  },

  async fetchTermsAndConditions(userId, { accepted = false } = {}) {
    try {
      const url = accepted
        ? `${API_URL}/terms-and-conditions/${userId}?accepted=1`
        : `${API_URL}/terms-and-conditions/${userId}`

      const data = await baseService.get(url)

      return {
        items: data?.terms ?? [],
        date_text: data?.date_text ?? "",
        version: data?.version ?? null,
        language_id: data?.language_id ?? null,
        showing_accepted: data?.showing_accepted ?? false,
      }
    } catch (error) {
      console.error("Error fetching terms and conditions:", error)
      throw error
    }
  },

  async fetchLegalStatus(userId) {
    try {
      return await baseService.get(`${API_URL}/legal-status/${userId}`)
    } catch (error) {
      console.error("Error fetching legal status:", error)
      throw error
    }
  },

  async submitPrivacyRequest({ userId, explanation, requestType }) {
    try {
      return await baseService.post(`${API_URL}/handle-privacy-request`, {
        explanation,
        userId,
        requestType,
      })
    } catch (error) {
      console.error("Error submitting privacy request:", error)
      throw error
    }
  },

  async submitAcceptTerm(userId) {
    try {
      return await baseService.post(`${API_URL}/send-legal-term`, {
        userId,
      })
    } catch (error) {
      console.error("Error accepting the term:", error)
      throw error
    }
  },

  async revokeAcceptTerm(userId) {
    try {
      return await baseService.post(`${API_URL}/delete-legal`, {
        userId,
      })
    } catch (error) {
      console.error("Error revoking acceptance:", error)
      throw error
    }
  },

  async checkTermsRestrictions(userId) {
    try {
      return await baseService.get(`${API_URL}/terms-restrictions/${userId}`)
    } catch (error) {
      console.error("Error checking terms restrictions:", error)
      throw error
    }
  },

  async fetchInvitations(userId) {
    try {
      return await baseService.get(`${API_URL}/invitations/${userId}`)
    } catch (error) {
      console.error("Error fetching invitations:", error)
      throw error
    }
  },

  async acceptInvitation(userId, targetUserId) {
    try {
      return await baseService.post(`${API_URL}/user-action`, {
        userId,
        targetUserId,
        action: "add_friend",
        is_my_friend: true,
      })
    } catch (error) {
      console.error("Error accepting invitation:", error)
      throw error
    }
  },

  async denyInvitation(userId, targetUserId) {
    try {
      return await baseService.post(`${API_URL}/user-action`, {
        userId,
        targetUserId,
        action: "deny_friend",
      })
    } catch (error) {
      console.error("Error denying invitation:", error)
      throw error
    }
  },

  async acceptGroupInvitation(userId, groupId) {
    try {
      return await baseService.post(`${API_URL}/group-action`, {
        userId,
        groupId,
        action: "accept",
      })
    } catch (error) {
      console.error("Error accepting group invitation:", error)
      throw error
    }
  },

  async denyGroupInvitation(userId, groupId) {
    try {
      return await baseService.post(`${API_URL}/group-action`, {
        userId,
        groupId,
        action: "deny",
      })
    } catch (error) {
      console.error("Error denying group invitation:", error)
      throw error
    }
  },

  async joinGroup(userId, groupId) {
    try {
      return await baseService.post(`${API_URL}/group-action`, {
        userId,
        groupId,
        action: "join",
      })
    } catch (error) {
      console.error("Error joining the group:", error)
      throw error
    }
  },

  createPost,

  sendPostLike,

  sendPostDislike,

  addAttachment,

  fetchOpenGraphPreview,

  getPosts,
  getPostAttachments,
  getOnlineStatus,
  getGroups,
  getGroupDetails,
  getUserRelation,
  getInvitationsCount,
  getForumLink,
  getUserProfile,
  groupAction,
  getInviteFriends,
  addUsersToGroup,
  getInvitedUsers,
  getGroupDiscussionMessages,

  delete: baseService.delete,
}
