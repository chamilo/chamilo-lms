<template>
  <div>
    <SectionHeader :title="t('Data exchanges (GDPR)')">
      <BaseButton
        :label="t('Back to third parties')"
        icon="arrow-left"
        type="secondary"
        class="mr-2"
        @click="goBack"
      />
      <BaseButton
        :label="t('Add exchange')"
        icon="plus"
        type="success"
        @click="openCreate"
      />
    </SectionHeader>

    <p v-if="selectedThirdPartyName" class="text-h3 text-gray-600 mb-4 ml-1">
      {{ selectedThirdPartyName }}
    </p>

    <BaseTable :is-loading="loading" :values="exchanges" data-key="id">
      <Column :header="t('Third party')">
        <template #body="{ data }">
          {{
            thirdParties.find((tp) => `/api/third_parties/${tp.id}` === data.thirdParty)?.title ||
            '-'
          }}
        </template>
      </Column>

      <Column :header="t('Date sent')" field="sentAt" />

      <Column :header="t('All users?')">
        <template #body="{ data }">
          {{ data.allUsers ? t('Yes') : t('No') }}
        </template>
      </Column>
      <Column
        :header="t('Users')"
        v-if="exchanges.some((e) => !e.allUsers)"
      >
        <template #body="{ data }">
          <div v-if="!data.allUsers && exchangeUsersMap[data['@id']]" class="flex flex-wrap gap-1">
            <span
              v-for="(user, index) in exchangeUsersMap[data['@id']].slice(0, 3)"
              :key="index"
              class="bg-support-1 text-gray-90 text-xs font-medium px-2 py-0.5 rounded-full"
            >
              {{ user.firstname }} {{ user.lastname }}
            </span>
            <span v-if="exchangeUsersMap[data['@id']].length > 3" class="text-xs text-gray-600">
              +{{ exchangeUsersMap[data['@id']].length - 3 }} more
            </span>
          </div>
          <span v-else>-</span>
        </template>
      </Column>

      <Column :header="t('Actions')">
        <template #body="{ data }">
          <div class="flex gap-2 flex-wrap">
            <BaseButton
              size="small"
              type="secondary"
              icon="edit"
              :label="t('Edit')"
              @click="openEdit(data)"
            />
            <BaseButton
              size="small"
              type="danger"
              icon="delete"
              :label="t('Delete')"
              @click="openDelete(data)"
            />
          </div>
        </template>
      </Column>
    </BaseTable>

    <BaseDialogConfirmCancel
      v-model:is-visible="showDialog"
      :title="dialogTitle"
      :confirm-label="t('Save')"
      :cancel-label="t('Cancel')"
      @confirm-clicked="submitExchange"
      @cancel-clicked="resetForm"
    >
      <div class="p-fluid">
        <BaseCalendar
          id="sentAt"
          :label="t('Date sent')"
          v-model="form.sentAt"
          showIcon
        />

        <BaseTinyEditor
          v-model="form.description"
          editor-id="exchange-description"
          :title="t('Description')"
          :required="true"
          :editor-config="{
            height: 150,
            toolbar: 'bold italic underline | removeformat',
          }"
        />

        <BaseCheckbox
          id="allUsers"
          name="allUsers"
          :label="t('All users?')"
          v-model="form.allUsers"
        />

        <BaseMultiSelect
          v-show="!form.allUsers"
          input-id="selectedUsers"
          :label="t('Select users')"
          v-model="form.selectedUsers"
          :options="users"
        />
      </div>
    </BaseDialogConfirmCancel>

    <BaseDialogConfirmCancel
      v-model:is-visible="showDeleteDialog"
      :title="t('Delete')"
      :confirm-label="t('Delete')"
      :cancel-label="t('Cancel')"
      @confirm-clicked="confirmDelete"
    >
      <p class="p-3">
        {{ t('Are you sure you want to delete this?') }}
      </p>
    </BaseDialogConfirmCancel>
  </div>
</template>
<script setup>
import { ref, onMounted, watch, computed } from "vue"
import { useRoute, useRouter } from "vue-router"
import { useI18n } from "vue-i18n"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseDialogConfirmCancel from "../../components/basecomponents/BaseDialogConfirmCancel.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseCalendar from "../../components/basecomponents/BaseCalendar.vue"
import BaseMultiSelect from "../../components/basecomponents/BaseMultiSelect.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import { useNotification } from "../../composables/notification"
import adminService from "../../services/adminService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { showSuccessNotification, showErrorNotification } = useNotification()

const exchanges = ref([])
const thirdParties = ref([])
const users = ref([])

const loading = ref(false)
const exchangeUsersMap = ref({})
const thirdPartyId = computed(() => parseInt(route.query.thirdPartyId))

const showDialog = ref(false)
const isEditing = ref(false)
const editingExchangeIri = ref(null)

const showDeleteDialog = ref(false)
const deletingExchangeIri = ref(null)

const form = ref({
  thirdPartyId: null,
  sentAt: new Date(),
  description: "",
  allUsers: true,
  selectedUsers: [],
})

const dialogTitle = computed(() =>
  isEditing.value ? t("Edit") : t("Add"),
)

const selectedThirdPartyName = computed(() => {
  return thirdParties.value.find((tp) => tp.id === thirdPartyId.value)?.title || null
})

const resetForm = () => {
  form.value = {
    thirdPartyId: thirdPartyId.value,
    sentAt: new Date(),
    description: "",
    allUsers: true,
    selectedUsers: [],
  }
  isEditing.value = false
  editingExchangeIri.value = null
  showDialog.value = false
}

const fetchThirdParties = async () => {
  try {
    thirdParties.value = await adminService.fetchThirdParties()
  } catch (e) {
    console.error("Error loading third parties", e)
  }
}

