<template>
  <div class="p-4">
    <h2 class="text-xl font-bold mb-4">{{ t("Upload your assignment") }}</h2>
    <Dashboard
      :uppy="uppy"
      :props="{ width: '100%', height: 400 }"
    />
  </div>
</template>

<script setup>
import { ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useCidReq } from "../../composables/cidReq"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"
import cStudentPublicationService from "../../services/cstudentpublication"
import "@uppy/core/dist/style.css"
import "@uppy/dashboard/dist/style.css"
import { Dashboard } from "@uppy/vue"
import Uppy from "@uppy/core"
import XHRUpload from "@uppy/xhr-upload"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { cid, sid, gid } = useCidReq()
const { showSuccessNotification, showErrorNotification } = useNotification()
const parentResourceNodeId = Number(route.params.node)

const queryParams = new URLSearchParams({ cid, ...(sid && { sid }), ...(gid && { gid }) }).toString()

const uppy = ref(
  new Uppy({
    restrictions: {
      maxNumberOfFiles: 1,
    },
    autoProceed: true,
  }),
)

uppy.value.use(XHRUpload, {
  endpoint: `/api/c_student_publications/upload?${queryParams}`,
  formData: true,
})

uppy.value.on("file-added", async (file) => {
  const formData = new FormData()
  formData.append("uploadFile", file.data)
  formData.append("title", file.name)
  formData.append("filetype", "file")

  formData.append("parentResourceNodeId", parentResourceNodeId)
  formData.append("parentId", Number(route.params.id))

  formData.append(
    "resourceLinkList",
    JSON.stringify([{ cid, sid, gid, visibility: 2 }]),
  )

  try {
    const response = await cStudentPublicationService.uploadStudentAssignment(formData, queryParams)
    showSuccessNotification(t("Assignment uploaded successfully"))
    router.back()
  } catch (error) {
    showErrorNotification(error)
  }
})
</script>
