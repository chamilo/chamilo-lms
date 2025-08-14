import { findUserActivePortals } from "../../services/accessurlService"
import { useSecurityStore } from "../../store/securityStore"
import { computed, ref } from "vue"
import { useNotification } from "../notification"
import securityService from "../../services/securityService"

export function useAccessUrlChooser() {
  const securityStore = useSecurityStore()

  const { showErrorNotification } = useNotification()

  const visible = computed(() => securityStore.showAccessUrlChooser)
  const isLoading = ref(true)
  const accessUrls = ref([])

  async function init() {
    if (!securityStore.showAccessUrlChooser) {
      return
    }

    try {
      const items = await findUserActivePortals(securityStore.user["@id"])

      accessUrls.value = items

      if (1 === items.length) {
        isLoading.value = false

        await doRedirectToPortal(items[0].url)
      }
    } catch (error) {
      showErrorNotification(error)
    } finally {
      if (1 !== accessUrls.value.length) {
        isLoading.value = false
      }
    }
  }

  async function doRedirectToPortal(url) {
    try {
      await securityService.loginTokenCheck(url, await securityService.loginTokenRequest())

      window.location.href = url
    } catch (error) {
      showErrorNotification(error)
    }
  }

  init().then(() => {})

  return {
    visible,
    isLoading,
    accessUrls,
    doRedirectToPortal,
  }
}