const fetchExchanges = async () => {
  try {
    loading.value = true
    exchanges.value = await adminService.fetchExchanges(thirdPartyId.value)
  } catch (e) {
    console.error("Error loading exchanges", e)
  } finally {
    loading.value = false
  }
}

const fetchUsers = async () => {
  try {
    const userList = await adminService.fetchUsers()
    users.value = userList.map((u) => ({
      id: u.id,
      name: u.fullName || `${u.firstname} ${u.lastname}`,
      firstname: u.firstname,
      lastname: u.lastname,
    }))
  } catch (e) {
    console.error("Error loading users", e)
  }
}

const fetchExchangeUsers = async () => {
  try {
    const all = await adminService.fetchExchangeUsers()
    const grouped = {}
    all.forEach((item) => {
      const key = item.dataExchange
      if (!grouped[key]) grouped[key] = []
      const fullUser = users.value.find((u) => `/api/users/${u.id}` === item.user)
      grouped[key].push({
        ...(fullUser || { firstname: "?", lastname: "?", id: null, name: "?" }),
        _relId: item.id || item["@id"],
        _userIri: item.user,
      })
    })
    exchangeUsersMap.value = grouped
  } catch (e) {
    console.error("Error loading exchange users", e)
  }
}

const openCreate = () => {
  resetForm()
  isEditing.value = false
  showDialog.value = true
}

const openEdit = (row) => {
  isEditing.value = true
  editingExchangeIri.value = row["@id"] || `/api/third_party_data_exchanges/${row.id}`

  form.value.thirdPartyId = thirdPartyId.value
  form.value.sentAt = row.sentAt ? new Date(row.sentAt) : new Date()
  form.value.description = row.description || ""
  form.value.allUsers = !!row.allUsers

  if (!form.value.allUsers) {
    const assigned = exchangeUsersMap.value[editingExchangeIri.value] || []
    form.value.selectedUsers = assigned.map((u) => u.id).filter((id) => id != null)
  } else {
    form.value.selectedUsers = []
  }

  showDialog.value = true
}

const submitExchange = async () => {
  try {
    const { thirdPartyId: tpId, sentAt, description, allUsers, selectedUsers } = form.value

    if (!allUsers && selectedUsers.length === 0) {
      showErrorNotification(t("Please select at least one user."))
      return
    }

    const payload = {
      thirdParty: `/api/third_parties/${tpId}`,
      sentAt,
      description,
      allUsers,
    }

    if (isEditing.value) {
      await adminService.updateExchange(editingExchangeIri.value, payload)

      const current = exchangeUsersMap.value[editingExchangeIri.value] || []
      const currentIds = new Set(current.map((u) => u.id).filter((id) => id != null))
      const newIds = new Set(selectedUsers)

      const toAdd = [...newIds].filter((id) => !currentIds.has(id))
      if (!allUsers && toAdd.length > 0) {
        const addPayload = toAdd.map((userId) => ({
          user: `/api/users/${userId}`,
          dataExchange: editingExchangeIri.value,
        }))
        await adminService.assignExchangeUsers(addPayload)
      }

      const toRemoveIds = [...currentIds].filter((id) => !newIds.has(id))
      if (toRemoveIds.length > 0) {
        const toRemoveRels = current
          .filter((u) => toRemoveIds.includes(u.id))
          .map((u) => u._relId)
          .filter(Boolean)
        await Promise.all(toRemoveRels.map((rel) => adminService.deleteExchangeUser(rel)))
      }

      showSuccessNotification(t("Data exchange updated successfully."))
    } else {
      const exchange = await adminService.createExchange(payload)

      if (!allUsers && selectedUsers.length > 0) {
        const userPayload = selectedUsers.map((userId) => ({
          user: `/api/users/${userId}`,
          dataExchange: exchange["@id"],
        }))
        await adminService.assignExchangeUsers(userPayload)
      }

      showSuccessNotification(t("Data exchange successfully saved."))
    }

    await fetchExchanges()
    await fetchExchangeUsers()
    resetForm()
  } catch (error) {
    console.error("Error saving data exchange", error)
    showErrorNotification(t("Error saving data exchange."))
  }
}

const openDelete = (row) => {
  deletingExchangeIri.value = row["@id"] || `/api/third_party_data_exchanges/${row.id}`
  showDeleteDialog.value = true
}

const confirmDelete = async () => {
  try {
    await adminService.deleteExchange(deletingExchangeIri.value)
    showSuccessNotification(t("Deleted"))
    showDeleteDialog.value = false
    deletingExchangeIri.value = null
    await fetchExchanges()
    await fetchExchangeUsers()
  } catch (e) {
    console.error(e)
    showErrorNotification(t("Error deleting data exchange."))
  }
}

onMounted(async () => {
  if (!thirdPartyId.value) {
    showErrorNotification("Missing third party context.")
    router.push({ name: "ThirdPartyManager" })
    return
  }
  await fetchThirdParties()
  await fetchUsers()
  await fetchExchanges()
  await fetchExchangeUsers()
})

watch(
  () => route.query.thirdPartyId,
  async () => {
    await fetchExchanges()
    await fetchExchangeUsers()
  },
)

watch([thirdParties, thirdPartyId], () => {
  if (thirdPartyId.value && thirdParties.value.length > 0) {
    const match = thirdParties.value.find((tp) => tp.id === thirdPartyId.value)
    if (match) {
      form.value.thirdPartyId = thirdPartyId.value
      showDialog.value = true
    }
  }
})

watch(showDialog, (newVal) => {
  if (!newVal) resetForm()
})

const goBack = () => {
  router.push({ name: "ThirdPartyManager" })
}
</script>
