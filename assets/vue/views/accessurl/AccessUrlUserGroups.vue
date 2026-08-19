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

const availableItems = computed(() => available.value.map((g) => ({ id: g.id, label: g.title })))
const assignedItems = computed(() => assigned.value.map((g) => ({ id: g.id, label: g.title })))
const allItemsForBulk = computed(() => [...assignedItems.value, ...availableItems.value])

async function loadData() {
  isLoading.value = true
  try {
    let data = await accessUrlManageService.listUserGroups(selectedUrlId.value)
    urls.value = data.urls
    if (!selectedUrlId.value && urls.value.length) {
      selectedUrlId.value = urls.value[0].id
      data = await accessUrlManageService.listUserGroups(selectedUrlId.value)
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

function addGroup(item) {
  const group = available.value.find((g) => g.id === item.id)
  available.value = available.value.filter((g) => g.id !== item.id)
  if (group) {
    assigned.value = [...assigned.value, group]
  }
}

function removeGroup(item) {
  const group = assigned.value.find((g) => g.id === item.id)
  assigned.value = assigned.value.filter((g) => g.id !== item.id)
  if (group) {
    available.value = [...available.value, group]
  }
}

function addAllGroups(items) {
  const ids = new Set(items.map((i) => i.id))
  const groupsToMove = available.value.filter((g) => ids.has(g.id))
  available.value = available.value.filter((g) => !ids.has(g.id))
  assigned.value = [...assigned.value, ...groupsToMove]
}

function removeAllGroups() {
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
    await accessUrlManageService.assignUserGroups({
      access_url_id: selectedUrlId.value,
      group_ids: assigned.value.map((g) => g.id),
    })
    notification.showSuccessNotification(t("Update successful"))
  } catch (e) {
    notification.showErrorNotification(e)
  } finally {
    isSaving.value = false
  }
}

async function onBulkSubmit({ itemIds, urlIds, action }) {
  try {
    await accessUrlManageService.bulkUserGroups({
      group_ids: itemIds,
      url_ids: urlIds,
      action,
    })
    notification.showSuccessNotification(
      "add" === action
        ? t("The group now belongs to the selected URL.")
        : t("The group has been removed from the selected URL."),
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
    <SectionHeader :title="t('Edit groups for one URL')">
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
          {{ t("Add group to URL") }}
        </Tab>
      </TabList>
      <TabPanels>
        <TabPanel value="0">
          <div class="flex flex-col gap-4 pt-4">
            <BaseSelect
              id="access-url-usergroups-url"
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
              :available-title="t('Platform groups list')"
              :assigned-title="t('Groups assigned to URL')"
              :disabled="isLoading"
              @add="addGroup"
              @remove="removeGroup"
              @add-all="addAllGroups"
              @remove-all="removeAllGroups"
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
              :items-label="t('User groups list')"
              :add-label="t('Add user group to selected URLs')"
              :remove-label="t('Remove user group from selected URLs')"
              :confirm-add-message="t('Are you sure you want to assign the selected groups to the selected URLs?')"
              :confirm-remove-message="
                t('Are you sure you want to unassign the selected groups from the selected URLs?')
              "
              @submit="onBulkSubmit"
            />
          </div>
        </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>
