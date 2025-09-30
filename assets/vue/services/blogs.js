import axios from "axios"
import { ENTRYPOINT } from "../config/entrypoint"

function withCourseParams(params = {}) {
  const merged = { ...params }
  const search = new URLSearchParams(window.location.search)
  for (const k of ["cid", "sid", "gid", "gradebook", "origin"]) {
    if (search.has(k) && merged[k] === undefined) merged[k] = search.get(k)
  }
  return merged
}

/** Extract course context (cid/sid/gid) as backend expects */
function courseContextParams() {
  const qs = new URLSearchParams(window.location.search)
  const pickNum = (...names) => {
    for (const n of names) {
      const v = qs.get(n)
      if (v !== null && v !== undefined && !Number.isNaN(Number(v))) return Number(v)
    }
    return null
  }
  const cid = pickNum("cid", "cidReq") ?? Number(window?.chamilo?.course?.id) ?? null
  const sid = pickNum("sid", "id_session") ?? Number(window?.chamilo?.session?.id) ?? null
  const gid = pickNum("gid", "gidReq") ?? Number(window?.chamilo?.group?.id) ?? null
  const out = {}
  if (cid) out.cid = cid
  if (sid) out.sid = sid
  if (gid) out.gid = gid
  return out
}

/** Extract a numeric or string ID from an API Platform item */
function extractId(item) {
  if (!item) return null
  if (item.iid) return item.iid
  if (item.id) return item.id
  const iri = item["@id"]
  if (typeof iri === "string") {
    const segs = iri.split("/")
    const last = segs.pop() || segs.pop()
    return Number(last) || last
  }
  return null
}

/** Map hydra members to rows with a mapper and return {rows,total} */
function hydraMembers(resp, mapper) {
  const data = resp?.data ?? {}
  const arr = (data["hydra:member"] || []).map(mapper)
  const total = data["hydra:totalItems"] ?? arr.length
  return { rows: arr, total }
}

/** Normalize CBlog into project row for UI grids */
function mapBlogToProject(item) {
  return {
    id: extractId(item),
    title: item.title,
    subtitle: item.blogSubtitle ?? "",
    createdAt: item.dateCreation ?? item.createdAt ?? null,
    visible: typeof item.visible === "boolean" ? item.visible : true,
    owner: item.owner ?? null,
  }
}

/** Build ApiPlatform order params from "field:dir" string */
function buildOrderParams(order) {
  const params = {}
  if (!order) return params
  const [field, dir] = order.split(":")
  if (field && dir) params[`order[${field}]`] = dir
  return params
}

/** Resolve parentResourceNodeId from URL or globals */
function resolveParentResourceNode() {
  const m = window.location.pathname.match(/\/resources\/[^/]+\/(\d+)(?:\/|$)/)
  if (m && m[1]) {
    const v = Number(m[1])
    if (!Number.isNaN(v)) return v
  }
  const qs = new URLSearchParams(window.location.search)
  const qv = Number(qs.get("parentResourceNode"))
  if (!Number.isNaN(qv) && qv > 0) return qv
  const gv = Number(window?.chamilo?.course?.resourceNodeId)
  if (!Number.isNaN(gv) && gv > 0) return gv
  return null
}

