<template>
  <div v-if="item && canEditItem">
    <DocumentsForm
      v-model="item"
      @submit="updateAndReturnToList"
    >
      <EditLinks
        v-model="item"
        :show-share-with-user="false"
        :show-status="false"
        links-type="users"
      />
    </DocumentsForm>
    <Loading :visible="isLoading" />
  </div>
</template>

<script setup>
import { computed, onMounted } from "vue"
import { useRoute, useRouter } from "vue-router"
import DocumentsForm from "../../components/documents/Form.vue"
import Loading from "../../components/Loading.vue"
import EditLinks from "../../components/resource_links/EditLinks.vue"
import { useDatatableUpdate } from "../../composables/datatableUpdate"
import { useSecurityStore } from "../../store/securityStore"
import { useIsAllowedToEdit } from "../../composables/userPermissions"

const securityStore = useSecurityStore()
const route = useRoute()
const router = useRouter()
const { isAllowedToEdit } = useIsAllowedToEdit({ tutor: true, coach: true, sessionCoach: true })
const isCurrentTeacher = computed(() => securityStore.isCurrentTeacher || isAllowedToEdit.value)
const { item, retrieve, updateItemWithFormData, isLoading } = useDatatableUpdate("Documents")

const canEditItem = computed(() => {
  const resourceLink = item.value?.resourceLinkListFromEntity?.[0]
  const sidFromResourceLink = resourceLink?.session?.["@id"]
  return (
    (sidFromResourceLink && sidFromResourceLink === `/api/sessions/${route.query.sid}` && isAllowedToEdit.value) ||
    isCurrentTeacher.value
  )
})

function normalizeResourceNodeId(value) {
  if (null === value || undefined === value) {
    return null
  }

  if ("number" === typeof value) {
    return value
  }

  if ("string" === typeof value) {
    const iriMatch = value.match(/\/api\/resource_nodes\/(\d+)/)

    if (iriMatch) {
      return Number(iriMatch[1])
    }

    if (/^\d+$/.test(value)) {
      return Number(value)
    }

    return null
  }

  if ("object" === typeof value) {
    return normalizeResourceNodeId(value.id || value["@id"])
  }

  return null
}

function getContainingNodeId(documentItem) {
  const parentNodeId = normalizeResourceNodeId(documentItem?.resourceNode?.parent)

  if (parentNodeId) {
    return parentNodeId
  }

  const routeNodeId = normalizeResourceNodeId(route.params.node || route.query.node)

  if (routeNodeId) {
    return routeNodeId
  }

  return null
}

async function updateAndReturnToList(payload) {
  await updateItemWithFormData(payload)

  const containingNodeId = getContainingNodeId(payload)

  if (!containingNodeId) {
    router.back()
    return
  }

  router.push({
    name: "DocumentsList",
    params: {
      node: containingNodeId,
    },
    query: {
      cid: route.query.cid,
      sid: route.query.sid,
      gid: route.query.gid,
    },
  })
}

onMounted(async () => {
  await retrieve()
})
</script>
