import { useI18n } from "vue-i18n"
import { useNotification } from "./notification"

// This is the migration from assets/vue/mixins/UploadMixin.js to composables
// some components still use UploadMixin with options API, this should be use
// when migrating from options API to composition API
export const useUpload = () => {
  const { t } = useI18n()
  const notification = useNotification()

  const onCreated = (item) => {
    notification.showSuccessNotification(t("{0} created", [item.resourceNode.title]))
  }

  const onError = (message) => {
    notification.showErrorNotification(message)
  }

  return {
    onCreated,
    onError,
  }
}
