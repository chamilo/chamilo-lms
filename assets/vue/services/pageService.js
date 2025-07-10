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
  const { data } = await api.put(iri, params)
  return data
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
  const { data } = await api.put(iri, params)
  return data
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
  const { data } = await api.put(iri, params)
  return data
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


export default {
  // Pages
  getPublicPageBySlug,
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
