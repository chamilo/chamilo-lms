<script setup>
import { ref, computed, onMounted } from "vue"
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"
import usergroupAdminService from "../../services/usergroupAdminService"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useConfirmation } from "../../composables/useConfirmation"

const { t } = useI18n()
const route = useRoute()
const { requireConfirmation } = useConfirmation()

const groupId = computed(() => Number(route.params.id))

const groupTitle = ref("")
const users = ref([])
const csrfToken = ref("")
const isLoading = ref(false)
const errorMessage = ref("")
const successMessage = ref("")

async function loadData() {
  isLoading.value = true
  errorMessage.value = ""
  try {
    const data = await usergroupAdminService.listUsers(groupId.value)
    groupTitle.value = data.groupTitle
    users.value = data.users
    csrfToken.value = data.csrfToken
  } catch {
    errorMessage.value = t("An error occurred. Please try again.")
  } finally {
    isLoading.value = false
  }
}

function confirmRemove(user) {
  requireConfirmation({
    message: t("Are you sure you want to remove this user from the class?"),
    accept: () => performRemove(user),
  })
}

async function performRemove(user) {
  errorMessage.value = ""
  successMessage.value = ""
  try {
    await usergroupAdminService.removeUser(groupId.value, user.id, csrfToken.value)
    successMessage.value = t("User removed")
    users.value = users.value.filter((u) => u.id !== user.id)
    setTimeout(() => {
      successMessage.value = ""
    }, 3000)
  } catch {
    errorMessage.value = t("An error occurred. Please try again.")
  }
}

onMounted(() => {
  loadData()
})
</script>

<template>
  <div class="flex flex-col gap-8">
    <SectionHeader :title="`${t('Users in class')}: ${groupTitle}`">
      <BaseButton
        :label="t('Back')"
        icon="arrow-left"
        type="plain"
        :route="{ name: 'AdminUsergroupList' }"
      />
    </SectionHeader>

    <div
      v-if="successMessage"
      class="rounded bg-green-100 px-4 py-2 text-green-800 text-sm"
    >
      {{ successMessage }}
    </div>
    <div
      v-if="errorMessage"
      class="rounded bg-red-100 px-4 py-2 text-red-800 text-sm"
    >
      {{ errorMessage }}
    </div>

    <BaseTable
      :values="users"
      :is-loading="isLoading"
    >
      <Column
        field="name"
        :header="t('Name')"
        sortable
      />
      <Column :header="t('Actions')">
        <template #body="{ data }">
          <BaseButton
            :label="t('Remove user')"
            icon="delete"
            only-icon
            size="small"
            type="danger-text"
            @click="confirmRemove(data)"
          />
        </template>
      </Column>
      <template #empty>
        <div class="text-center text-gray-500 py-6">
          {{ t("No users in this class") }}
        </div>
      </template>
    </BaseTable>
  </div>
</template>
