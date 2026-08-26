<script setup>
import { ref, computed, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import accessUrlManageService from "../../services/accessUrlManageService"
import { useNotification } from "../../composables/notification"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseDialog from "../../components/basecomponents/BaseDialog.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseTextArea from "../../components/basecomponents/BaseTextArea.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"

const { t } = useI18n()
const notification = useNotification()

const isLoading = ref(false)
const items = ref([])
const myMissingUrls = ref([])
// Creating a URL and registering into every URL are reserved to an unrestricted global
// admin; default to false (hiding those actions) until the backend confirms otherwise.
const canManageAllUrls = ref(false)

const dialogVisible = ref(false)
const isSaving = ref(false)
const editingItem = ref(null)
const form = ref({ url: "https://", description: "", active: false, isLoginOnly: false, parentId: 0 })

// Options for the "Parent URL" selector: every known URL except the one being edited and
// its own descendants (walked client-side via each item's parentId), so the dropdown can't
// offer a cycle. The backend re-validates this regardless.
const parentOptions = computed(() => {
  if (!editingItem.value) {
    return items.value
  }

  const childrenByParent = {}
  for (const item of items.value) {
    if (item.parentId) {
      childrenByParent[item.parentId] ||= []
      childrenByParent[item.parentId].push(item.id)
    }
  }

  const excluded = new Set([editingItem.value.id])
  const queue = [editingItem.value.id]
  while (queue.length) {
    const current = queue.shift()
    for (const childId of childrenByParent[current] || []) {
      if (!excluded.has(childId)) {
        excluded.add(childId)
        queue.push(childId)
      }
    }
  }

  return items.value.filter((item) => !excluded.has(item.id))
})

async function loadData() {
  isLoading.value = true
  try {
    const data = await accessUrlManageService.list()
    items.value = data.items
    myMissingUrls.value = data.myMissingUrls
    canManageAllUrls.value = data.canManageAllUrls
  } catch (e) {
    notification.showErrorNotification(e)
  } finally {
    isLoading.value = false
  }
}

function openCreateDialog() {
  editingItem.value = null
  form.value = { url: "https://", description: "", active: false, isLoginOnly: false, parentId: 0 }
  dialogVisible.value = true
}

function openEditDialog(item) {
  editingItem.value = item
  form.value = {
    url: item.url,
    description: item.description,
    active: item.active,
    isLoginOnly: item.isLoginOnly,
    parentId: item.parentId || 0,
  }
  dialogVisible.value = true
}

async function save() {
  isSaving.value = true
  try {
    const payload = { ...form.value }
    const response = editingItem.value
      ? await accessUrlManageService.update(editingItem.value.id, payload)
      : await accessUrlManageService.create(payload)

    if (response?.error) {
      notification.showErrorNotification(response.error)
      return
    }

    if (response?.warning) {
      notification.showWarningNotification(t(response.warning))
    }

    notification.showSuccessNotification(editingItem.value ? t("The URL has been edited") : t("The URL has been added"))
    dialogVisible.value = false
    await loadData()
  } catch (e) {
    notification.showErrorNotification(e)
  } finally {
    isSaving.value = false
  }
}

async function toggleStatus(item) {
  try {
    if (item.active) {
      await accessUrlManageService.lock(item.id)
      notification.showSuccessNotification(t("The URL has been disabled"))
    } else {
      await accessUrlManageService.unlock(item.id)
      notification.showSuccessNotification(t("The URL has been enabled"))
    }
    await loadData()
  } catch (e) {
    notification.showErrorNotification(e)
  }
}

async function registerAdmin() {
  try {
    await accessUrlManageService.registerAdmin()
    notification.showSuccessNotification(t("Admin user assigned to this URL"))
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
    <SectionHeader :title="t('Multiple access URL / Branding')">
      <BaseButton
        :label="t('Back')"
        icon="back"
        type="plain"
        :route="{ name: 'AdminMultiUrlList' }"
      />
      <BaseButton
        v-if="canManageAllUrls"
        :label="t('Add URL')"
        icon="plus"
        type="success"
        @click="openCreateDialog"
      />
      <BaseButton
        :label="t('Manage users')"
        icon="account"
        type="secondary"
        :route="{ name: 'AccessUrlUsers' }"
      />
      <BaseButton
        :label="t('Manage courses')"
        icon="courses"
        type="secondary"
        :route="{ name: 'AccessUrlCourses' }"
      />
      <BaseButton
        :label="t('Manage user groups')"
        icon="account-group"
        type="secondary"
        :route="{ name: 'AccessUrlUserGroups' }"
      />
      <BaseButton
        :label="t('Manage course categories')"
        icon="file-tree-outline"
        type="secondary"
        :route="{ name: 'AccessUrlCourseCategories' }"
      />
    </SectionHeader>

    <div
      v-if="myMissingUrls.length && canManageAllUrls"
      class="rounded bg-orange-100 text-orange-800 px-4 py-3 text-sm flex items-center justify-between gap-4 flex-wrap"
    >
      <span>{{ t("Admin user should be registered here") }}: {{ myMissingUrls.map((u) => u.url).join(", ") }}</span>
      <BaseButton
        :label="t('Click here to register the admin into all sites')"
        type="secondary"
        @click="registerAdmin"
      />
    </div>

    <BaseTable
      :values="items"
      :total-items="items.length"
      :is-loading="isLoading"
      :lazy="false"
      :text-for-empty="t('No results found')"
    >
      <Column
        field="url"
        :header="t('URL')"
      >
        <template #body="{ data }">
          <span :style="{ paddingLeft: `${data.depth * 24}px` }">
            <a
              :href="data.url"
              target="_blank"
              rel="noopener noreferrer"
              class="text-blue-600 hover:underline"
            >
              {{ data.url }}
            </a>
          </span>
        </template>
      </Column>
      <Column
        field="description"
        :header="t('Description')"
      />
      <Column :header="t('Active')">
        <template #body="{ data }">
          <button
            v-if="!data.isDefault && canManageAllUrls"
            type="button"
            @click="toggleStatus(data)"
          >
            <BaseIcon :icon="data.active ? 'toggle-switch' : 'toggle-switch-off'" />
          </button>
          <BaseIcon
            v-else
            :icon="data.active ? 'toggle-switch' : 'toggle-switch-off'"
          />
        </template>
      </Column>
      <Column :header="t('Login-only URL')">
        <template #body="{ data }">{{ data.isLoginOnly ? t("Yes") : t("No") }}</template>
      </Column>
      <Column :header="t('Created at')">
        <template #body="{ data }">{{ data.tms }}</template>
      </Column>
      <Column
        v-if="canManageAllUrls"
        :header="t('Edit')"
      >
        <template #body="{ data }">
          <div class="flex gap-1 flex-nowrap">
            <BaseButton
              :label="t('Edit')"
              icon="pencil"
              only-icon
              size="small"
              type="secondary-text"
              @click="openEditDialog(data)"
            />
            <BaseButton
              v-if="!data.isDefault"
              :label="t('Delete')"
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              :route="{ name: 'AccessUrlDelete', params: { id: data.id }, query: { url: data.url } }"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <BaseDialog
      v-model:is-visible="dialogVisible"
      :title="editingItem ? t('Edit URL') : t('Add URL')"
      header-icon="globe"
    >
      <div class="flex flex-col gap-4 p-4 min-w-[24rem]">
        <BaseInputText
          id="access-url-url"
          v-model="form.url"
          name="url"
          :label="t('URL')"
          type="url"
        />
        <BaseTextArea
          id="access-url-description"
          v-model="form.description"
          :label="t('Description')"
        />
        <BaseSelect
          v-if="!editingItem || !editingItem.isDefault"
          id="access-url-parent"
          v-model="form.parentId"
          name="parentId"
          :label="t('Parent URL')"
          :options="parentOptions"
          option-label="url"
          option-value="id"
          allow-clear
        />
        <BaseCheckbox
          v-if="editingItem && !editingItem.isDefault"
          id="access-url-active"
          v-model="form.active"
          name="active"
          :label="t('active')"
        />
        <BaseCheckbox
          id="access-url-login-only"
          v-model="form.isLoginOnly"
          name="isLoginOnly"
          :label="t('Login-only URL')"
        />
        <div class="flex justify-end gap-4 mt-2">
          <BaseButton
            :label="t('Cancel')"
            type="plain"
            @click="dialogVisible = false"
          />
          <BaseButton
            :label="t('Save')"
            type="success"
            :is-loading="isSaving"
            @click="save"
          />
        </div>
      </div>
    </BaseDialog>
  </div>
</template>
