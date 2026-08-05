<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import usergroupAdminService from "../../services/usergroupAdminService"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useConfirmation } from "../../composables/useConfirmation"

const { t } = useI18n()
const { requireConfirmation } = useConfirmation()

const isLoading = ref(false)
const isSaving = ref(false)
const items = ref([])
const totalItems = ref(0)
const csrfToken = ref("")
const search = ref("")
const currentPage = ref(1)
const pageSize = ref(20)

const showDialog = ref(false)
const isEditing = ref(false)
const errorMessage = ref("")
const successMessage = ref("")

const form = ref({
  id: null,
  title: "",
  description: "",
  groupType: 0,
  url: "",
  visibility: "1",
  allowMembersToLeaveGroup: 0,
  pictureUrl: "",
  pictureFile: null,
  deletePicture: false,
})

async function loadData() {
  isLoading.value = true
  errorMessage.value = ""
  try {
    const data = await usergroupAdminService.list({
      page: currentPage.value,
      limit: pageSize.value,
      search: search.value,
    })
    items.value = data.items
    totalItems.value = data.totalItems
    csrfToken.value = data.csrfToken
  } catch {
    errorMessage.value = t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

function onSearch() {
  currentPage.value = 1
  loadData()
}

function onPage(event) {
  currentPage.value = event.page + 1
  pageSize.value = event.rows
  loadData()
}

function openAddDialog() {
  isEditing.value = false
  form.value = {
    id: null,
    title: "",
    description: "",
    groupType: 0,
    url: "",
    visibility: "1",
    allowMembersToLeaveGroup: 0,
    pictureUrl: "",
    pictureFile: null,
    deletePicture: false,
  }
  errorMessage.value = ""
  showDialog.value = true
}

function openEditDialog(item) {
  isEditing.value = true
  form.value = {
    id: item.id,
    title: item.title,
    description: item.description ?? "",
    groupType: item.groupType,
    url: item.url ?? "",
    visibility: item.visibility ?? "1",
    allowMembersToLeaveGroup: item.allowMembersToLeaveGroup ?? 0,
    pictureUrl: item.pictureUrl ?? "",
    pictureFile: null,
    deletePicture: false,
  }
  errorMessage.value = ""
  showDialog.value = true
}

async function saveForm() {
  if (!form.value.title.trim()) {
    errorMessage.value = t("Title is required")
    return
  }

  isSaving.value = true
  errorMessage.value = ""

  try {
    const payload = new FormData()
    payload.append("_token", csrfToken.value)
    payload.append("title", form.value.title.trim())
    payload.append("description", form.value.description)
    payload.append("groupType", String(form.value.groupType))
    payload.append("url", form.value.url)
    payload.append("visibility", form.value.visibility)
    payload.append("allowMembersToLeaveGroup", String(form.value.allowMembersToLeaveGroup))
    if (form.value.pictureFile) {
      payload.append("picture", form.value.pictureFile)
    }
    if (form.value.deletePicture) {
      payload.append("deletePicture", "1")
    }

    if (isEditing.value && form.value.id) {
      await usergroupAdminService.update(form.value.id, payload)
      successMessage.value = t("Update successful")
    } else {
      await usergroupAdminService.create(payload)
      successMessage.value = t("Item added")
    }

    showDialog.value = false
    await loadData()
    setTimeout(() => {
      successMessage.value = ""
    }, 3000)
  } catch (err) {
    if (err.response?.status === 409) {
      errorMessage.value = t("Already exists")
    } else {
      errorMessage.value = t("An error occurred")
    }
  } finally {
    isSaving.value = false
  }
}

function confirmDelete(item) {
  requireConfirmation({
    message: t("Are you sure you want to delete this item?"),
    accept: () => performDelete(item),
  })
}

async function performDelete(item) {
  try {
    await usergroupAdminService.remove(item.id, csrfToken.value)
    successMessage.value = t("Deleted")
    await loadData()
    setTimeout(() => {
      successMessage.value = ""
    }, 3000)
  } catch {
    errorMessage.value = t("An error occurred")
  }
}

function groupTypeLabel(type) {
  return 1 === type ? t("Social") : t("Class")
}

function groupTypeBadgeClass(type) {
  return 1 === type ? "bg-green-100 text-green-700" : "bg-blue-100 text-blue-700"
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <div class="flex flex-col gap-8">
    <SectionHeader :title="t('Classes')">
      <BaseButton
        :label="t('Export')"
        icon="file-export"
        type="primary"
        :to-url="'/admin/usergroups-data/export'"
      />
      <BaseButton
        :label="t('Import classes')"
        icon="file-upload"
        type="success"
        :route="{ name: 'AdminUsergroupImport' }"
      />
      <BaseButton
        :label="t('Import users')"
        icon="join-group"
        type="success"
        :route="{ name: 'AdminUsergroupUserImport' }"
      />
      <BaseButton
        :label="t('Add a class')"
        icon="plus"
        type="success"
        @click="openAddDialog"
      />
    </SectionHeader>

    <div
      v-if="successMessage"
      class="rounded bg-green-100 px-4 py-2 text-green-800 text-sm"
    >
      {{ successMessage }}
    </div>
    <div
      v-if="errorMessage && !showDialog"
      class="rounded bg-red-100 px-4 py-2 text-red-800 text-sm"
    >
      {{ errorMessage }}
    </div>

    <form
      class="flex gap-4 items-end"
      @submit.prevent="onSearch"
    >
      <div class="flex flex-col gap-1 flex-1 max-w-md">
        <input
          v-model="search"
          :placeholder="t('Search classes')"
          class="border border-gray-300 rounded px-3 py-1.5 text-sm"
          type="text"
        />
      </div>
      <BaseButton
        :label="t('Search')"
        icon="search"
        is-submit
      />
    </form>

    <BaseTable
      :values="items"
      :total-items="totalItems"
      :is-loading="isLoading"
      :lazy="true"
      :rows="pageSize"
      @page="onPage"
    >
      <Column
        field="title"
        :header="t('Title')"
        sortable
      />
      <Column :header="t('Users')">
        <template #body="{ data }">
          <router-link
            :to="{ name: 'AdminUsergroupUsers', params: { id: data.id } }"
            class="text-blue-600 hover:underline"
          >
            {{ data.userCount }}
          </router-link>
        </template>
      </Column>
      <Column
        field="courseCount"
        :header="t('Courses')"
      />
      <Column
        field="sessionCount"
        :header="t('Course sessions')"
      />
      <Column :header="t('Type')">
        <template #body="{ data }">
          <span :class="['inline-block rounded px-2 py-0.5 text-xs font-medium', groupTypeBadgeClass(data.groupType)]">
            {{ groupTypeLabel(data.groupType) }}
          </span>
        </template>
      </Column>
      <Column :header="t('Actions')">
        <template #body="{ data }">
          <div class="flex items-center gap-1">
            <BaseButton
              :label="t('Subscribe users to class')"
              icon="join-group"
              only-icon
              size="small"
              type="success"
              :route="{ name: 'AdminUsergroupAddUsers', params: { id: data.id } }"
            />
            <BaseButton
              :label="t('Subscribe class to courses')"
              icon="courses"
              only-icon
              size="small"
              type="success"
              :route="{ name: 'AdminUsergroupAddCourses', params: { id: data.id } }"
            />
            <BaseButton
              :label="t('Subscribe class to sessions')"
              icon="sessions"
              only-icon
              size="small"
              type="success"
              :route="{ name: 'AdminUsergroupAddSessions', params: { id: data.id } }"
            />
            <BaseButton
              :label="t('Preview class members and courses')"
              icon="list"
              only-icon
              size="small"
              type="primary"
              :route="{ name: 'AdminUsergroupPreview', params: { id: data.id } }"
            />
            <BaseButton
              :label="t('Edit')"
              icon="pencil"
              only-icon
              size="small"
              type="secondary-text"
              @click="openEditDialog(data)"
            />
            <BaseButton
              :label="t('Delete')"
              icon="delete"
              only-icon
              size="small"
              type="danger-text"
              @click="confirmDelete(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <!-- Create / Edit dialog -->
    <div
      v-if="showDialog"
      class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
    >
      <div class="bg-white rounded-lg shadow-xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between border-b px-6 py-4">
          <h2 class="text-lg font-semibold text-gray-800">
            {{ isEditing ? t("Edit") : t("Add a class") }}
          </h2>
          <button
            class="text-gray-400 hover:text-gray-600"
            @click="showDialog = false"
          >
            <BaseIcon icon="close" />
          </button>
        </div>

        <div class="px-6 py-4 flex flex-col gap-4">
          <div
            v-if="errorMessage"
            class="rounded bg-red-100 px-4 py-2 text-red-800 text-sm"
          >
            {{ errorMessage }}
          </div>

          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700">
              {{ t("Title") }} <span class="text-red-500">*</span>
            </label>
            <input
              v-model="form.title"
              name="title"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm"
              type="text"
              maxlength="255"
            />
          </div>

          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700">{{ t("Description") }}</label>
            <textarea
              v-model="form.description"
              name="description"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm"
              rows="3"
            />
          </div>

          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700">{{ t("URL") }}</label>
            <input
              v-model="form.url"
              name="url"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm"
              type="text"
            />
          </div>

          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700">{{ t("Group permissions") }}</label>
            <select
              v-model="form.visibility"
              name="visibility"
              class="border border-gray-300 rounded px-3 py-1.5 text-sm"
            >
              <option value="1">{{ t("Open") }}</option>
              <option value="2">{{ t("Closed") }}</option>
            </select>
          </div>

          <div class="flex items-center gap-2">
            <input
              v-model="form.groupType"
              name="groupType"
              :true-value="1"
              :false-value="0"
              class="w-4 h-4"
              type="checkbox"
            />
            <label class="text-sm text-gray-700">{{ t("Social group") }}</label>
          </div>

          <div class="flex items-center gap-2">
            <input
              v-model="form.allowMembersToLeaveGroup"
              name="allowMembersToLeaveGroup"
              :true-value="1"
              :false-value="0"
              class="w-4 h-4"
              type="checkbox"
            />
            <label class="text-sm text-gray-700">{{ t("Allow members to leave group") }}</label>
          </div>

          <div class="flex flex-col gap-1">
            <label class="text-sm font-medium text-gray-700">{{ t("Picture") }}</label>
            <div
              v-if="isEditing && form.pictureUrl && !form.deletePicture"
              class="flex items-center gap-3 mb-1"
            >
              <img
                :src="form.pictureUrl"
                class="w-16 h-16 rounded object-cover border border-gray-200"
                alt=""
              />
              <label class="flex items-center gap-2 text-sm text-gray-600 cursor-pointer">
                <input
                  v-model="form.deletePicture"
                  name="deletePicture"
                  type="checkbox"
                  class="w-4 h-4"
                />
                {{ t("Remove picture") }}
              </label>
            </div>
            <input
              name="picture"
              class="text-sm"
              type="file"
              accept="image/*"
              @change="
                (e) => {
                  form.pictureFile = e.target.files[0] ?? null
                }
              "
            />
          </div>
        </div>

        <div class="flex justify-end gap-3 border-t px-6 py-4">
          <BaseButton
            :label="t('Cancel')"
            type="plain"
            @click="showDialog = false"
          />
          <BaseButton
            :label="isEditing ? t('Save') : t('Add')"
            :is-loading="isSaving"
            :type="isEditing ? 'secondary' : 'success'"
            @click="saveForm"
          />
        </div>
      </div>
    </div>
  </div>
</template>
