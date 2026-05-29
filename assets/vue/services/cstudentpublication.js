import makeService from "./api"
import { getCourseContext } from "../utils/courseContext"
import baseService from "./baseService"

function buildCidParams() {
  const { cid, sid, gid } = getCourseContext()

  return {
    cid,
    ...(sid ? { sid } : {}),
    ...(gid ? { gid } : {}),
  }
}

function extractId(idOrIri) {
  if (typeof idOrIri === "number") return idOrIri
  if (typeof idOrIri === "string") return Number(String(idOrIri).split("/").pop())
  return Number(idOrIri?.iid ?? idOrIri?.id ?? 0)
}

async function updatePublication(idOrIri, payload) {
  const id = extractId(idOrIri)

  return baseService.put(`/api/c_student_publications/${id}`, payload, { params: buildCidParams() })
}

async function findStudentAssignments() {
  return baseService.get(`/assignments/student`, buildCidParams())
}

async function getAssignmentMetadata(assignmentId, cid, sid = 0, gid = 0) {
  return baseService.get(`/api/c_student_publications/${assignmentId}`, {
    cid,
    ...(sid && { sid }),
    ...(gid && { gid }),
  })
}

async function getAssignmentDetail({ assignmentId, page = 1, itemsPerPage = 10, order = {} }) {
  const params = {
    ...buildCidParams(),
    page,
    itemsPerPage,
    ...Object.fromEntries(Object.entries(order).map(([key, val]) => [`order[${key}]`, val])),
  }

  return baseService.get(`/assignments/${assignmentId}/submissions`, params)
}

async function getAssignmentDetailForTeacher({ assignmentId, page = 1, itemsPerPage = 10, order = {} }) {
  const params = {
    ...buildCidParams(),
    page,
    itemsPerPage,
    ...Object.fromEntries(Object.entries(order).map(([key, val]) => [`order[${key}]`, val])),
  }

  return baseService.get(`/assignments/${assignmentId}/submissions/teacher`, params)
}

async function uploadStudentAssignment(formData, queryParams) {
  return baseService.post(`/api/c_student_publications/upload?${queryParams}`, formData)
}

async function getStudentProgress(queryParams = {}) {
  return baseService.get(`/assignments/progress`, { ...buildCidParams(), ...queryParams })
}

async function deleteAssignmentSubmission(submissionId) {
  await baseService.delete(`/assignments/submissions/${submissionId}`, { params: buildCidParams() })
}

async function updateSubmission(id, data) {
  await baseService.patch(`/assignments/submissions/${id}/edit`, data, {
    headers: { "Content-Type": "application/json" },
    params: buildCidParams(),
  })
}

async function uploadComment(submissionId, parentResourceNodeId, formData, sendMail = false) {
  const params = {
    submissionId,
    parentResourceNodeId,
    filetype: "file",
    sendMail: sendMail ? "1" : "0",
    ...buildCidParams(),
  }

  return baseService.post(`/api/c_student_publication_comments/upload`, formData, {}, { params })
}

async function loadComments(submissionId) {
  try {
    const { items } = await baseService.getCollection(`/api/c_student_publication_comments`, {
      "publication.iid": submissionId,
      ...buildCidParams(),
    })

    return items || []
  } catch (error) {
    console.error("[Assignments] Failed to load comments", error)
    return []
  }
}

async function deleteComment(commentIid) {
  await baseService.delete(`/api/c_student_publication_comments/${commentIid}`, { params: buildCidParams() })
}

async function moveSubmission(submissionId, newAssignmentId) {
  return baseService.patch(
    `/assignments/submissions/${submissionId}/move`,
    { newAssignmentId },
    {
      headers: { "Content-Type": "application/json" },
      params: buildCidParams(),
    },
  )
}

async function getUnsubmittedUsers(assignmentId) {
  const { items } = await baseService.getCollection(`/assignments/${assignmentId}/unsubmitted-users`, buildCidParams())

  return items
}

async function sendEmailToUnsubmitted(assignmentId, queryParams = {}) {
  const params = { ...buildCidParams(), ...queryParams }

  return baseService.post(`/assignments/${assignmentId}/unsubmitted-users/email`, {}, {}, { params })
}

async function deleteAllCorrections(assignmentId, cid, sid = 0) {
  const params = { ...buildCidParams(), cid, ...(sid && { sid }) }

  await baseService.delete(`/assignments/${assignmentId}/corrections/delete`, { params })
}

