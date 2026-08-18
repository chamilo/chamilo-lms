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

const bulkUsers = ref([])
const bulkFirstLetter = ref("")
const isBulkLoading = ref(false)
const alphabetOptions = [
  { value: "__all__", label: "--" },
  ...Array.from({ length: 26 }, (_, i) => {
    const letter = String.fromCharCode(65 + i)
    return { value: letter, label: letter }
  }),
]

const availableItems = computed(() =>
  available.value.map((u) => ({ id: u.id, label: `${u.username} - ${u.firstname} ${u.lastname}` })),
)
const assignedItems = computed(() =>
  assigned.value.map((u) => ({ id: u.id, label: `${u.username} - ${u.firstname} ${u.lastname}` })),
)
const bulkItems = computed(() =>
  bulkUsers.value.map((u) => ({ id: u.id, label: `${u.username} - ${u.firstname} ${u.lastname}` })),
)

async function loadData() {
  isLoading.value = true
  try {
    let data = await accessUrlManageService.listUsers(selectedUrlId.value)
    urls.value = data.urls
    if (!selectedUrlId.value && urls.value.length) {
      selectedUrlId.value = urls.value[0].id
      data = await accessUrlManageService.listUsers(selectedUrlId.value)
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

async function loadBulkUsers() {
  isBulkLoading.value = true
  try {
    const data = await accessUrlManageService.listAllUsers(bulkFirstLetter.value)
    bulkUsers.value = data.items
    bulkFirstLetter.value = data.appliedFirstLetter
  } catch (e) {
    notification.showErrorNotification(e)
  } finally {
    isBulkLoading.value = false
  }
}

function addUser(item) {
  const user = available.value.find((u) => u.id === item.id)
  available.value = available.value.filter((u) => u.id !== item.id)
  if (user) {
    assigned.value = [...assigned.value, user]
  }
}

function removeUser(item) {
  const user = assigned.value.find((u) => u.id === item.id)
  assigned.value = assigned.value.filter((u) => u.id !== item.id)
  if (user) {
    available.value = [...available.value, user]
  }
}

function addAllUsers(items) {
  const ids = new Set(items.map((i) => i.id))
  const usersToMove = available.value.filter((u) => ids.has(u.id))
  available.value = available.value.filter((u) => !ids.has(u.id))
  assigned.value = [...assigned.value, ...usersToMove]
}

function removeAllUsers() {
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
    await accessUrlManageService.assignUsers({
      access_url_id: selectedUrlId.value,
      user_ids: assigned.value.map((u) => u.id),
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
    await accessUrlManageService.bulkUsers({
      user_ids: itemIds,
      url_ids: urlIds,
      action,
    })
    notification.showSuccessNotification(
      "add" === action
        ? t("The user accounts are now attached to the URL")
        : t("The user accounts have been unassigned from the URL"),
    )
    await loadData()
  } catch (e) {
    notification.showErrorNotification(e)
  }
}

onMounted(() => {
  loadData()
  loadBulkUsers()
})
</script>

<template>
  <div class="flex flex-col gap-6">
    <SectionHeader :title="t('Edit users and URLs')">
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
          {{ t("Add users to an URL") }}
        </Tab>
      </TabList>
      <TabPanels>
        <TabPanel value="0">
          <div class="flex flex-col gap-4 pt-4">
            <BaseSelect
              id="access-url-users-url"
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
              :available-title="t('Available users')"
              :assigned-title="t('Assigned users')"
              :disabled="isLoading"
              @add="addUser"
              @remove="removeUser"
              @add-all="addAllUsers"
              @remove-all="removeAllUsers"
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
          <div class="flex flex-col gap-4 pt-4">
            <BaseSelect
              id="access-url-users-bulk-first-letter"
              v-model="bulkFirstLetter"
              :label="t('First letter')"
              :options="alphabetOptions"
              option-label="label"
              option-value="value"
              class="max-w-xs"
              @update:model-value="loadBulkUsers"
            />

            <AccessUrlBulkAssign
              :items="bulkItems"
              :urls="urls"
              :disabled="isBulkLoading"
              :items-label="t('User list')"
              :add-label="t('Add users to selected URLs')"
              :remove-label="t('Remove users from selected URLs')"
              :confirm-add-message="t('Are you sure you want to assign the selected users to the selected URLs?')"
              :confirm-remove-message="
                t('Are you sure you want to unassign the selected users from the selected URLs?')
              "
              @submit="onBulkSubmit"
            />
          </div>
        </TabPanel>
      </TabPanels>
    </Tabs>
  </div>
</template>
