<script setup>
import { ref, computed, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import baseService from "../../services/baseService"
import { findAll as listAccessUrl } from "../../services/accessurlService"
import { useNotification } from "../../composables/notification"
import BaseAvatarList from "../../components/basecomponents/BaseAvatarList.vue"
import BaseUserFinder from "../../components/basecomponents/BaseUserFinder.vue"

const { t } = useI18n()
const router = useRouter()

const { showErrorNotification, showSuccessNotification } = useNotification()

const accessUrlList = ref([])
const authSourceList = ref([])

const accessUrl = ref(null)
const authSource = ref(null)
const isLoadingAssign = ref(false)

const userFinder = ref({ selectedUsers: [] })

/** Map of userIri → current auth_source string (or null) for the selected URL */
const currentAuthSourceMap = ref({})

async function fetchCurrentAuthSources(accessUrlIri) {
  currentAuthSourceMap.value = {}
  const users = userFinder.value.selectedUsers
  if (!accessUrlIri || users.length === 0) {
    return
  }

  try {
    const params = new URLSearchParams()
    params.append("access_url", accessUrlIri)
    users.forEach((u) => params.append("users[]", u["@id"]))

    const data = await baseService.get(`/access-url/auth-sources/users-current?${params.toString()}`)
    currentAuthSourceMap.value = data
  } catch (e) {
    // Non-blocking: just skip the display
  }
}

/** Selected users enriched with their current auth_source label for the chosen URL */
const selectedUsersWithAuthSource = computed(() =>
  userFinder.value.selectedUsers.map((user) => {
    const iri = user["@id"]
    const current = currentAuthSourceMap.value[iri]
    return {
      ...user,
      roleLabel: current ? `${t("Current")}: ${current}` : t("No auth source"),
    }
  }),
)

async function listAuthSourcesByAccessUrl({ value: accessUrlIri }) {
  authSourceList.value = []
  authSource.value = null

  try {
    const data = await baseService.get("/access-url/auth-sources/list", { access_url: accessUrlIri })
    authSourceList.value = data.map((methodName) => ({ label: methodName, value: methodName }))
  } catch (error) {
    showErrorNotification(error)
  }

  await fetchCurrentAuthSources(accessUrlIri)
}

// Re-fetch when the selected user list changes while a URL is already chosen.
watch(
  () => userFinder.value.selectedUsers,
  () => {
    if (accessUrl.value) {
      fetchCurrentAuthSources(accessUrl.value)
    }
  },
  { deep: true },
)

async function assignAuthSources() {
  isLoadingAssign.value = true

  try {
    await baseService.post("/access-url/auth-sources/assign", {
      users: userFinder.value.selectedUsers.map((userInfo) => userInfo["@id"]),
      auth_source: authSource.value,
      access_url: accessUrl.value,
    })

    showSuccessNotification(t("Authentication sources assigned successfully"))

    userFinder.value.selectedUsers = []
    currentAuthSourceMap.value = {}
  } catch (e) {
    showErrorNotification(e)
  } finally {
    isLoadingAssign.value = false
  }
}

listAccessUrl().then((items) => (accessUrlList.value = items))
</script>

<template>
  <SectionHeader :title="t('Assign authentication sources to users')" />

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

  <div class="grid grid-flow-row-dense md:grid-cols-5 gap-4">
    <div class="md:col-span-3">
      <BaseUserFinder ref="userFinder" />
    </div>

    <div class="md:col-span-2">
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
        :label="t('Authentication source')"
        :options="authSourceList"
      />

      <div class="field">
        <BaseAvatarList
          :count-several="selectedUsersWithAuthSource.length || 0"
          :users="selectedUsersWithAuthSource"
        />
      </div>

      <BaseButton
        :disabled="!accessUrl || !authSource || 0 === userFinder.selectedUsers.length || isLoadingAssign"
        :is-loading="isLoadingAssign"
        :label="t('Assign')"
        icon="save"
        type="primary"
        @click="assignAuthSources"
      />
    </div>
  </div>
</template>
