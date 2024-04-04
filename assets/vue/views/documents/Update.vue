<template>
  <div v-if="item && isCurrentTeacher">
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
import DocumentsForm from "../../components/documents/Form.vue"
import Loading from "../../components/Loading.vue"
import EditLinks from "../../components/resource_links/EditLinks.vue"
import { useDatatableUpdate } from "../../composables/datatableUpdate"
import { useSecurityStore } from "../../store/securityStore"

const securityStore = useSecurityStore()
const isCurrentTeacher = securityStore.isCurrentTeacher

const { item, retrieve, updateItemWithFormData, isLoading } = useDatatableUpdate("Documents")

retrieve()
</script>
