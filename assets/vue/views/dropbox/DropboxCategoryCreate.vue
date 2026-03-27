<template>
  <div class="max-w-lg">
    <BaseToolbar><template #start><h3 class="font-semibold">New folder</h3></template></BaseToolbar>

    <BaseInputText id="catTitle" label="Category name" v-model="name" :form-submitted="submitted" :is-invalid="!name" />

    <div class="mt-2 text-sm text-gray-600">Area: <b>{{ area }}</b></div>

    <div class="flex justify-end gap-2 mt-2">
      <BaseAppLink :to="backTo"><BaseButton type="black" icon="xmark" :label="t('Cancel')" /></BaseAppLink>
      <BaseButton type="success" icon="check" :label="t('Create category')" @click="save" />
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"

import service from "../../services/dropbox"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const area = route.query.area === "received" ? "received" : "sent"
const name = ref("")
const submitted = ref(false)

const backTo = computed(() => ({ name: area === "sent" ? "DropboxListSent" : "DropboxListReceived", params: route.params }))

async function save(){
  submitted.value = true
  if (!name.value.trim()) return
  await service.createCategory({ title: name.value.trim(), area })
  router.push(backTo.value)
}
</script>
