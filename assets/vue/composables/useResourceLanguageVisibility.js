import { computed } from "vue"
import { usePlatformConfig } from "../store/platformConfig"

export function useResourceLanguageVisibility() {
  const platformConfigStore = usePlatformConfig()

  const resourceLanguageEnabled = computed(
    () => "true" === platformConfigStore.getSetting("language.language_by_resource"),
  )

  return { resourceLanguageEnabled }
}
