import { ref } from "vue"
import courseService from "../../services/courseService"

export function useCourseRequirementStatus(courseId, sessionId, onLockChange = null) {
  const isLocked = ref(false)
  const hasRequirements = ref(false)
  const requirementList = ref([])
  const graphImage = ref(null)
  const loading = ref(true)

  async function fetchStatus() {
    if (!courseId || courseId === 0) {
      loading.value = false
      return
    }

    loading.value = true
    try {
      const result = await courseService.getNextCourse(courseId, sessionId)
      const locked = !(result?.allowSubscription ?? true)

      isLocked.value = locked
      hasRequirements.value = result?.sequenceList?.length > 0
      requirementList.value = result?.sequenceList ?? []
      graphImage.value = result?.graph || null

      if (onLockChange) {
        onLockChange(locked)
      }
    } catch (e) {
      isLocked.value = false
      hasRequirements.value = false
      requirementList.value = []
      graphImage.value = null

      if (onLockChange) {
        onLockChange(false)
      }
    } finally {
      loading.value = false
    }
  }

  return {
    isLocked,
    hasRequirements,
    requirementList,
    graphImage,
    loading,
    fetchStatus,
  }
}
