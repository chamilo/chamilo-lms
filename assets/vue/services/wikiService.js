import baseService from "./baseService";

function cleanParams(params = {}) {
  return Object.fromEntries(
    Object.entries(params).filter(
      ([, value]) =>
        value !== undefined && value !== null && value !== "" && value !== 0,
    ),
  );
}

function buildUrl(path, params = {}) {
  const query = new URLSearchParams(cleanParams(params)).toString();

  return query ? `${path}?${query}` : path;
}

export default {
  async getPage(params = {}) {
    return await baseService.get("/api/wiki/page", cleanParams(params));
  },

  async getForm(params = {}) {
    return await baseService.get("/api/wiki/form", cleanParams(params));
  },

  async getReport(params = {}) {
    return await baseService.get("/api/wiki/report", cleanParams(params));
  },

  async getHistory(pageId, params = {}) {
    return await baseService.get(
      `/api/wiki/page/${pageId}/history`,
      cleanParams(params),
    );
  },

  async getDiscussion(pageId, params = {}) {
    return await baseService.get(
      `/api/wiki/page/${pageId}/discussion`,
      cleanParams(params),
    );
  },

  async addDiscussionComment(pageId, params = {}, payload = {}) {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/discussion`, params),
      payload,
    );
  },

  async createPage(params = {}, payload = {}) {
    return await baseService.post(buildUrl("/api/wiki/page", params), payload);
  },

  async updatePage(pageId, params = {}, payload = {}) {
    return await baseService.put(
      buildUrl(`/api/wiki/page/${pageId}`, params),
      payload,
    );
  },

  async acquireLock(pageId, params = {}, csrfToken = "") {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/lock`, params),
      { csrfToken },
    );
  },

  async releaseLock(pageId, params = {}, csrfToken = "") {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/unlock`, params),
      { csrfToken },
    );
  },

  async restoreVersion(pageId, versionIid, params = {}, csrfToken = "") {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/restore`, params),
      { csrfToken, versionIid },
    );
  },

  async setPageVisibility(pageId, enabled, params = {}, csrfToken = "") {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/visibility`, params),
      { csrfToken, enabled },
    );
  },

  async setPageProtection(pageId, enabled, params = {}, csrfToken = "") {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/protection`, params),
      { csrfToken, enabled },
    );
  },

  async setPageSubscription(pageId, enabled, params = {}, csrfToken = "") {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/subscription`, params),
      { csrfToken, enabled },
    );
  },

  async deletePage(pageId, params = {}, csrfToken = "") {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/delete`, params),
      { csrfToken },
    );
  },

  async setContextAddLock(enabled, params = {}, csrfToken = "") {
    return await baseService.post(
      buildUrl("/api/wiki/context/add-lock", params),
      { csrfToken, enabled },
    );
  },

  async setContextSubscription(enabled, params = {}, csrfToken = "") {
    return await baseService.post(
      buildUrl("/api/wiki/context/subscription", params),
      { csrfToken, enabled },
    );
  },

  async setDiscussionVisibility(pageId, enabled, params = {}, csrfToken = "") {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/discussion/visibility`, params),
      { csrfToken, enabled },
    );
  },

  async setDiscussionCommenting(pageId, enabled, params = {}, csrfToken = "") {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/discussion/commenting`, params),
      { csrfToken, enabled },
    );
  },

  async setDiscussionRating(pageId, enabled, params = {}, csrfToken = "") {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/discussion/rating`, params),
      { csrfToken, enabled },
    );
  },

  async setDiscussionSubscription(
    pageId,
    enabled,
    params = {},
    csrfToken = "",
  ) {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/discussion/subscription`, params),
      { csrfToken, enabled },
    );
  },

  async deleteContext(params = {}, csrfToken = "") {
    return await baseService.post(
      buildUrl("/api/wiki/context/delete", params),
      { csrfToken },
    );
  },
};
