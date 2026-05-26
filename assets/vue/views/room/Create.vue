<template>
  <div>
    <RoomForm
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
import RoomForm from "../../components/room/Form.vue"
import Loading from "../../components/Loading.vue"
import baseService from "../../services/baseService"

const router = useRouter()
const { t } = useI18n()
const toast = useToast()

const item = ref({
  title: "",
  description: "",
  branch: null,
})
const isLoading = ref(false)

async function createItem(formData) {
  isLoading.value = true
  try {
    await baseService.post("/api/rooms", formData, true)
    toast.add({ severity: "success", detail: t("{0} created", [formData.title]), life: 3500 })
    router.push({ name: "RoomList" })
  } catch (e) {
    toast.add({ severity: "error", detail: e.message, life: 3500 })
  } finally {
    isLoading.value = false
  }
}
</script>
