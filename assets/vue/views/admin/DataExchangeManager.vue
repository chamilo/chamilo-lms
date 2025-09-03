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
        @click="showDialog = true"
      />
    </SectionHeader>

    <p
      v-if="selectedThirdPartyName"
      class="text-h3 text-gray-600 mb-4 ml-1"
    >
      {{ selectedThirdPartyName }}
    </p>

    <BaseTable
      :is-loading="loading"
      :values="exchanges"
      data-key="id"
    >
      <Column :header="t('Third party')">
        <template #body="{ data }">
          {{ thirdParties.find((tp) => `/api/third_parties/${tp.id}` === data.thirdParty)?.name || "-" }}
        </template>
      </Column>
      <Column
        :header="t('Date sent')"
        field="sentAt"
      />
      <Column :header="t('All users?')">
        <template #body="{ data }">
          {{ data.allUsers ? t("Yes") : t("No") }}
        </template>
      </Column>
      <Column
        :header="t('Users')"
        v-if="exchanges.some((e) => !e.allUsers)"
      >
        <template #body="{ data }">
          <div
            v-if="!data.allUsers && exchangeUsersMap[data['@id']]"
            class="flex flex-wrap gap-1"
          >
            <span
              v-for="(user, index) in exchangeUsersMap[data['@id']].slice(0, 3)"
              :key="index"
              class="bg-support-1 text-gray-90 text-xs font-medium px-2 py-0.5 rounded-full"
            >
              {{ user.firstname }} {{ user.lastname }}
            </span>
            <span
              v-if="exchangeUsersMap[data['@id']].length > 3"
              class="text-xs text-gray-600"
            >
              +{{ exchangeUsersMap[data["@id"]].length - 3 }} more
            </span>
          </div>
          <span v-else>-</span>
        </template>
      </Column>
    </BaseTable>

    <BaseDialogConfirmCancel
      v-model:is-visible="showDialog"
      :title="t('Add data exchange')"
      :confirm-label="t('Save')"
      :cancel-label="t('Cancel')"
      @confirm-clicked="saveExchange"
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
const exchanges = ref([])
const thirdParties = ref([])
const users = ref([])
const showDialog = ref(false)
const loading = ref(false)
const exchangeUsersMap = ref({})
const { showSuccessNotification, showErrorNotification } = useNotification()
const thirdPartyId = computed(() => parseInt(route.query.thirdPartyId))

const form = ref({
  thirdPartyId: null,
  sentAt: new Date(),
  description: "",
  allUsers: true,
  selectedUsers: [],
})

const selectedThirdPartyName = computed(() => {
  return thirdParties.value.find((tp) => tp.id === thirdPartyId.value)?.name || null
})

const resetForm = () => {
  form.value = {
    thirdPartyId: thirdPartyId.value,
    sentAt: new Date(),
    description: "",
    allUsers: true,
    selectedUsers: [],
  }
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
    exchanges.value = await adminService.fetchExchanges(thirdPartyId.value)
  } catch (e) {
    console.error("Error loading exchanges", e)
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
      grouped[key].push(fullUser || { firstname: "?", lastname: "?" })
    })
    exchangeUsersMap.value = grouped
  } catch (e) {
    console.error("Error loading exchange users", e)
  }
}

const saveExchange = async () => {
  try {
    const { thirdPartyId, sentAt, description, allUsers, selectedUsers } = form.value

    if (!allUsers && selectedUsers.length === 0) {
      showErrorNotification(t("Please select at least one user."))
      return
    }

    const exchangePayload = {
      thirdParty: `/api/third_parties/${thirdPartyId}`,
      sentAt,
      description,
      allUsers,
    }

    const exchange = await adminService.createExchange(exchangePayload)

    if (!allUsers && selectedUsers.length > 0) {
      const userPayload = selectedUsers.map((userId) => ({
        user: `/api/users/${userId}`,
        dataExchange: exchange["@id"],
      }))
      await adminService.assignExchangeUsers(userPayload)
    }

    showSuccessNotification(t("Data exchange successfully saved."))
    await fetchExchanges()
    await fetchExchangeUsers()
    showDialog.value = false
    resetForm()
  } catch (error) {
    console.error("Error saving data exchange", error)
    showErrorNotification(t("Error saving data exchange."))
  }
}

onMounted(() => {
  if (!thirdPartyId.value) {
    showErrorNotification("Missing third party context.")
    router.push({ name: "ThirdPartyManager" })
    return
  }

  fetchExchanges()
  fetchThirdParties()
  fetchUsers()
  fetchExchangeUsers()
})

watch(
  () => route.query.thirdPartyId,
  () => {
    fetchExchanges()
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
