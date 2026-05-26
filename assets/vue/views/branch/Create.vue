<template>
  <div>
    <BranchForm
      v-model="item"
      @submit="createItem"
    />
    <Loading :visible="isLoading" />
  </div>
</template>

<script setup>
import { ref } from "vue"
import { useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import { useToast } from "primevue/usetoast"
import BranchForm from "../../components/branch/Form.vue"
import Loading from "../../components/Loading.vue"
import baseService from "../../services/baseService"

const router = useRouter()
const { t } = useI18n()
const toast = useToast()

const item = ref({
  title: "",
  description: "",
})
const isLoading = ref(false)

async function createItem(formData) {
  isLoading.value = true
  try {
    await baseService.post("/api/branches", formData, true)
    toast.add({ severity: "success", detail: t("{0} created", [formData.title]), life: 3500 })
    router.push({ name: "BranchList" })
  } catch (e) {
    toast.add({ severity: "error", detail: e.message, life: 3500 })
  } finally {
    isLoading.value = false
  }
}
</script>
