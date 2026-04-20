import { computed, onBeforeUnmount, watch } from "vue"
import { useStore } from "vuex"
import { useNotification } from "./notification"

const MODULE = "documents"

export function useDocumentCreate() {
  const store = useStore()
  const { showErrorNotification } = useNotification()

  const isLoading = computed(() => store.state[MODULE].isLoading)
  const error = computed(() => store.state[MODULE].error)
  const created = computed(() => store.state[MODULE].created)
  const violations = computed(() => store.state[MODULE].violations)

  watch(error, (message) => {
    if (message) showErrorNotification(message)
  })

  function createWithFormData(payload) {
    return store.dispatch(`${MODULE}/createWithFormData`, payload)
  }

  function onSendFormData(formRef) {
    if (!formRef) {
      return null
    }

    formRef.v$.$touch()

    if (!formRef.v$.$invalid) {
      return createWithFormData(formRef.v$.item.$model)
    }

    return null
  }

  function resetForm(formRef, itemRef) {
    if (formRef) formRef.v$.$reset()
    if (itemRef) itemRef.value = {}
  }

  onBeforeUnmount(() => {
    store.dispatch(`${MODULE}/resetCreate`)
  })

  return {
    isLoading,
    error,
    created,
    violations,
    createWithFormData,
    onSendFormData,
    resetForm,
  }
}
