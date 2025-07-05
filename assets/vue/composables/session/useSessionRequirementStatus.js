import api from "../../config/api"
import { ref } from "vue"

export function useSessionRequirementStatus(sessionId) {
  const requirementList = ref([])
  const dependencyList = ref([])
  const allowSubscription = ref(true)
  const graphImage = ref(null)

  async function fetchStatus() {
    requirementList.value = []
    dependencyList.value = []
    graphImage.value = null

    const { data } = await api.get(`/sessions/${sessionId}/next-session`)

    if (data.requirements?.length) {
      requirementList.value.push({
        name: "Session requirements",
        requirements: data.requirements.map((item) => ({
          name: item.name,
          status: false,
          adminLink: item.admin_link,
        })),
      })
    }

    if (data.dependencies?.length) {
      dependencyList.value.push({
        name: "Sessions that depend on this session",
        requirements: data.dependencies.map((item) => ({
          name: item.name,
          status: null,
          adminLink: item.admin_link,
        })),
      })
    }

    graphImage.value = data.graph || null

    allowSubscription.value = requirementList.value.length === 0
  }

  return {
    fetchStatus,
    requirementList,
    dependencyList,
    allowSubscription,
    graphImage,
  }
}