async function exportAssignmentPdf(assignmentId, cid, sid = 0, gid = 0) {
  const params = { ...buildCidParams(), cid, ...(sid && { sid }), ...(gid && { gid }) }

  const response = await baseService.getRaw(`/assignments/${assignmentId}/export/pdf`, {
    params,
    responseType: "blob",
  })

  return response.data
}

async function downloadAssignments(assignmentId) {
  const response = await baseService.getRaw(`/assignments/${assignmentId}/download-package`, {
    params: buildCidParams(),
    responseType: "blob",
  })

  return response.data
}

async function uploadCorrectionsPackage(assignmentId, file) {
  const formData = new FormData()
  formData.append("file", file)

  return baseService.post(
    `/assignments/${assignmentId}/upload-corrections-package`,
    formData,
    {},
    {
      params: buildCidParams(),
    },
  )
}

async function updateScore(iid, qualification) {
  return baseService.put(`/api/c_student_publications/${iid}`, { qualification }, { params: buildCidParams() })
}

async function aiGradeSubmission(submissionId, payload = {}) {
  return baseService.post(
    `/assignments/submissions/${submissionId}/ai-grade`,
    payload,
    {},
    {
      params: buildCidParams(),
    },
  )
}

async function getAiTextProviders() {
  return baseService.get("/assignments/ai/text-providers")
}

async function getAiTaskGraderDefaultPrompt(submissionId, params = {}) {
  return baseService.get(`/assignments/submissions/${submissionId}/ai-task-grader-default-prompt`, params)
}

async function aiTaskGrade(submissionId, payload) {
  return baseService.post(`/assignments/submissions/${submissionId}/ai-task-grade`, payload)
}

async function getAiTaskGradeCapabilities(submissionId) {
  return baseService.get(`/assignments/submissions/${submissionId}/ai-task-grade-capabilities`)
}

/** Fetches a single publication by id, with optional query params. */
async function getPublication(publicationId, params = {}) {
  return baseService.get(`/api/c_student_publications/${publicationId}`, params)
}

/** Creates a publication (assignment). */
async function createPublication(payload) {
  return baseService.post(`/api/c_student_publications`, payload)
}

/** Lists publication-user relations (collection endpoint). */
async function getRelUsers(params) {
  return baseService.getCollection(`/api/c_student_publication_rel_users`, params)
}

/** Links a user to a publication. */
async function addRelUser(payload) {
  return baseService.post(`/api/c_student_publication_rel_users`, payload)
}

/** Removes a publication-user relation. */
async function removeRelUser(relId) {
  return baseService.delete(`/api/c_student_publication_rel_users/${relId}`)
}

/** Lists publication-document relations (collection endpoint). */
async function getRelDocuments(params) {
  return baseService.getCollection(`/api/c_student_publication_rel_documents`, params)
}

/** Links a document to a publication. */
async function addRelDocument(payload, params = {}) {
  return baseService.post(`/api/c_student_publication_rel_documents`, payload, {}, { params })
}

/** Removes a publication-document relation. */
async function removeRelDocument(relId, params = {}) {
  return baseService.delete(`/api/c_student_publication_rel_documents/${relId}`, { params })
}

/** Uploads a teacher correction file for a submission. */
async function uploadCorrection(formData, { parentResourceNodeId, submissionId } = {}) {
  return baseService.post(
    `/api/c_student_publication_corrections/upload`,
    formData,
    {},
    { params: { parentResourceNodeId, submissionId, filetype: "file" } },
  )
}

export default {
  ...makeService("c_student_publications"),
  findStudentAssignments,
  getAssignmentMetadata,
  getAssignmentDetail,
  getAssignmentDetailForTeacher,
  uploadStudentAssignment,
  getStudentProgress,
  deleteAssignmentSubmission,
  updateSubmission,
  uploadComment,
  loadComments,
  deleteComment,
  moveSubmission,
  getUnsubmittedUsers,
  sendEmailToUnsubmitted,
  deleteAllCorrections,
  exportAssignmentPdf,
  downloadAssignments,
  uploadCorrectionsPackage,
  updateScore,
  updatePublication,
  aiGradeSubmission,
  getAiTextProviders,
  getAiTaskGraderDefaultPrompt,
  aiTaskGrade,
  getAiTaskGradeCapabilities,
  getPublication,
  createPublication,
  getRelUsers,
  addRelUser,
  removeRelUser,
  getRelDocuments,
  addRelDocument,
  removeRelDocument,
  uploadCorrection,
}
