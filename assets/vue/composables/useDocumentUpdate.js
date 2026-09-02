import { computed, onBeforeUnmount, ref, watch } from "vue"
import { useStore } from "vuex"
import { useRoute, useRouter } from "vue-router"
import { isEmpty } from "lodash"
import { useNotification } from "./notification"
import documentsService from "../services/documents"

const MODULE = "documents"

export function useDocumentUpdate() {
  const store = useStore()
  const route = useRoute()
  const router = useRouter()
  const { showErrorNotification } = useNotification()

  const storeIsLoading = computed(() => store.state[MODULE].isLoading)
  const error = computed(() => store.state[MODULE].error)
  const updated = computed(() => store.state[MODULE].updated)
  const violations = computed(() => store.state[MODULE].violations)

  const item = ref({})
  const initialItem = ref({})
  const isContentLoading = ref(false)
  const contentLoadError = ref(null)
  const isLoading = computed(() => storeIsLoading.value || isContentLoading.value)
  let hydrationToken = 0

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

  watch(retrievedItem, async (val) => {
    if (isEmpty(val)) {
      return
    }

    const currentToken = ++hydrationToken
    contentLoadError.value = null
    isContentLoading.value = true

    try {
      const hydratedItem = await hydrateEditableContent(val)
      if (currentToken !== hydrationToken) {
        return
      }

      item.value = hydratedItem
      initialItem.value = { ...hydratedItem }
    } catch (loadError) {
      if (currentToken !== hydrationToken) {
        return
      }

      contentLoadError.value = loadError
      console.error("[Documents] Unable to load editable document content.", loadError)
      showErrorNotification(loadError?.message || String(loadError))
      item.value = { ...val }
      initialItem.value = { ...val }
    } finally {
      if (currentToken === hydrationToken) {
        isContentLoading.value = false
      }
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

    if (contentLoadError.value) {
      showErrorNotification(contentLoadError.value?.message || String(contentLoadError.value))
      return
    }

    formRef.v$.$touch()

    if (!formRef.v$.$invalid) {
      updateWithFormData(formRef.v$.item.$model)
      item.value = { ...initialItem.value }
    }
  }

  function resetForm(formRef) {
    if (formRef) {
      formRef.v$.$reset()
    }

    item.value = { ...initialItem.value }
  }

  async function hydrateEditableContent(value) {
    const hydratedItem = { ...value }
    const filetype = String(hydratedItem.filetype || "")
      .trim()
      .toLowerCase()
    const resourceFile = hydratedItem.resourceNode?.firstResourceFile
    const isEditableText = Boolean(resourceFile?.text) || ["certificate", "html"].includes(filetype)

    if (!isEditableText) {
      return hydratedItem
    }

    const embeddedContent = String(hydratedItem.resourceNode?.content || "")
    const contentUrl = String(hydratedItem.contentUrl || resourceFile?.contentUrl || "").trim()

    if (!contentUrl) {
      hydratedItem.contentFile = embeddedContent
      return hydratedItem
    }

    try {
      const rawContent = await documentsService.fetchTextContent(contentUrl)
      hydratedItem.contentFile = String(rawContent ?? embeddedContent)
    } catch (loadError) {
      if (embeddedContent) {
        console.warn("[Documents] Falling back to embedded document content after file loading failed.", loadError)
        hydratedItem.contentFile = embeddedContent
        return hydratedItem
      }

      throw loadError
    }

    return hydratedItem
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
