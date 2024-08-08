<template>
  <div v-if="item && canEditItem">
    <DocumentsForm
      v-model="item"
      @submit="updateItemWithFormData"
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
import { computed, ref, onMounted } from 'vue'
import DocumentsForm from "../../components/documents/Form.vue"
import Loading from "../../components/Loading.vue"
import EditLinks from "../../components/resource_links/EditLinks.vue"
import { useDatatableUpdate } from "../../composables/datatableUpdate"
import { useSecurityStore } from "../../store/securityStore"
import { useRoute } from 'vue-router'
import { checkIsAllowedToEdit } from '../../composables/userPermissions'

const securityStore = useSecurityStore()
const route = useRoute()
const isAllowedToEdit = ref(false)
const isCurrentTeacher = computed(() => securityStore.isCurrentTeacher || isAllowedToEdit.value)
const { item, retrieve, updateItemWithFormData, isLoading } = useDatatableUpdate("Documents")

const canEditItem = computed(() => {

  console.log('item.value ::: ', item.value)

  const resourceLink = item.value?.resourceLinkListFromEntity?.[0]
  const sidFromResourceLink = resourceLink?.session?.['@id']
  return (
    (sidFromResourceLink && sidFromResourceLink === `/api/sessions/${route.query.sid}` && isAllowedToEdit.value) ||
    isCurrentTeacher.value
  )
})

onMounted(async () => {
  isAllowedToEdit.value = await checkIsAllowedToEdit(true, true, true)
  await retrieve()
})
</script>
