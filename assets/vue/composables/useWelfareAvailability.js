import { reactive, toRefs, watch } from "vue"
import { useSecurityStore } from "../store/securityStore"
import welfareService from "../services/welfareService"

/** Navigation hints only; server checks setting, permissions and impersonation on every request. */
export function useWelfareAvailability() {
  const securityStore = useSecurityStore()
  const state = reactive({ available: false, canInvestigate: false })

  watch(
    () => [securityStore.isAuthenticated, securityStore.user?.id],
    async ([authenticated]) => {
      state.available = false
      state.canInvestigate = false
      if (!authenticated) return

      try {
        const result = await welfareService.availability()
        if (securityStore.isAuthenticated) {
          state.available = result.enabled === true
          state.canInvestigate = state.available && result.canInvestigate === true
        }
      } catch {
        state.available = false
        state.canInvestigate = false
      }
    },
    { immediate: true },
  )

  return toRefs(state)
}
