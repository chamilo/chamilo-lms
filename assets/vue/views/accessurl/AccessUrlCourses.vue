<script setup>
import { ref, computed, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute } from "vue-router"
import Tabs from "primevue/tabs"
import TabList from "primevue/tablist"
import Tab from "primevue/tab"
import TabPanels from "primevue/tabpanels"
import TabPanel from "primevue/tabpanel"
import accessUrlManageService from "../../services/accessUrlManageService"
import { useNotification } from "../../composables/notification"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import AccessUrlDualList from "../../components/accessurl/AccessUrlDualList.vue"
import AccessUrlBulkAssign from "../../components/accessurl/AccessUrlBulkAssign.vue"

const { t } = useI18n()
const route = useRoute()
const notification = useNotification()

const urls = ref([])
const assigned = ref([])
const available = ref([])
const selectedUrlId = ref(Number(route.query.access_url_id) || 0)
const isLoading = ref(false)
const isSaving = ref(false)

const availableItems = computed(() => available.value.map((c) => ({ id: c.id, label: `${c.title} (${c.code})` })))
const assignedItems = computed(() => assigned.value.map((c) => ({ id: c.id, label: `${c.title} (${c.code})` })))
const allItemsForBulk = computed(() => [...assignedItems.value, ...availableItems.value])

async function loadData() {
  isLoading.value = true
  try {
    let data = await accessUrlManageService.listCourses(selectedUrlId.value)
    urls.value = data.urls
    if (!selectedUrlId.value && urls.value.length) {
      selectedUrlId.value = urls.value[0].id
      data = await accessUrlManageService.listCourses(selectedUrlId.value)
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

function addCourse(item) {
  const course = available.value.find((c) => c.id === item.id)
  available.value = available.value.filter((c) => c.id !== item.id)
  if (course) {
    assigned.value = [...assigned.value, course]
  }
}

function removeCourse(item) {
  const course = assigned.value.find((c) => c.id === item.id)
  assigned.value = assigned.value.filter((c) => c.id !== item.id)
  if (course) {
    available.value = [...available.value, course]
  }
}

function addAllCourses(items) {
  const ids = new Set(items.map((i) => i.id))
  const coursesToMove = available.value.filter((c) => ids.has(c.id))
  available.value = available.value.filter((c) => !ids.has(c.id))
  assigned.value = [...assigned.value, ...coursesToMove]
}

function removeAllCourses() {
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
    await accessUrlManageService.assignCourses({
      access_url_id: selectedUrlId.value,
      course_ids: assigned.value.map((c) => c.id),
    })
    notification.showSuccessNotification(t("Courses updated successfully"))
  } catch (e) {
    notification.showErrorNotification(e)
  } finally {
    isSaving.value = false
  }
}

async function onBulkSubmit({ itemIds, urlIds, action }) {
  try {
    await accessUrlManageService.bulkCourses({
      course_ids: itemIds,
      url_ids: urlIds,
      action,
    })
    notification.showSuccessNotification(
      "add" === action ? t("Course registered to the URL") : t("Course unregistered from the URL"),
    )
    await loadData()
  } catch (e) {
    notification.showErrorNotification(e)
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <div class="flex flex-col gap-6">
    <SectionHeader :title="t('Edit courses of an URL')">
      <BaseButton
        :label="t('Back')"
        icon="back"
        type="plain"
        :route="{ name: 'AccessUrlManage' }"
      />
    </SectionHeader>

    <Tabs value="0">
      <TabList class="flex gap-2 border-b border-gray-20">
        <Tab
          value="0"
          class="cursor-pointer border-b-2 border-transparent px-4 py-2 text-sm text-gray-50 hover:text-primary data-[p-active=true]:border-primary data-[p-active=true]:font-semibold data-[p-active=true]:text-primary"
        >
          {{ t("Multiple registration") }}
        </Tab>
        <Tab
          value="1"
          class="cursor-pointer border-b-2 border-transparent px-4 py-2 text-sm text-gray-50 hover:text-primary data-[p-active=true]:border-primary data-[p-active=true]:font-semibold data-[p-active=true]:text-primary"
        >
          {{ t("Add courses to URLs") }}
        </Tab>
      </TabList>
      <TabPanels>
        <TabPanel value="0">
          <div class="flex flex-col gap-4 pt-4">
            <BaseSelect
              id="access-url-courses-url"
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
              :available-title="t('Courses list')"
              :assigned-title="t('Courses of the selected URL')"
              :disabled="isLoading"
              @add="addCourse"
              @remove="removeCourse"
              @add-all="addAllCourses"
              @remove-all="removeAllCourses"
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
        </TabPanel>
        <TabPanel value="1">
          <div class="pt-4">
            <AccessUrlBulkAssign
              :items="allItemsForBulk"
              :urls="urls"
              :items-label="t('Course list')"
              :add-label="t('Add courses to selected URLs')"
              :remove-label="t('Remove courses from selected URLs')"
              :confirm-add-message="t('Are you sure you want to add the selected courses to the selected URLs?')"
              :confirm-remove-message="
                t('Are you sure you want to remove the selected courses from the selected URLs?')
              "
              @submit="onBulkSubmit"
            />
          </div>
        </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>
