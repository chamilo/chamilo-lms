import makeService from "./api"
import { useCidReq } from "../composables/cidReq"
import axios from "axios"
import { ENTRYPOINT } from "../config/entrypoint"

async function findStudentAssignments() {
  const { sid, cid, gid } = useCidReq()
  const params = new URLSearchParams({ cid, ...(sid && { sid }), ...(gid && { gid }) }).toString()
  const response = await fetch(`/assignments/student?${params}`)
  if (!response.ok) throw new Error("Failed to load student assignments")
  return response.json()
}

async function getAssignmentMetadata(assignmentId) {
  const { sid, cid, gid } = useCidReq()
  const params = new URLSearchParams({ cid, ...(sid && { sid }), ...(gid && { gid }) }).toString()
  const response = await axios.get(`${ENTRYPOINT}c_student_publications/${assignmentId}?${params}`)
  return response.data
}

async function getAssignmentDetail({ assignmentId, page = 1, itemsPerPage = 10, order = {} }) {
  const params = {
    page,
    itemsPerPage,
    ...Object.fromEntries(Object.entries(order).map(([key, val]) => [`order[${key}]`, val])),
  }
  const response = await axios.get(`/assignments/${assignmentId}/submissions`, { params })
  return response.data
}

async function getAssignmentDetailForTeacher({ assignmentId, page = 1, itemsPerPage = 10, order = {} }) {
  const params = {
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
  const params = new URLSearchParams(queryParams).toString()
  const url = params ? `/assignments/progress?${params}` : `/assignments/progress`
  const response = await axios.get(url)
  return response.data
}

async function deleteAssignmentSubmission(submissionId) {
  await axios.delete(`/assignments/submissions/${submissionId}`)
}

async function updateSubmission(id, data) {
  await axios.patch(`/assignments/submissions/${id}/edit`, data)
}

async function uploadComment(submissionId, parentResourceNodeId, formData, sendMail = false) {
  const queryParams = new URLSearchParams({
    submissionId,
    parentResourceNodeId,
    filetype: "file",
    sendMail: sendMail ? "1" : "0",
  }).toString()

  const response = await axios.post(`${ENTRYPOINT}c_student_publication_comments/upload?${queryParams}`, formData, {
    headers: {
      "Content-Type": "multipart/form-data",
    },
  })
  return response.data
}

async function loadComments(submissionId) {
  try {
    const response = await axios.get(`${ENTRYPOINT}c_student_publication_comments?publication.iid=${submissionId}`)
    return response.data["hydra:member"] || []
  } catch (error) {
    console.error("Failed to load comments", error)
    return []
  }
}

async function moveSubmission(submissionId, newAssignmentId) {
  const response = await axios.patch(`/assignments/submissions/${submissionId}/move`, {
    newAssignmentId,
  })
  return response.data
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
}
