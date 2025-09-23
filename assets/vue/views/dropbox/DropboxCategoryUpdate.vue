<template>
  <div class="max-w-lg" v-if="cat">
    <BaseToolbar><template #start><h3 class="font-semibold">Edit folder</h3></template></BaseToolbar>
    <BaseInputText id="catTitle2" label="Category name" v-model="name" :form-submitted="submitted" :is-invalid="!name" />
    <div class="flex justify-end gap-2 mt-4">
      <RouterLink :to="backTo"><BaseButton type="black" icon="xmark" label="Cancel" /></RouterLink>
      <BaseButton type="primary" icon="check" label="Save" @click="save" />
    </div>
  </div>
  <div v-else>Not found (demo)</div>
</template>

<script setup>
import { ref, computed, onMounted } from "vue"
import { useRoute, useRouter } from "vue-router"
import service from "../../services/dropbox"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"

const route = useRoute()
const router = useRouter()
const cat = ref(null)
const name = ref("")
const submitted = ref(false)

const backTo = computed(() => ({ name: cat.value?.area === "received" ? "DropboxListReceived" : "DropboxListSent", params: route.params }))

onMounted(async () => {
  const all = (await service.listCategories({ area: "sent" })).concat(await service.listCategories({ area: "received" }))
  cat.value = all.find(c => c.id === Number(route.params.id))
  name.value = cat.value?.title || ""
})

async function save(){
  submitted.value = true
  if (!name.value.trim()) return
  await service.updateCategory({ id: route.params.id, title: name.value.trim() })
  router.push(backTo.value)
}
</script>
