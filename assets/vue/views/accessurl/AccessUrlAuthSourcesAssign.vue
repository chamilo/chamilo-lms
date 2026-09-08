<script setup>
import { ref, computed, watch } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseToolbar from "../../components/basecomponents/BaseToolbar.vue"
import BaseSelect from "../../components/basecomponents/BaseSelect.vue"
import BaseMultiSelect from "../../components/basecomponents/BaseMultiSelect.vue"
import baseService from "../../services/baseService"
import { findAll as listAccessUrl } from "../../services/accessurlService"
import { useNotification } from "../../composables/notification"
import { useConfirmation } from "../../composables/useConfirmation"
import BaseAvatarList from "../../components/basecomponents/BaseAvatarList.vue"
import BaseUserFinder from "../../components/basecomponents/BaseUserFinder.vue"

const { t } = useI18n()
const router = useRouter()

const { showErrorNotification, showSuccessNotification } = useNotification()
const { requireConfirmation } = useConfirmation()

const accessUrlList = ref([])
const authSourceList = ref([])

const accessUrl = ref(null)
/** Authentication methods to apply to every selected user (replaces their current set for this URL) */
const authSources = ref([])
const isLoadingAssign = ref(false)

const userFinder = ref({ selectedUsers: [] })

/** Map of userIri → current auth_source strings (possibly several) for the selected URL */
const currentAuthSourceMap = ref({})

/** Pre-fill the multi-select with the single selected user's current sources; leave it
 * untouched for 0 or several users, since there is no single "current set" to show.
 * Filtered to methods still offered by authSourceList: a method the user was assigned
 * while it was configured (e.g. LDAP, later disabled) has no matching option to render
 * a label for, and resubmitting it verbatim would fail the backend's allow-list check. */
function syncAuthSourcesSelection() {
  const users = userFinder.value.selectedUsers
  if (users.length !== 1) {
    authSources.value = []
    return
  }

  const current = currentAuthSourceMap.value[users[0]["@id"]] ?? []
  const availableValues = new Set(authSourceList.value.map((option) => option.value))
  authSources.value = current.filter((authentication) => availableValues.has(authentication))
}

async function fetchCurrentAuthSources(accessUrlIri) {
  currentAuthSourceMap.value = {}
  const users = userFinder.value.selectedUsers
  if (!accessUrlIri || users.length === 0) {
    syncAuthSourcesSelection()
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

  syncAuthSourcesSelection()
}

/** Selected users enriched with their current auth_source label for the chosen URL */
const selectedUsersWithAuthSource = computed(() =>
  userFinder.value.selectedUsers.map((user) => {
    const iri = user["@id"]
    const current = currentAuthSourceMap.value[iri]
    return {
      ...user,
      roleLabel: current?.length ? `${t("Current")}: ${current.join(", ")}` : t("No auth source"),
    }
  }),
)

async function listAuthSourcesByAccessUrl({ value: accessUrlIri }) {
  authSourceList.value = []
  authSources.value = []

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

function assignAuthSources() {
  if (authSources.value.length === 0) {
    requireConfirmation({
      message: t(
        "No authentication method is selected. This removes every authentication method for the selected user(s) on this URL, which may prevent them from logging in. Continue?",
      ),
      accept: () => performAssignAuthSources(),
    })
    return
  }

  performAssignAuthSources()
}

async function performAssignAuthSources() {
  isLoadingAssign.value = true

  try {
    await baseService.post("/access-url/auth-sources/assign", {
      users: userFinder.value.selectedUsers.map((userInfo) => userInfo["@id"]),
      auth_sources: authSources.value,
      access_url: accessUrl.value,
    })

    showSuccessNotification(t("Authentication sources assigned successfully"))

    userFinder.value.selectedUsers = []
    currentAuthSourceMap.value = {}
    authSources.value = []
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

      <BaseMultiSelect
        v-model="authSources"
        input-id="auth_source"
        :label="t('Authentication source')"
        :options="authSourceList"
        option-label="label"
        option-value="value"
      />

      <div class="field">
        <BaseAvatarList
          :count-several="selectedUsersWithAuthSource.length || 0"
          :users="selectedUsersWithAuthSource"
        />
      </div>

      <BaseButton
        :disabled="!accessUrl || 0 === userFinder.selectedUsers.length || isLoadingAssign"
        :is-loading="isLoadingAssign"
        :label="t('Assign')"
        icon="save"
        type="primary"
        @click="assignAuthSources"
      />
    </div>
  </div>
</template>