/** Returns relative IRI like "/api/c_blogs/1" */
function iri(resource, id) {
  const basePath = new URL(ENTRYPOINT, window.location.origin).pathname.replace(/\/$/, "")
  const res = String(resource).replace(/^\//, "")
  return `${basePath}/${res}/${id}`
}

/** Best-effort display name for user objects */
function displayName(u) {
  const fn = u?.firstname || ""
  const ln = u?.lastname || ""
  const username = u?.username || ""
  const full = `${fn} ${ln}`.trim()
  return full || username || `User #${u?.id ?? "?"}`
}

/** Try to read an avatar URL from common fields */
function avatarFromUser(u) {
  return u?.avatarUrl || u?.pictureUrl || u?.picture?.url || u?.avatar?.url || null
}

/** Ensure { id, name, avatar } for any user reference */
async function ensureUserInfo(userRef) {
  if (userRef && typeof userRef === "object" && (userRef.firstname || userRef.lastname || userRef.username)) {
    const id = extractId(userRef)
    return { id, name: displayName(userRef), avatar: avatarFromUser(userRef) }
  }
  if (typeof userRef === "string") {
    const id = extractId({ "@id": userRef })
    return fetchUserBasic(id)
  }
  if (typeof userRef === "number") {
    return fetchUserBasic(userRef)
  }
  return { id: null, name: "User", avatar: null }
}

/** Fetch minimal user info from /users/{id} */
async function fetchUserBasic(id) {
  try {
    if (!id) return { id: null, name: "User", avatar: null }
    const resp = await axios.get(`${ENTRYPOINT}users/${id}`, { params: withCourseParams() })
    const u = resp?.data || {}
    return { id: extractId(u) || id, name: displayName(u), avatar: avatarFromUser(u) }
  } catch {
    return { id, name: `User #${id}`, avatar: null }
  }
}

/** Resolve the CBlog id from the URL. Supports:
 *  /resources/blog/{courseNodeId}/{blogId}/...
 *  /resources/blog/{blogId}/...
 *  ?blogId=...
 */
function resolveBlogIdFromPath() {
  const path = window.location.pathname
  let m = path.match(/\/resources\/blog\/(\d+)\/(\d+)(?:\/|$)/)
  if (m && m[2]) {
    const v = Number(m[2])
    if (!Number.isNaN(v) && v > 0) return v
  }
  m = path.match(/\/resources\/blog\/(\d+)(?:\/|$)/)
  if (m && m[1]) {
    const v = Number(m[1])
    if (!Number.isNaN(v) && v > 0) return v
  }
  const qs = new URLSearchParams(window.location.search)
  const qv = Number(qs.get("blogId"))
  if (!Number.isNaN(qv) && qv > 0) return qv
  return null
}

/* =========================
   Projects (CBlog)
   ========================= */

/** GET /c_blogs */
async function listProjects({ q = "", order = "dateCreation:desc" } = {}) {
  const params = withCourseParams({
    ...(q ? { title: q } : {}),
    ...buildOrderParams(order),
  })
  const resp = await axios.get(`${ENTRYPOINT}c_blogs`, { params })
  return hydraMembers(resp, mapBlogToProject)
}

/** POST /c_blogs */
async function createProject({
  title,
  subtitle,
  parentResourceNode = undefined,
  resourceLinkList = undefined,
  showOnHomepage = false,
}) {
  const payload = { title, blogSubtitle: subtitle }
  if (parentResourceNode !== undefined && parentResourceNode !== null) {
    payload.parentResourceNodeId = parentResourceNode
  }
  if (Array.isArray(resourceLinkList) && resourceLinkList.length) {
    payload.resourceLinkList = resourceLinkList
  }
  if (showOnHomepage) payload.showOnHomepage = true

  const resp = await axios.post(`${ENTRYPOINT}c_blogs`, payload, {
    headers: { "Content-Type": "application/json" },
    params: withCourseParams(),
  })
  return { id: extractId(resp?.data) }
}

/** PATCH /c_blogs/{id} */
async function renameProject(id, newTitle) {
  await axios.patch(
    `${ENTRYPOINT}c_blogs/${id}`,
    { title: newTitle },
    {
      headers: { "Content-Type": "application/merge-patch+json" },
      params: withCourseParams(),
    },
  )
  return { ok: true }
}

/** PUT /c_blogs/{id}/toggle_visibility */
async function toggleProjectVisibility(id) {
  const params = withCourseParams({
    parentResourceNodeId: resolveParentResourceNode(),
  })
  await axios.put(`${ENTRYPOINT}c_blogs/${id}/toggle_visibility`, null, { params })
  return { ok: true }
}

/** DELETE /c_blogs/{id} */
async function deleteProject(id) {
  await axios.delete(`${ENTRYPOINT}c_blogs/${id}`, {
    params: withCourseParams(),
  })
  return { ok: true }
}

/** GET /c_blogs/{id} */
async function getProject(id) {
  const resp = await axios.get(`${ENTRYPOINT}c_blogs/${id}`, {
    params: withCourseParams(),
  })
  const raw = resp?.data || {}
  const mapped = mapBlogToProject(raw)
  return { ...mapped, subtitle: raw.blogSubtitle ?? mapped.subtitle ?? "" }
}

/* =========================
   Posts (CBlogPost) + Ratings (CBlogRating)
   ========================= */

/** Strip HTML and build a safe excerpt */
function stripHtml(html) {
  if (!html) return ""
  const tmp = document.createElement("div")
  tmp.innerHTML = html
  return (tmp.textContent || tmp.innerText || "").trim()
}
function makeExcerpt(html, max = 160) {
  const text = stripHtml(html).replace(/\s+/g, " ").trim()
  return text.length > max ? text.slice(0, max) + "…" : text
}

/** Map a post list row */
function mapPostRow(item) {
  const id = extractId(item)
  const authorName =
    item.author?.username || [item.author?.firstname, item.author?.lastname].filter(Boolean).join(" ") || "Author"
  const date = item.dateCreation ?? item.createdAt ?? item.updatedAt ?? null
  const fullText = item.fullText ?? ""
  const embeddedAttachCount = Array.isArray(item.attachments) ? item.attachments.length : undefined
  return {
    id,
    title: item.title ?? "",
    author: authorName,
    date: date ? new Date(date).toISOString().slice(0, 10) : "",
    excerpt: makeExcerpt(fullText, 160),
    tags: [],
    comments: 0,
    ...(typeof embeddedAttachCount === "number" ? { attachmentsCount: embeddedAttachCount } : {}),
  }
}

/** Map a post detail row */
function mapPostDetail(item) {
  const authorInfo = item.authorInfo || {}
  const fallbackName =
    item.author?.username || [item.author?.firstname, item.author?.lastname].filter(Boolean).join(" ") || "Author"

  return {
    id: extractId(item),
    title: item.title ?? "",
    author: authorInfo.name || fallbackName,
    authorId: authorInfo.id ?? item.author?.id ?? null,
    authorInfo: {
      id: authorInfo.id ?? item.author?.id ?? null,
      name: authorInfo.name || fallbackName,
    },
    date: item.dateCreation ?? null,
    fullText: item.fullText ?? "",
    attachments: (item.attachments || []).map((a) => ({
      id: extractId(a),
      name: a.filename,
      size: a.size,
      path: a.path,
      comment: a.comment || null,
    })),
    rating: { average: 0, count: 0 },
  }
}

/** Map a comment row */
function mapCommentRow(item) {
  const id = extractId(item)
  const authorInfo = item.authorInfo || {}
  const fallbackName =
    item.author?.username || [item.author?.firstname, item.author?.lastname].filter(Boolean).join(" ") || "Author"
  const when = item.dateCreation ?? item.createdAt ?? item.updatedAt ?? null
  const text = item.text ?? item.content ?? item.comment ?? ""
  return {
    id,
    author: authorInfo.name || fallbackName,
    authorId: authorInfo.id ?? item.author?.id ?? null,
    authorInfo: {
      id: authorInfo.id ?? item.author?.id ?? null,
      name: authorInfo.name || fallbackName,
    },
    text,
    date: when ? new Date(when).toISOString().slice(0, 16).replace("T", " ") : "",
  }
}

/** GET /c_blog_posts */
async function listPostsApi({ blogId, page = 1, pageSize = 10, q = "", order = "dateCreation:desc" } = {}) {
  const params = withCourseParams({
    blog: iri("c_blogs", blogId),
    ...(q ? { title: q } : {}),
    ...buildOrderParams(order),
    page,
    itemsPerPage: pageSize,
  })
  const resp = await axios.get(`${ENTRYPOINT}c_blog_posts`, { params })
  return hydraMembers(resp, mapPostRow)
}

/** POST /c_blog_posts */
async function createPostApi({ blogId, title, fullText }) {
  const payload = {
    title,
    fullText,
    blog: iri("c_blogs", blogId),
  }
  const resp = await axios.post(`${ENTRYPOINT}c_blog_posts`, payload, {
    headers: { "Content-Type": "application/json" },
    params: withCourseParams(),
  })
  return { id: extractId(resp?.data) }
}

/** GET /c_blog_posts/{postId} */
async function getPostApi(postId) {
  const resp = await axios.get(`${ENTRYPOINT}c_blog_posts/${postId}`, {
    params: withCourseParams(),
  })
  return mapPostDetail(resp.data)
}

async function updatePost(postId, payload) {
  return axios.patch(`${ENTRYPOINT}c_blog_posts/${postId}`, payload, {
    params: withCourseParams(),
    headers: { "Content-Type": "application/merge-patch+json" },
  })
}

async function deletePost(postId) {
  return axios.delete(`${ENTRYPOINT}c_blog_posts/${postId}`, { params: withCourseParams() })
}

async function updateComment(commentId, payload) {
  return axios.patch(`${ENTRYPOINT}c_blog_comments/${commentId}`, payload, {
    params: withCourseParams(),
    headers: { "Content-Type": "application/merge-patch+json" },
  })
}

async function deleteComment(commentId) {
  return axios.delete(`${ENTRYPOINT}c_blog_comments/${commentId}`, { params: withCourseParams() })
}

/* === Attachments (CBlogAttachment) === */
async function listPostAttachmentsApi(postId) {
  try {
    const params = withCourseParams({ post: iri("c_blog_posts", postId) })
    const resp = await axios.get(`${ENTRYPOINT}c_blog_attachments`, { params })
    const data = resp?.data ?? {}
    const arr = data["hydra:member"] || []
    return arr.map((a) => ({
      id: extractId(a),
      name: a.filename,
      size: a.size,
      path: a.path,
      comment: a.comment || null,
    }))
  } catch (e) {
    if (e?.response?.status === 404) return []
    throw e
  }
}

/* === Ratings (CBlogRating) === */
async function ratePostApi(blogId, postId, score) {
  const payload = {
    blog: iri("c_blogs", blogId),
    post: iri("c_blog_posts", postId),
    rating: Number(score),
    ratingType: "post",
  }
  await axios.post(`${ENTRYPOINT}c_blog_ratings`, payload, {
    headers: { "Content-Type": "application/json" },
    params: withCourseParams(),
  })
  return { ok: true }
}

async function getPostRatingApi(blogId, postId) {
  const params = withCourseParams({
    blog: iri("c_blogs", blogId),
    post: iri("c_blog_posts", postId),
  })
  const resp = await axios.get(`${ENTRYPOINT}c_blog_ratings`, { params })
  const arr = resp?.data?.["hydra:member"] ?? []
  const count = arr.length
  const average = count ? arr.reduce((s, it) => s + Number(it.rating || 0), 0) / count : 0
  return { average, count }
}

async function getManyPostRatingsApi(blogId, postIds = []) {
  const out = {}
  for (const id of postIds) {
    try {
      out[id] = await getPostRatingApi(blogId, id)
    } catch {
      out[id] = { average: 0, count: 0 }
    }
  }
  return out
}

/* =========================
   Uploads (ResourceFile) + Attachments
   ========================= */

async function uploadResourceFileApi(file) {
  const fd = new FormData()
  fd.append("file", file, file.name)
  const resp = await axios.post(`${ENTRYPOINT}resource_files`, fd, {
    headers: { "Content-Type": "multipart/form-data" },
    params: withCourseParams(),
  })
  const data = resp?.data || {}
  return {
    path: data.path || data.filePath || data.url || "",
    filename: data.filename || file.name,
    size: Number(data.size ?? file.size ?? 0),
    iri: data["@id"] || null,
  }
}

async function createAttachmentForPostApi({ blogId, postId, fileInfo, comment = "" }) {
  const payload = {
    blog: iri("c_blogs", blogId),
    post: iri("c_blog_posts", postId),
    path: fileInfo.path,
    filename: fileInfo.filename,
    size: Number(fileInfo.size || 0),
    comment,
  }
  await axios.post(`${ENTRYPOINT}c_blog_attachments`, payload, {
    headers: { "Content-Type": "application/json" },
    params: withCourseParams(),
  })
  return { ok: true }
}

async function uploadBlogAttachmentApi({ blogId, postId, file, comment = "" }) {
  const fd = new FormData()
  fd.append("uploadFile", file, file.name)
  fd.append("blog", iri("c_blogs", blogId))
  fd.append("post", iri("c_blog_posts", postId))
  if (comment) fd.append("comment", comment)
  const resp = await axios.post(`${ENTRYPOINT}c_blog_attachments/upload`, fd, {
    headers: { "Content-Type": "multipart/form-data" },
    params: courseContextParams(),
  })
  return resp?.data ?? { ok: true }
}

/* =========================
   Members (c_blog_rel_user)
   ========================= */

/** GET /c_blog_rel_users */
async function listBlogMembers(blogId) {
  const params = withCourseParams({
    blog: iri("c_blogs", blogId),
    itemsPerPage: 1000,
  })
  const resp = await axios.get(`${ENTRYPOINT}c_blog_rel_users`, { params })
  const arr = resp?.data?.["hydra:member"] ?? []
  const hydrated = await Promise.all(
    arr.map(async (row) => {
      const relId = extractId(row)
      const base = await ensureUserInfo(row.user)
      return { relId, userId: base.id, name: base.name, avatar: base.avatar, role: "member" }
    }),
  )
  return hydrated
}

/** POST /c_blog_rel_users */
async function addBlogMember(blogId, userId) {
  const payload = {
    blog: iri("c_blogs", blogId),
    user: iri("users", userId),
  }
  await axios.post(`${ENTRYPOINT}c_blog_rel_users`, payload, {
    headers: { "Content-Type": "application/json" },
    params: withCourseParams(),
  })
  return { ok: true }
}

/** DELETE /c_blog_rel_users/{relId} */
async function removeBlogMember(relId) {
  await axios.delete(`${ENTRYPOINT}c_blog_rel_users/${relId}`, {
    params: withCourseParams(),
  })
  return { ok: true }
}

/** Build a de-duplicated user pool from course/session context */
async function listCourseOrSessionUsers() {
  const ctx = courseContextParams()
  const cid = ctx.cid
  const sid = ctx.sid

  const usersById = new Map()

  const addUsers = async (rows, metaLabel) => {
    for (const it of rows) {
      const base = await ensureUserInfo(it.user || it)
      if (!base.id) continue
      if (!usersById.has(base.id)) {
        usersById.set(base.id, { id: base.id, name: base.name, avatar: base.avatar, meta: metaLabel })
      }
    }
  }

  if (cid) {
    const params = withCourseParams({
      course: iri("courses", cid),
      itemsPerPage: 1000,
    })
    const resp = await axios.get(`${ENTRYPOINT}course_rel_users`, { params })
    await addUsers(resp?.data?.["hydra:member"] ?? [], "course")
  }

  if (sid) {
    const params = withCourseParams({
      session: iri("sessions", sid),
      itemsPerPage: 1000,
    })
    const resp = await axios.get(`${ENTRYPOINT}session_rel_users`, { params })
    await addUsers(resp?.data?.["hydra:member"] ?? [], "session")
  }

  return Array.from(usersById.values()).sort((a, b) => a.name.localeCompare(b.name))
}

/* =========================
   Tasks (CBlogTask) + Assignments (CBlogTaskRelUser)
   ========================= */

/** Normalize CBlogTask row */
function mapTaskRow(row) {
  return {
    id: extractId(row),
    taskId: row.taskId ?? 0,
    title: row.title ?? "",
    description: row.description ?? "",
    color: row.color ?? "#0ea5e9",
    status: row.systemTask ? "System" : "Open",
    system: !!row.systemTask,
  }
}

/** Normalize CBlogTaskRelUser row */
async function mapAssignmentRow(row) {
  const id = extractId(row)
  const taskId = extractId(row.task)
  const targetDate = row.targetDate ?? row.target_date ?? null
  const u = await ensureUserInfo(row.user)
  return {
    id,
    taskId,
    user: { id: u.id, name: u.name, avatar: u.avatar },
    targetDate: targetDate ? String(targetDate).slice(0, 10) : null,
  }
}

/** GET /c_blog_tasks */
async function listTasks(blogId = resolveBlogIdFromPath()) {
  const params = withCourseParams({
    blog: iri("c_blogs", blogId),
    itemsPerPage: 1000,
  })
  const resp = await axios.get(`${ENTRYPOINT}c_blog_tasks`, { params })
  const arr = resp?.data?.["hydra:member"] ?? []
  return arr.map(mapTaskRow)
}

/** POST /c_blog_tasks */
async function createTask(
  { title, description, color, taskId = 0, systemTask = false },
  blogId = resolveBlogIdFromPath(),
) {
  const payload = {
    title: String(title ?? "").trim(),
    description: String(description ?? ""),
    color: color || "#0ea5e9",
    taskId: Number(taskId) || 0,
    systemTask: !!systemTask,
    blog: iri("c_blogs", blogId),
  }
  const resp = await axios.post(`${ENTRYPOINT}c_blog_tasks`, payload, {
    headers: { "Content-Type": "application/json" },
    params: withCourseParams(),
  })
  return { id: extractId(resp?.data) }
}

/** GET /c_blog_task_rel_users */
async function listAssignments(blogId = resolveBlogIdFromPath()) {
  const params = withCourseParams({
    blog: iri("c_blogs", blogId),
    itemsPerPage: 1000,
  })
  const resp = await axios.get(`${ENTRYPOINT}c_blog_task_rel_users`, { params })
  const arr = resp?.data?.["hydra:member"] ?? []
  return Promise.all(arr.map(mapAssignmentRow))
}

/** POST /c_blog_task_rel_users */
async function assignTask({ taskId, userId, targetDate }, blogId = resolveBlogIdFromPath()) {
  const payload = {
    task: iri("c_blog_tasks", taskId),
    user: iri("users", userId),
    blog: iri("c_blogs", blogId),
    targetDate, // ISO "YYYY-MM-DD"
  }
  await axios.post(`${ENTRYPOINT}c_blog_task_rel_users`, payload, {
    headers: { "Content-Type": "application/json" },
    params: withCourseParams(),
  })
  return { ok: true }
}

async function createPostWithFiles({ blogId, title, fullText, files = [], commentsByIndex = [] }) {
  const { id: postId } = await createPostApi({ blogId, title, fullText })

  for (let i = 0; i < files.length; i++) {
    const file = files[i]
    const comment = commentsByIndex[i] || ""
    await uploadBlogAttachmentApi({ blogId, postId, file, comment })
  }

  return { postId }
}

async function ratePost(blogId, postId, score) {
  return ratePostApi(blogId, postId, score)
}

async function getPostRating(blogId, postId) {
  return getPostRatingApi(blogId, postId)
}

/* =========================
   Export
   ========================= */

export default {
  createPostWithFiles,
  listProjects,
  createProject,
  renameProject,
  toggleProjectVisibility,
  deleteProject,
  getProject,

  // Posts + Ratings
  listPostsApi,
  createPostApi,
  getPostApi,
  listPostAttachmentsApi,
  ratePostApi,
  getPostRatingApi,
  ratePost,
  getPostRating,
  getManyPostRatingsApi,
  updatePost,
  deletePost,
  updateComment,
  deleteComment,

  // Uploads / Attachments
  uploadResourceFileApi,
  createAttachmentForPostApi,
  uploadBlogAttachmentApi,

  // Comments
  async listComments(postId) {
    try {
      const params = withCourseParams({
        post: iri("c_blog_posts", postId),
        "order[dateCreation]": "asc",
      })
      const resp = await axios.get(`${ENTRYPOINT}c_blog_comments`, { params })
      return (resp?.data?.["hydra:member"] ?? []).map(mapCommentRow)
    } catch (e) {
      if (e?.response?.status === 404) return []
      throw e
    }
  },
  async addComment(postId, { text, blogId } = {}) {
    const payload = {
      post: iri("c_blog_posts", postId),
      comment: String(text ?? "").trim(),
    }
    if (blogId) payload.blog = iri("c_blogs", blogId)
    await axios.post(`${ENTRYPOINT}c_blog_comments`, payload, {
      headers: { "Content-Type": "application/json" },
      params: withCourseParams(),
    })
    return { ok: true }
  },

  // Members and user pools
  listBlogMembers,
  addBlogMember,
  removeBlogMember,
  listCourseOrSessionUsers,

  // Tasks & Assignments
  listTasks,
  createTask,
  listAssignments,
  assignTask,

  // Backward-compat alias used elsewhere in UI
  async listMembers() {
    const blogId = resolveBlogIdFromPath()
    return listBlogMembers(blogId)
  },
}
