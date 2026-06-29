import baseService from "./baseService"

function cleanParams(params = {}) {
  const query = {}

  for (const [key, value] of Object.entries(params)) {
    if (value !== undefined && value !== null && String(value) !== "") {
      query[key] = value
    }
  }

  return query
}

function buildForumFormData(payload = {}) {
  const formData = new FormData()
  const attachments = Array.isArray(payload.attachments) ? payload.attachments : []

  for (const [key, value] of Object.entries(payload)) {
    if (["attachments", "image"].includes(key)) {
      continue
    }

    if (value !== undefined && value !== null) {
      formData.append(key, value)
    }
  }

  attachments.forEach((file) => {
    formData.append("attachments[]", file)
  })

  if (hasForumImage(payload)) {
    formData.append("image", payload.image)
  }

  return formData
}

function hasAttachments(payload = {}) {
  return Array.isArray(payload.attachments) && payload.attachments.length > 0
}

function hasForumImage(payload = {}) {
  const image = payload.image

  return (
    (typeof File !== "undefined" && image instanceof File) ||
    (typeof Blob !== "undefined" && image instanceof Blob)
  )
}

export default {
  async searchForums(params = {}) {
    return await baseService.get("/api/forum/search", cleanParams(params))
  },
  async getCategories(params) {
    const { items } = await baseService.getCollection("/api/forum_categories", cleanParams(params))

    return items || []
  },

  async createCategory(params, payload) {
    return await baseService.post("/api/forum_categories/create", payload, {}, { params: cleanParams(params) })
  },

  async updateCategory(categoryId, params, payload) {
    return await baseService.put(`/api/forum_categories/${categoryId}/update`, payload, { params: cleanParams(params) })
  },

  async deleteCategory(categoryId, params, payload) {
    return await baseService.delete(`/api/forum_categories/${categoryId}`, { params: cleanParams(params), data: payload })
  },

  async toggleCategoryLock(categoryId, params, payload) {
    return await baseService.put(`/api/forum_categories/${categoryId}/toggle-lock`, payload, { params: cleanParams(params) })
  },

  async toggleCategoryVisibility(categoryId, params, payload) {
    return await baseService.put(`/api/forum_categories/${categoryId}/toggle-visibility`, payload, { params: cleanParams(params) })
  },

  async moveCategory(categoryId, params, payload) {
    return await baseService.put(`/api/forum_categories/${categoryId}/move`, payload, { params: cleanParams(params) })
  },

  async getForums(params) {
    const { items } = await baseService.getCollection("/api/forums", cleanParams(params))

    return items || []
  },

  async getForum(forumId, params = {}) {
    return await baseService.get(`/api/forums/${forumId}`, cleanParams(params))
  },

  async createForum(params, payload) {
    return await baseService.post("/api/forums/create", payload, {}, { params: cleanParams(params) })
  },

  async updateForum(forumId, params, payload) {
    return await baseService.put(`/api/forums/${forumId}/update`, payload, { params: cleanParams(params) })
  },

  async deleteForum(forumId, params, payload) {
    return await baseService.delete(`/api/forums/${forumId}`, { params: cleanParams(params), data: payload })
  },

  async toggleForumLock(forumId, params, payload) {
    return await baseService.put(`/api/forums/${forumId}/toggle-lock`, payload, { params: cleanParams(params) })
  },

  async toggleForumVisibility(forumId, params, payload) {
    return await baseService.put(`/api/forums/${forumId}/toggle-visibility`, payload, { params: cleanParams(params) })
  },

  async moveForum(forumId, params, payload) {
    return await baseService.put(`/api/forums/${forumId}/move`, payload, { params: cleanParams(params) })
  },

  async toggleForumSubscription(forumId, params, payload) {
    return await baseService.put(`/api/forums/${forumId}/toggle-subscription`, payload, { params: cleanParams(params) })
  },

  async uploadForumImage(forumId, params, payload) {
    const body = hasForumImage(payload) ? buildForumFormData(payload) : payload

    return await baseService.post(`/api/forums/${forumId}/image`, body, {}, { params: cleanParams(params) })
  },

  async getThreads(forumId, params) {
    const { items } = await baseService.getCollection(
      "/api/forum_threads",
      cleanParams({ ...params, forum: `/api/forums/${forumId}` }),
    )

    return items || []
  },

  async getThread(threadId, params = {}) {
    return await baseService.get(`/api/forum_threads/${threadId}`, cleanParams(params))
  },

  async getPosts(params) {
    const { items } = await baseService.getCollection("/api/forum_posts", cleanParams(params))

    return items || []
  },

  async getThreadPosts(threadId, forumId, params = {}) {
    return await baseService.get(`/api/forum_threads/${threadId}/posts`, cleanParams({ ...params, forumId }))
  },

  async getActionToken() {
    return await baseService.get("/api/forum/action-token")
  },

  async getGradingOptions(params = {}) {
    return await baseService.get("/api/forum/grading-options", cleanParams(params))
  },

  async getThreadGrading(threadId, params = {}) {
    return await baseService.get(`/api/forum_threads/${threadId}/grading`, cleanParams(params))
  },

  async updateThreadGrading(threadId, params = {}, payload = {}) {
    return await baseService.put(`/api/forum_threads/${threadId}/grading`, payload, { params: cleanParams(params) })
  },

  async saveThreadScore(threadId, params = {}, payload = {}) {
    return await baseService.put(`/api/forum_threads/${threadId}/grading/score`, payload, { params: cleanParams(params) })
  },

  async updateThread(threadId, params, payload) {
    return await baseService.put(`/api/forum_threads/${threadId}/update`, payload, { params: cleanParams(params) })
  },

  async toggleThreadLock(threadId, params, payload) {
    return await baseService.put(`/api/forum_threads/${threadId}/toggle-lock`, payload, { params: cleanParams(params) })
  },

  async toggleThreadSticky(threadId, params, payload) {
    return await baseService.put(`/api/forum_threads/${threadId}/toggle-sticky`, payload, { params: cleanParams(params) })
  },

  async toggleThreadVisibility(threadId, params, payload) {
    return await baseService.put(`/api/forum_threads/${threadId}/toggle-visibility`, payload, { params: cleanParams(params) })
  },

  async moveThread(threadId, params, payload) {
    return await baseService.put(`/api/forum_threads/${threadId}/move`, payload, { params: cleanParams(params) })
  },

  async toggleThreadSubscription(threadId, params, payload) {
    return await baseService.put(`/api/forum_threads/${threadId}/toggle-subscription`, payload, { params: cleanParams(params) })
  },

  async deleteThread(threadId, params, payload) {
    return await baseService.delete(`/api/forum_threads/${threadId}`, { params: cleanParams(params), data: payload })
  },

  async updatePost(postId, params, payload) {
    return await baseService.put(`/api/forum_posts/${postId}/update`, payload, { params: cleanParams(params) })
  },

  async togglePostVisibility(postId, params, payload) {
    return await baseService.put(`/api/forum_posts/${postId}/toggle-visibility`, payload, { params: cleanParams(params) })
  },

  async approvePost(postId, params, payload) {
    return await baseService.put(`/api/forum_posts/${postId}/approve`, payload, { params: cleanParams(params) })
  },

  async rejectPost(postId, params, payload) {
    return await baseService.put(`/api/forum_posts/${postId}/reject`, payload, { params: cleanParams(params) })
  },

  async deletePost(postId, params, payload) {
    return await baseService.delete(`/api/forum_posts/${postId}`, { params: cleanParams(params), data: payload })
  },

  async movePost(postId, params, payload) {
    return await baseService.put(`/api/forum_posts/${postId}/move`, payload, { params: cleanParams(params) })
  },

  async askPostRevision(postId, params, payload) {
    return await baseService.put(`/api/forum_posts/${postId}/ask-revision`, payload, { params: cleanParams(params) })
  },

  async reportPost(postId, params, payload) {
    return await baseService.put(`/api/forum_posts/${postId}/report`, payload, { params: cleanParams(params) })
  },

  async deleteAttachment(attachmentId, params, payload) {
    return await baseService.delete(`/api/forum_attachments/${attachmentId}`, { params: cleanParams(params), data: payload })
  },

  async createThread(params, payload) {
    const body = hasAttachments(payload) ? buildForumFormData(payload) : payload

    return await baseService.post("/api/forum_threads/create", body, {}, { params: cleanParams(params) })
  },

  async createReply(params, payload) {
    const body = hasAttachments(payload) ? buildForumFormData(payload) : payload

    return await baseService.post("/api/forum_posts/reply", body, {}, { params: cleanParams(params) })
  },
}
