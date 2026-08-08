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

  async getCategories(params = {}) {
    return await baseService.get("/api/wiki/categories", cleanParams(params));
  },

  async getSettings(params = {}) {
    return await baseService.get("/api/wiki/settings", cleanParams(params));
  },

  async updateSettings(params = {}, payload = {}) {
    return await baseService.post(
      buildUrl("/api/wiki/settings", params),
      payload,
    );
  },

  async createCategory(params = {}, payload = {}) {
    return await baseService.post(
      buildUrl("/api/wiki/categories", params),
      payload,
    );
  },

  async updateCategory(categoryId, params = {}, payload = {}) {
    return await baseService.patch(
      buildUrl(`/api/wiki/categories/${categoryId}`, params),
      payload,
    );
  },

  async deleteCategory(categoryId, params = {}) {
    return await baseService.post(
      buildUrl(`/api/wiki/categories/${categoryId}/delete`, params),
      {},
    );
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
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}`, params),
      payload,
    );
  },

  async acquireLock(pageId, params = {}) {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/lock`, params),
      {},
    );
  },

  async releaseLock(pageId, params = {}) {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/unlock`, params),
      {},
    );
  },

  async restoreVersion(pageId, versionIid, params = {}) {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/restore`, params),
      { versionIid },
    );
  },

  async setPageVisibility(pageId, enabled, params = {}) {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/visibility`, params),
      { enabled },
    );
  },

  async setPageProtection(pageId, enabled, params = {}) {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/protection`, params),
      { enabled },
    );
  },

  async setPageSubscription(pageId, enabled, params = {}) {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/subscription`, params),
      { enabled },
    );
  },

  async deletePage(pageId, params = {}) {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/delete`, params),
      {},
    );
  },

  async setContextAddLock(enabled, params = {}) {
    return await baseService.post(
      buildUrl("/api/wiki/context/add-lock", params),
      { enabled },
    );
  },

  async setContextSubscription(enabled, params = {}) {
    return await baseService.post(
      buildUrl("/api/wiki/context/subscription", params),
      { enabled },
    );
  },

  async setDiscussionVisibility(pageId, enabled, params = {}) {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/discussion/visibility`, params),
      { enabled },
    );
  },

  async setDiscussionCommenting(pageId, enabled, params = {}) {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/discussion/commenting`, params),
      { enabled },
    );
  },

  async setDiscussionRating(pageId, enabled, params = {}) {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/discussion/rating`, params),
      { enabled },
    );
  },

  async setDiscussionSubscription(pageId, enabled, params = {}) {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/discussion/subscription`, params),
      { enabled },
    );
  },

  async downloadPagePdf(pageId, params = {}) {
    return await baseService.getRaw(
      buildUrl(`/api/wiki/page/${pageId}/export.pdf`, params),
      { responseType: "blob" },
    );
  },

  async exportPageToDocuments(pageId, params = {}) {
    return await baseService.post(
      buildUrl(`/api/wiki/page/${pageId}/export/document`, params),
      {},
    );
  },

  async deleteContext(params = {}) {
    return await baseService.post(
      buildUrl("/api/wiki/context/delete", params),
      {},
    );
  },
};
