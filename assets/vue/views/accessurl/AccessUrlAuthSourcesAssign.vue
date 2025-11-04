<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import baseService from "../../services/baseService"
import { findAll as listAccessUrl } from "../../services/accessurlService"
import userService from "../../services/userService"
import { useNotification } from "../../composables/notification"

const { t } = useI18n()
const router = useRouter()

const { showErrorNotification, showSuccessNotification } = useNotification()

const accessUrlList = ref([])
const authSourceList = ref([])
const userList = ref([])
const userListTotal = ref(0)

const accessUrl = ref(null)
const authSource = ref(null)
const selectedUsers = ref([])
const isLoadingUserList = ref(true)
const isLoadingAssign = ref(false)

async function listAuthSourcesByAccessUrl({ value: accessUrlIri }) {
  authSourceList.value = []
  authSource.value = null

  try {
    const data = await baseService.get("/access-url/auth-sources/list", { access_url: accessUrlIri })

    authSourceList.value = data.map((methodName) => ({ label: methodName, value: methodName }))
  } catch (error) {
    showErrorNotification(error)
  }
}

async function listUsers({ page, rows }) {
  isLoadingUserList.value = true

  try {
    const { totalItems, items } = await userService.findAll({
      page: page + 1,
      itemsPerPage: rows,
    })

    userListTotal.value = totalItems
    userList.value = items
  } catch (error) {
    showErrorNotification(error)
  } finally {
    isLoadingUserList.value = false
  }
}

async function onPage({ page, rows }) {
  await listUsers({ page, rows })
}

async function assignAuthSources() {
  isLoadingAssign.value = true

  try {
    await baseService.post(
      "/access-url/auth-sources/assign",
      {
        users: selectedUsers.value.map((userInfo) => userInfo["@id"]),
        auth_source: authSource.value,
        access_url: accessUrl.value,
      },
      true,
    )

    showSuccessNotification(t("Auth sources assigned successfully"))

    selectedUsers.value = []
  } catch (e) {
    showErrorNotification(e)
  } finally {
    isLoadingAssign.value = false
  }
}

listAccessUrl().then((items) => (accessUrlList.value = items))
listUsers({ page: 0, rows: 20 })
</script>

<template>
  <SectionHeader :title="t('Assign auth sources to users')" />

  <BaseToolbar>
    <template #start>
      <BaseButton
        :title="t('Back to user assignment page')"
        icon="back"
        only-icon
        type="black"
        @click="router.back()"
      />
    </template>
  </BaseToolbar>

  <div class="grid grid-flow-row-dense md:grid-cols-3 gap-4">
    <div class="md:col-span-2">
      <BaseTable
        v-model:selected-items="selectedUsers"
        :is-loading="isLoadingUserList"
        :total-items="userListTotal"
        :values="userList"
        data-key="@id"
        lazy
        @page="onPage"
      >
        <Column selectionMode="multiple" />

        <Column
          field="fullName"
          :header="t('Full name')"
        />
      </BaseTable>
    </div>

    <div>
      <BaseSelect
        id="access_url"
        v-model="accessUrl"
        :disabled="0 === accessUrlList.length"
        :label="t('Access URL')"
        :options="accessUrlList"
        option-label="url"
        option-value="@id"
        @change="listAuthSourcesByAccessUrl"
      />

      <BaseSelect
        id="auth_source"
        v-model="authSource"
        :disabled="0 === authSourceList.length"
        :label="t('Auth source')"
        :options="authSourceList"
      />

      <BaseButton
        :disabled="!accessUrl || !authSource || 0 === selectedUsers.length || isLoadingAssign"
        :is-loading="isLoadingAssign"
        :label="t('Assign')"
        icon="save"
        type="primary"
        @click="assignAuthSources"
      />
    </div>
  </div>
</template>
