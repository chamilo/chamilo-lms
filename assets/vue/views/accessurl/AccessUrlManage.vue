<script setup>
import { ref, onMounted } from "vue"
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

const { t } = useI18n()
const notification = useNotification()

const isLoading = ref(false)
const items = ref([])
const myMissingUrls = ref([])

const dialogVisible = ref(false)
const isSaving = ref(false)
const editingItem = ref(null)
const form = ref({ url: "https://", description: "", active: false, isLoginOnly: false })

async function loadData() {
  isLoading.value = true
  try {
    const data = await accessUrlManageService.list()
    items.value = data.items
    myMissingUrls.value = data.myMissingUrls
  } catch (e) {
    notification.showErrorNotification(e)
  } finally {
    isLoading.value = false
  }
}

function openCreateDialog() {
  editingItem.value = null
  form.value = { url: "https://", description: "", active: false, isLoginOnly: false }
  dialogVisible.value = true
}

function openEditDialog(item) {
  editingItem.value = item
  form.value = {
    url: item.url,
    description: item.description,
    active: item.active,
    isLoginOnly: item.isLoginOnly,
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
      v-if="myMissingUrls.length"
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
        sortable
      >
        <template #body="{ data }">
          <a
            :href="data.url"
            target="_blank"
            rel="noopener noreferrer"
            class="text-blue-600 hover:underline"
          >
            {{ data.url }}
          </a>
        </template>
      </Column>
      <Column
        field="description"
        :header="t('Description')"
      />
      <Column :header="t('Active')">
        <template #body="{ data }">
          <button
            v-if="!data.isDefault"
            type="button"
            @click="toggleStatus(data)"
          >
            <BaseIcon :icon="data.active ? 'toggle-switch' : 'toggle-switch-off'" />
          </button>
          <BaseIcon
            v-else
            icon="toggle-switch"
          />
        </template>
      </Column>
      <Column :header="t('Login-only URL')">
        <template #body="{ data }">{{ data.isLoginOnly ? t("Yes") : t("No") }}</template>
      </Column>
      <Column :header="t('Created at')">
        <template #body="{ data }">{{ data.tms }}</template>
      </Column>
      <Column :header="t('Edit')">
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
          :label="t('URL')"
          type="url"
        />
        <BaseTextArea
          id="access-url-description"
          v-model="form.description"
          :label="t('Description')"
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
