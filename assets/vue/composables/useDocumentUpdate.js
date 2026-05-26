import { computed, onBeforeUnmount, ref, watch } from "vue"
import { useStore } from "vuex"
import { useRoute, useRouter } from "vue-router"
import { isEmpty } from "lodash"
import { useNotification } from "./notification"

const MODULE = "documents"

export function useDocumentUpdate() {
  const store = useStore()
  const route = useRoute()
  const router = useRouter()
  const { showErrorNotification } = useNotification()

  const isLoading = computed(() => store.state[MODULE].isLoading)
  const error = computed(() => store.state[MODULE].error)
  const updated = computed(() => store.state[MODULE].updated)
  const violations = computed(() => store.state[MODULE].violations)

  const item = ref({})

  const retrievedItem = computed(() => {
    let id = route.params.id

    if (isEmpty(id)) {
      id = route.query.id
    }

    if (isEmpty(id)) {
      return null
    }

    return store.getters[`${MODULE}/find`](decodeURIComponent(id))
  })

  watch(retrievedItem, (val) => {
    if (!isEmpty(val)) {
      item.value = { ...val }
    }
  })

  watch(error, (message) => {
    if (message) {
      showErrorNotification(message)
    }
  })

  watch(updated, (val) => {
    if (val) {
      router.go(-1)
    }
  })

  function retrieve() {
    let id = route.params.id

    if (isEmpty(id)) {
      id = route.query.id
    }

    if (!isEmpty(id)) {
      store.dispatch(`${MODULE}/load`, decodeURIComponent(id))
    }
  }

  function updateWithFormData(payload) {
    return store.dispatch(`${MODULE}/updateWithFormData`, payload)
  }

  function onSendFormData(formRef) {
    if (!formRef) {
      return
    }

    formRef.v$.$touch()

    if (!formRef.v$.$invalid) {
      updateWithFormData(formRef.v$.item.$model)
      item.value = { ...(retrievedItem.value ?? {}) }
    }
  }

  function resetForm(formRef) {
    if (formRef) {
      formRef.v$.$reset()
    }

    item.value = { ...(retrievedItem.value ?? {}) }
  }

  onBeforeUnmount(() => {
    store.dispatch(`${MODULE}/resetUpdate`)
    store.dispatch(`${MODULE}/resetDelete`)
    store.dispatch(`${MODULE}/resetCreate`)
  })

  return {
    item,
    isLoading,
    error,
    updated,
    violations,
    retrieve,
    updateWithFormData,
    onSendFormData,
    resetForm,
  }
}
