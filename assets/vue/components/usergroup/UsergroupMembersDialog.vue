<script setup>
import { ref, watch } from "vue"
import { useI18n } from "vue-i18n"
import BaseDialog from "../basecomponents/BaseDialog.vue"
import BaseTable from "../basecomponents/BaseTable.vue"
import usergroupAdminService from "../../services/usergroupAdminService"

const { t } = useI18n()

const isVisible = defineModel("isVisible", {
  required: true,
  type: Boolean,
})

const props = defineProps({
  usergroupId: {
    type: Number,
    default: null,
  },
  title: {
    type: String,
    default: "",
  },
})

const members = ref([])
const totalItems = ref(0)
const isLoading = ref(false)
const errorMessage = ref("")
const page = ref(1)
const pageSize = ref(10)

async function loadMembers() {
  if (!props.usergroupId) {
    return
  }

  isLoading.value = true
  errorMessage.value = ""
  try {
    const data = await usergroupAdminService.members(props.usergroupId, {
      page: page.value,
      limit: pageSize.value,
    })
    members.value = data.items
    totalItems.value = data.totalItems
  } catch {
    members.value = []
    totalItems.value = 0
    errorMessage.value = t("An error occurred")
  } finally {
    isLoading.value = false
  }
}

function onPage(event) {
  page.value = event.page + 1
  pageSize.value = event.rows
  loadMembers()
}

watch(
  () => [isVisible.value, props.usergroupId],
  ([visible]) => {
    if (visible) {
      page.value = 1
      loadMembers()
    }
  },
  { immediate: true },
)
</script>

<template>
  <BaseDialog
    v-model:is-visible="isVisible"
    :close-label="t('Close')"
    :title="title ? `${t('Class members')}: ${title}` : t('Class members')"
    header-icon="account-group"
  >
    <div
      v-if="errorMessage"
      class="rounded bg-red-100 px-4 py-2 text-red-800 text-sm"
    >
      {{ errorMessage }}
    </div>
    <BaseTable
      v-else
      :is-loading="isLoading"
      :lazy="true"
      :rows="pageSize"
      :text-for-empty="t('No users in this class')"
      :total-items="totalItems"
      :values="members"
      data-key="id"
      @page="onPage"
    >
      <Column
        :header="t('Name')"
        field="name"
      />
      <Column
        :header="t('Username')"
        field="username"
      />
    </BaseTable>
  </BaseDialog>
</template>
