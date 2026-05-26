import { computed, ref, watch } from "vue"
import { useStore } from "vuex"
import { useRoute } from "vue-router"
import { isEmpty } from "lodash"
import { useI18n } from "vue-i18n"
import { useToast } from "primevue/usetoast"

export function useDatatableUpdate(servicePrefix) {
  const moduleName = servicePrefix.toLowerCase()

  const store = useStore()
  const route = useRoute()
  const { t } = useI18n()

  const toast = useToast()

  const isLoading = computed(() => store.getters[`${moduleName}/isLoading`])

  const item = ref({})

  async function retrieve() {
    let id = route.params.id

    if (isEmpty(id)) {
      id = route.query.id
    }

    if (isEmpty(id)) {
      return
    }

    await store.dispatch(`${moduleName}/load`, decodeURIComponent(id))
  }

  const retrievedItem = computed(() => {
    let id = route.params.id

    if (isEmpty(id)) {
      id = route.query.id
    }

    if (isEmpty(id)) {
      return null
    }

    return store.getters[`${moduleName}/find`](id)
  })

  watch(retrievedItem, (newValue) => {
    item.value = newValue
  })

  async function updateItem(item) {
    await store.dispatch(`${moduleName}/update`, item)
  }

  const updated = computed(() => store.state[moduleName].updated)

  watch(updated, (newValue) => {
    if (!newValue) {
      return
    }

    onUpdated(item)
  })

  async function updateItemWithFormData(item) {
    await store.dispatch(`${moduleName}/updateWithFormData`, item)
  }

  function onUpdated(item) {
    toast.add({
      severity: "success",
      detail: t("{0} updated", [item["@id"]]),
      life: 3500,
    })
  }

  return {
    isLoading,
    item,
    retrieve,
    retrievedItem,
    updateItem,
    updateItemWithFormData,
    updated,
    onUpdated,
  }
}
