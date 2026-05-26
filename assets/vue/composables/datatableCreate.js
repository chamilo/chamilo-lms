import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import { useStore } from "vuex"
import { useToast } from "primevue/usetoast"

export function useDatatableCreate(servicePrefix) {
  const moduleName = servicePrefix.toLowerCase()

  const store = useStore()
  const router = useRouter()
  const route = useRoute()
  const { t } = useI18n()

  const toast = useToast()

  function onCreated(item) {
    toast.add({
      severity: "success",
      detail: t("{0} created", {
        resource: item.resourceNode ? item.resourceNode.title : item.title,
      }),
      life: 3500,
    })

    let folderParams = route.query

    router.push({
      name: `${servicePrefix}List`,
      params: { id: item["@id"] },
      query: folderParams,
    })
  }

  async function createItem(item) {
    await store.dispatch(`${moduleName}/create`, item)
  }

  return {
    createItem,
    onCreated,
  }
}
