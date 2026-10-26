import api from "../config/api"
import baseService from "./baseService"

/**
 * ---------------------------
 * Pages Service
 * ---------------------------
 */

/**
 * Get a public page by slug.
 *
 * @param {string} slug
 * @returns {Promise<Object|null>}
 */
async function getPublicPageBySlug(slug) {
  const { items } = await baseService.getCollection("/api/pages", {
    slug,
    "category.title": "public",
  })

  if (items.length) {
    return items[0]
  }

  return null
}

/**
 * Create a new page.
 *
 * @param {Object} params
 * @returns {Promise<Object>}
 */
async function postPage(params) {
  const { data } = await api.post("/api/pages", params)
  return data
}

/**
 * Update a page.
 *
 * @param {string} iri
 * @param {Object} params
 * @returns {Promise<Object>}
 */
async function updatePage(iri, params) {
  return await baseService.patch(iri, params)
}

/**
 * Delete a page.
 *
 * @param {string} iri
 * @returns {Promise<void>}
 */
async function deletePage(iri) {
  await api.delete(iri)
}

/**
 * Load CMS pages in the same category, across languages, for explicit selection.
 * The collection is already scoped to the current access URL by PageExtension.
 */
async function getPagesForLanguageDeletion(category) {
  const pages = []
  let page = 1
  const limit = 500

  while (true) {
    const result = await baseService.getCollection("/api/pages", { category, page, itemsPerPage: 100 })
    pages.push(...(result.items || []))

    if (pages.length > limit) {
      throw new Error("Too many pages in this category to review safely. Narrow the category first.")
    }
    if (!result.nextPageParams) {
      if (Number(result.totalItems || 0) > pages.length) {
        throw new Error("Not all pages could be loaded. No pages were deleted.")
      }
      return pages
    }
    const nextPage = Number(result.nextPageParams.page)
    if (!Number.isInteger(nextPage) || nextPage <= page) {
      throw new Error("Page pagination failed. No pages were deleted.")
    }
    page = nextPage
  }
}

/** Delete exactly the page IDs selected by the administrator, atomically. */
async function deleteLanguagePages(sourcePageId, pageIds) {
  return await baseService.post(`/api/pages/${sourcePageId}/delete-languages`, { pageIds })
}

/** Delete explicitly selected CMS pages, including across categories. */
async function deleteBulkPages(pageIds) {
  return await baseService.post("/api/pages/actions/bulk-delete", { pageIds })
}

/**
 * ---------------------------
 * Page Layouts Service
 * ---------------------------
 */

/**
 * Get all page layouts.
 *
 * @returns {Promise<Array>}
 */
async function getPageLayouts() {
  const { data } = await api.get("/api/page_layouts")
  return data["hydra:member"]
}

/**
 * Get a page layout by ID.
 *
 * @param {number} id
 * @returns {Promise<Object>}
 */
async function getPageLayout(id) {
  const { data } = await api.get(`/api/page_layouts/${id}`)
  return data
}

/**
 * Create a new page layout.
 *
 * @param {Object} params
 * @returns {Promise<Object>}
 */
async function createPageLayout(params) {
  const { data } = await api.post("/api/page_layouts", params)
  return data
}

/**
 * Update a page layout.
 *
 * @param {string} iri
 * @param {Object} params
 * @returns {Promise<Object>}
 */
async function updatePageLayout(iri, params) {
  return await baseService.patch(iri, params)
}

/**
 * Delete a page layout.
 *
 * @param {string} iri
 * @returns {Promise<void>}
 */
async function deletePageLayout(iri) {
  await api.delete(iri)
}

/**
 * ---------------------------
 * Page Layout Templates Service
 * ---------------------------
 */

async function getPageLayoutTemplates() {
  const { data } = await api.get("/api/page_layout_templates")
  return data["hydra:member"]
}

async function createPageLayoutTemplate(params) {
  const { data } = await api.post("/api/page_layout_templates", params)
  return data
}

async function updatePageLayoutTemplate(iri, params) {
  return await baseService.patch(iri, params)
}

async function deletePageLayoutTemplate(iri) {
  await api.delete(iri)
}

/**
 * Get a single page layout template by ID.
 *
 * @param {number} id
 * @returns {Promise<Object>}
 */
async function getPageLayoutTemplate(id) {
  const { data } = await api.get(`/api/page_layout_templates/${id}`)
  return data
}

/**
 * Fetches the topbar visibility flags for non-logged-in users.
 *
 * @param {string} locale
 * @returns {Promise<Object>}
 */
async function getTopbarVisibility(locale) {
  const response = await baseService.getRaw("/pages/_topbar-visibility", {
    params: { locale },
    headers: { "Cache-Control": "no-cache" },
  })

  return response.data
}

export default {
  getTopbarVisibility,
  // Pages
  getPublicPageBySlug,
  getPagesForLanguageDeletion,
  deleteLanguagePages,
  deleteBulkPages,
  postPage,
  updatePage,
  deletePage,

  // Page Layouts
  getPageLayouts,
  getPageLayout,
  createPageLayout,
  updatePageLayout,
  deletePageLayout,

  // Page Layout Templates
  getPageLayoutTemplates,
  getPageLayoutTemplate,
  createPageLayoutTemplate,
  updatePageLayoutTemplate,
  deletePageLayoutTemplate,
}
