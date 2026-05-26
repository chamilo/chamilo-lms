<template>
  <div>
    <div class="mb-4">
      <Button
        icon="mdi mdi-arrow-left"
        :label="t('Back to list')"
        class="p-button-secondary"
        @click="router.push({ name: 'BranchList' })"
      />
    </div>

    <BranchForm
      v-model="item"
      @submit="updateItem"
    />
    <Loading :visible="isLoading" />
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { useToast } from "primevue/usetoast"
import BranchForm from "../../components/branch/Form.vue"
import Loading from "../../components/Loading.vue"
import baseService from "../../services/baseService"

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const toast = useToast()

const item = ref({})
const isLoading = ref(false)

onMounted(async () => {
  const id = route.query.id
  if (!id) return

  isLoading.value = true
  try {
    item.value = await baseService.get(id)
  } catch (e) {
    console.error(e)
  } finally {
    isLoading.value = false
  }
})

async function updateItem(formData) {
  isLoading.value = true
  try {
    await baseService.put(formData["@id"], {
      title: formData.title,
      description: formData.description,
      parent: formData.parent || null,
      branchIp: formData.branchIp || null,
      latitude: formData.latitude || null,
      longitude: formData.longitude || null,
      dwnSpeed: formData.dwnSpeed ?? null,
      upSpeed: formData.upSpeed ?? null,
      delay: formData.delay ?? null,
      adminMail: formData.adminMail || null,
      adminName: formData.adminName || null,
      adminPhone: formData.adminPhone || null,
    })
    toast.add({ severity: "success", detail: t("{0} updated", [formData["@id"]]), life: 3500 })
    router.push({ name: "BranchList" })
  } catch (e) {
    toast.add({ severity: "error", detail: e.message, life: 3500 })
  } finally {
    isLoading.value = false
  }
}
</script>
