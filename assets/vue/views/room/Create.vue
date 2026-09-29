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
import { useNotification } from "../../composables/notification"
import RoomForm from "../../components/room/Form.vue"
import Loading from "../../components/Loading.vue"
import baseService from "../../services/baseService"

const router = useRouter()
const { t } = useI18n()
const { showSuccessNotification, showErrorNotification } = useNotification()

const item = ref({
  title: "",
  description: "",
  branch: null,
  floorNumber: null,
  capacity: null,
})
const isLoading = ref(false)

async function createItem(formData) {
  isLoading.value = true
  try {
    await baseService.post("/api/rooms", formData)
    showSuccessNotification(t("{0} created", [formData.title]))
    router.push({ name: "RoomList" })
  } catch (e) {
    showErrorNotification(e)
  } finally {
    isLoading.value = false
  }
}
</script>
