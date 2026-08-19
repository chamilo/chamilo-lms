<script setup>
import { ref, computed, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import accessUrlManageService from "../../services/accessUrlManageService"
import { useNotification } from "../../composables/notification"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import AccessUrlDualList from "../../components/accessurl/AccessUrlDualList.vue"

const { t } = useI18n()
const route = useRoute()
const notification = useNotification()

const urls = ref([])
const assigned = ref([])
const available = ref([])
const selectedUrlId = ref(Number(route.query.access_url_id) || 0)
const isLoading = ref(false)
const isSaving = ref(false)

const availableItems = computed(() => available.value.map((c) => ({ id: c.id, label: c.title })))
const assignedItems = computed(() => assigned.value.map((c) => ({ id: c.id, label: c.title })))

async function loadData() {
  isLoading.value = true
  try {
    let data = await accessUrlManageService.listCourseCategories(selectedUrlId.value)
    urls.value = data.urls
    if (!selectedUrlId.value && urls.value.length) {
      selectedUrlId.value = urls.value[0].id
      data = await accessUrlManageService.listCourseCategories(selectedUrlId.value)
    }
    assigned.value = data.assigned
    available.value = data.available
  } catch (e) {
    notification.showErrorNotification(e)
  } finally {
    isLoading.value = false
  }
}

function onUrlChange() {
  loadData()
}

function addCategory(item) {
  const category = available.value.find((c) => c.id === item.id)
  available.value = available.value.filter((c) => c.id !== item.id)
  if (category) {
    assigned.value = [...assigned.value, category]
  }
}

function removeCategory(item) {
  const category = assigned.value.find((c) => c.id === item.id)
  assigned.value = assigned.value.filter((c) => c.id !== item.id)
  if (category) {
    available.value = [...available.value, category]
  }
}

function addAllCategories(items) {
  const ids = new Set(items.map((i) => i.id))
  const categoriesToMove = available.value.filter((c) => ids.has(c.id))
  available.value = available.value.filter((c) => !ids.has(c.id))
  assigned.value = [...assigned.value, ...categoriesToMove]
}

function removeAllCategories() {
  available.value = [...available.value, ...assigned.value]
  assigned.value = []
}

async function save() {
  if (!selectedUrlId.value) {
    notification.showErrorNotification(t("Select a URL"))
    return
  }

  isSaving.value = true
  try {
    await accessUrlManageService.assignCourseCategories({
      access_url_id: selectedUrlId.value,
      category_ids: assigned.value.map((c) => c.id),
    })
    notification.showSuccessNotification(t("Update successful"))
  } catch (e) {
    notification.showErrorNotification(e)
  } finally {
    isSaving.value = false
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <div class="flex flex-col gap-6">
    <SectionHeader :title="t('Edit groups for one URL')">
      <BaseButton
        :label="t('Back')"
        icon="back"
        type="plain"
        :route="{ name: 'AccessUrlManage' }"
      />
    </SectionHeader>

    <div class="flex flex-col gap-4">
      <BaseSelect
        id="access-url-course-categories-url"
        v-model="selectedUrlId"
        :label="t('Select URL')"
        :options="urls"
        option-label="url"
        option-value="id"
        @update:model-value="onUrlChange"
      />

      <AccessUrlDualList
        :available="availableItems"
        :assigned="assignedItems"
        :available-title="t('Course categories available')"
        :assigned-title="t('Course categories in selected URL')"
        :disabled="isLoading"
        @add="addCategory"
        @remove="removeCategory"
        @add-all="addAllCategories"
        @remove-all="removeAllCategories"
      >
        <template #actions>
          <BaseButton
            :label="t('Save')"
            type="success"
            :is-loading="isSaving"
            @click="save"
          />
        </template>
      </AccessUrlDualList>
    </div>
  </div>
</template>
