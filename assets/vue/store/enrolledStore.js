import { defineStore } from "pinia"
import courseService from "../services/courseService"
import { ref } from "vue"

// Avoid re-hitting /course/check-enrollments on every layout mount (e.g. each SPA navigation
// that switches layouts) — the result rarely changes within a short window.
const CACHE_TTL_MS = 60000

export const useEnrolledStore = defineStore("enrolledStore", () => {
  // Reactive state to track if the user is enrolled in courses or sessions
  const isEnrolledInCourses = ref(false)
  const isEnrolledInSessions = ref(false)
  const isInitialized = ref(false)
  let lastFetchedAt = 0
  let pendingRequest = null

  // Function to check enrollment status
  async function checkEnrollments() {
    try {
      const data = await courseService.checkEnrollments()
      console.log("Check enrollments data:", data)
      isEnrolledInCourses.value = data.isEnrolledInCourses
      isEnrolledInSessions.value = data.isEnrolledInSessions
    } catch (error) {
      console.error("Error verifying enrollments:", error)
    } finally {
      isInitialized.value = true
    }
  }

  // Function to initialize the store
  async function initialize() {
    if (isInitialized.value && Date.now() - lastFetchedAt < CACHE_TTL_MS) {
      return
    }

    if (!pendingRequest) {
      lastFetchedAt = Date.now()
      pendingRequest = checkEnrollments().finally(() => {
        pendingRequest = null
      })
    }

    await pendingRequest
  }

  return {
    // Computed properties for reactivity
    isEnrolledInCourses,
    isEnrolledInSessions,
    initialize,
    isInitialized,
  }
})
