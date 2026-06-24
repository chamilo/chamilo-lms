<template>
  <div class="flex max-w-2xl flex-col gap-6">
    <SectionHeader :title="isEdit ? t('Edit category') : t('Add category')" />
    <form class="flex flex-col gap-6" @submit.prevent="save">
      <BaseInputText id="lp-category-title" v-model="title" name="title" :label="t('Title')" required />
      <div class="flex justify-end gap-2">
        <BaseButton :label="t('Cancel')" type="plain" @click="cancel" />
        <BaseButton :disabled="saving" :label="t('Save')" icon="content-save" type="success" is-submit />
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import { storeToRefs } from "pinia"
import { useCidReqStore } from "../../store/cidReq"
import lpService from "../../services/lpService"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useNotification } from "../../composables/notification"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { course, session } = storeToRefs(useCidReqStore())
const { showSuccessNotification, showErrorNotification } = useNotification()
const title = ref("")
const saving = ref(false)
const actionToken = ref("")
const categoryId = computed(() => Number(route.params.categoryId || 0))
const isEdit = computed(() => categoryId.value > 0)
const context = computed(() => ({ cid: course.value?.id, sid: session.value?.id ?? 0, gid: Number(route.query.gid ?? 0) }))

onMounted(async () => {
  try {
    actionToken.value = (await lpService.getActionToken(context.value)).token
    if (isEdit.value) {
      const categories = await lpService.getLpCategories({ ...context.value, "resourceNode.parent": route.params.node })
      title.value = categories.find((item) => Number(item.iid) === categoryId.value)?.title || ""
    }
  } catch (error) {
    showErrorNotification(error)
  }
})

async function save() {
  if (!title.value.trim()) {
    showErrorNotification(t("Title is required"))
    return
  }
  saving.value = true
  try {
    const payload = { title: title.value, csrfToken: actionToken.value }
    if (isEdit.value) {
      await lpService.updateCategory(categoryId.value, context.value, payload)
    } else {
      await lpService.createCategory(context.value, payload)
    }
    showSuccessNotification(t("Saved"))
    await router.push({ name: "LpList", query: route.query })
  } catch (error) {
    showErrorNotification(error)
  } finally {
    saving.value = false
  }
}

function cancel() {
  router.push({ name: "LpList", query: route.query })
}
</script>
