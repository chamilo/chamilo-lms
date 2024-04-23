import { defineStore } from "pinia"
import axios from "axios"
import { ref } from "vue"

export const useEnrolledStore = defineStore("enrolledStore", () => {
  // Reactive state to track if the user is enrolled in courses or sessions
  const isEnrolledInCourses = ref(false)
  const isEnrolledInSessions = ref(false)
  const isInitialized = ref(false)

  // Function to check enrollment status
  async function checkEnrollments() {
    try {
      const { data } = await axios.get("/course/check-enrollments")
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
    await checkEnrollments()
  }

  return {
    // Computed properties for reactivity
    isEnrolledInCourses,
    isEnrolledInSessions,
    initialize,
    isInitialized,
  }
})
