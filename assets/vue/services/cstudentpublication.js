import makeService from "./api"
import { useCidReq } from "../composables/cidReq"
import axios from "axios"
import { ENTRYPOINT } from "../config/entrypoint"

function buildCidParams() {
  const { cid, sid, gid } = useCidReq()

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
  return axios.put(`${ENTRYPOINT}c_student_publications/${id}`, payload, {
    params: buildCidParams(),
  })
}

async function findStudentAssignments() {
  const params = new URLSearchParams(buildCidParams()).toString()
  const response = await fetch(`/assignments/student?${params}`)
  if (!response.ok) throw new Error("Failed to load student assignments")
  return response.json()
}

async function getAssignmentMetadata(assignmentId, cid, sid = 0, gid = 0) {
  const params = new URLSearchParams({
    cid,
    ...(sid && { sid }),
    ...(gid && { gid }),
  }).toString()

  const response = await axios.get(`${ENTRYPOINT}c_student_publications/${assignmentId}?${params}`)
  return response.data
}

async function getAssignmentDetail({ assignmentId, page = 1, itemsPerPage = 10, order = {} }) {
  const params = {
    ...buildCidParams(),
    page,
    itemsPerPage,
    ...Object.fromEntries(Object.entries(order).map(([key, val]) => [`order[${key}]`, val])),
  }
  const response = await axios.get(`/assignments/${assignmentId}/submissions`, { params })
  return response.data
}

async function getAssignmentDetailForTeacher({ assignmentId, page = 1, itemsPerPage = 10, order = {} }) {
  const params = {
    ...buildCidParams(),
    page,
    itemsPerPage,
    ...Object.fromEntries(Object.entries(order).map(([key, val]) => [`order[${key}]`, val])),
  }
  const response = await axios.get(`/assignments/${assignmentId}/submissions/teacher`, { params })
  return response.data
}

async function uploadStudentAssignment(formData, queryParams) {
  const response = await axios.post(`${ENTRYPOINT}c_student_publications/upload?${queryParams}`, formData, {
    headers: { "Content-Type": "multipart/form-data" },
  })
  return response.data
}

async function getStudentProgress(queryParams = {}) {
  const merged = { ...buildCidParams(), ...queryParams }
  const params = new URLSearchParams(merged).toString()
  const url = params ? `/assignments/progress?${params}` : `/assignments/progress`
  const response = await axios.get(url)
  return response.data
}

async function deleteAssignmentSubmission(submissionId) {
  await axios.delete(`/assignments/submissions/${submissionId}`, {
    params: buildCidParams(),
  })
}

async function updateSubmission(id, data) {
  await axios.patch(`/assignments/submissions/${id}/edit`, data, {
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

  const response = await axios.post(`${ENTRYPOINT}c_student_publication_comments/upload`, formData, {
    params,
    headers: { "Content-Type": "multipart/form-data" },
  })

  return response.data
}

async function loadComments(submissionId) {
  try {
    const response = await axios.get(`${ENTRYPOINT}c_student_publication_comments`, {
      params: {
        "publication.iid": submissionId,
        ...buildCidParams(),
      },
    })
    return response.data["hydra:member"] || []
  } catch (error) {
    console.error("[Assignments] Failed to load comments", error)
    return []
  }
}

async function moveSubmission(submissionId, newAssignmentId) {
  const response = await axios.patch(
    `/assignments/submissions/${submissionId}/move`,
    { newAssignmentId },
    { params: buildCidParams() },
  )
  return response.data
}

async function getUnsubmittedUsers(assignmentId) {
  const params = new URLSearchParams(buildCidParams()).toString()
  const response = await axios.get(`/assignments/${assignmentId}/unsubmitted-users?${params}`)
  return response.data["hydra:member"]
}

async function sendEmailToUnsubmitted(assignmentId, queryParams = {}) {
  const merged = { ...buildCidParams(), ...queryParams }
  const params = new URLSearchParams(merged).toString()
  const response = await axios.post(`/assignments/${assignmentId}/unsubmitted-users/email?${params}`)
  return response.data
}

async function deleteAllCorrections(assignmentId, cid, sid = 0) {
  const params = { ...buildCidParams(), cid, ...(sid && { sid }) }
  await axios.delete(`/assignments/${assignmentId}/corrections/delete`, { params })
}

async function exportAssignmentPdf(assignmentId, cid, sid = 0, gid = 0) {
  const params = { ...buildCidParams(), cid, ...(sid && { sid }), ...(gid && { gid }) }
  const response = await axios.get(`/assignments/${assignmentId}/export/pdf`, {
    params,
    responseType: "blob",
  })
  return response.data
}

async function downloadAssignments(assignmentId) {
  const response = await axios.get(`/assignments/${assignmentId}/download-package`, {
    params: buildCidParams(),
    responseType: "blob",
  })
  return response.data
}

async function uploadCorrectionsPackage(assignmentId, file) {
  const formData = new FormData()
  formData.append("file", file)

  const response = await axios.post(`/assignments/${assignmentId}/upload-corrections-package`, formData, {
    params: buildCidParams(),
    headers: { "Content-Type": "multipart/form-data" },
  })

  return response.data
}

async function updateScore(iid, qualification) {
  return axios.put(`${ENTRYPOINT}c_student_publications/${iid}`, { qualification }, { params: buildCidParams() })
}

async function aiGradeSubmission(submissionId, payload = {}) {
  const response = await axios.post(`/assignments/submissions/${submissionId}/ai-grade`, payload, {
    headers: { "Content-Type": "application/json" },
    params: buildCidParams(),
  })
  return response.data
}

async function getAiTextProviders() {
  const { data } = await axios.get("/assignments/ai/text-providers")
  return data
}

async function getAiTaskGraderDefaultPrompt(submissionId, params = {}) {
  const { data } = await axios.get(`/assignments/submissions/${submissionId}/ai-task-grader-default-prompt`, {
    params,
  })
  return data
}

async function aiTaskGrade(submissionId, payload) {
  const { data } = await axios.post(`/assignments/submissions/${submissionId}/ai-task-grade`, payload)
  return data
}

async function getAiTaskGradeCapabilities(submissionId) {
  const { data } = await this.api.get(`/assignments/submissions/${submissionId}/ai-task-grade-capabilities`)
  return data
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
}
