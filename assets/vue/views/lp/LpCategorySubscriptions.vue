<script setup>
import { computed, onMounted, ref } from "vue"
import { useI18n } from "vue-i18n"
import { useRoute, useRouter } from "vue-router"
import MultiSelect from "primevue/multiselect"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import { useNotification } from "../../composables/notification"
import lpService from "../../services/lpService"

const { t } = useI18n()
const route = useRoute()
const router = useRouter()
const { showErrorNotification, showSuccessNotification } = useNotification()

const isLoading = ref(false)
const isSaving = ref(false)
const subscriptionData = ref(null)
const activeSection = ref("users")
const selectedUserIds = ref([])
const selectedGroupIds = ref([])
const selectedUserGroupIds = ref([])

const categoryId = computed(() => Number(route.params.categoryId || 0))
const contextParams = computed(() => ({
  cid: Number(route.query.cid || 0),
  sid: Number(route.query.sid || 0),
  gid: Number(route.query.gid || 0),
}))

const sections = computed(() => {
  const values = [
    {
      id: "users",
      label: t("Subscribe users to category"),
      fieldLabel: t("Users"),
      options: subscriptionData.value?.users || [],
      selectedIds: selectedUserIds.value,
      inputName: "users",
    },
    {
      id: "groups",
      label: t("Subscribe groups to category"),
      fieldLabel: t("Groups"),
      options: subscriptionData.value?.groups || [],
      selectedIds: selectedGroupIds.value,
      inputName: "groups",
    },
  ]

  if (subscriptionData.value?.allowUserGroups) {
    values.push({
      id: "usergroups",
      label: t("Subscribe classes to category"),
      fieldLabel: t("Classes"),
      options: subscriptionData.value?.userGroups || [],
      selectedIds: selectedUserGroupIds.value,
      inputName: "usergroups",
    })
  }

  return values
})

const currentSection = computed(
  () => sections.value.find((section) => section.id === activeSection.value) || sections.value[0],
)
const currentSelectedIds = computed({
  get() {
    if (activeSection.value === "groups") {
      return selectedGroupIds.value
    }

    if (activeSection.value === "usergroups") {
      return selectedUserGroupIds.value
    }

    return selectedUserIds.value
  },
  set(value) {
    if (activeSection.value === "groups") {
      selectedGroupIds.value = value
      return
    }

    if (activeSection.value === "usergroups") {
      selectedUserGroupIds.value = value
      return
    }

    selectedUserIds.value = value
  },
})

async function loadSubscriptions() {
  if (!categoryId.value || !contextParams.value.cid) {
    showErrorNotification(t("An error occurred"))
    return
  }

  isLoading.value = true

  try {
    const data = await lpService.getCategorySubscriptions(categoryId.value, contextParams.value)
    subscriptionData.value = data
    selectedUserIds.value = [...(data.selectedUserIds || [])]
    selectedGroupIds.value = [...(data.selectedGroupIds || [])]
    selectedUserGroupIds.value = [...(data.selectedUserGroupIds || [])]

    if (!data.allowUserGroups && activeSection.value === "usergroups") {
      activeSection.value = "users"
    }
  } catch (error) {
    showErrorNotification(error)
  } finally {
    isLoading.value = false
  }
}

async function saveSubscriptions() {
  if (!currentSection.value || !subscriptionData.value?.csrfToken) {
    return
  }

  isSaving.value = true

  try {
    await lpService.saveCategorySubscriptions(categoryId.value, contextParams.value, {
      section: currentSection.value.id,
      selectedIds: currentSelectedIds.value,
      csrfTokenInput: subscriptionData.value.csrfToken,
    })
    showSuccessNotification(t("Update successful"))
    await loadSubscriptions()
  } catch (error) {
    showErrorNotification(error)
  } finally {
    isSaving.value = false
  }
}

function goBack() {
  router.push({
    name: "LpList",
    params: { node: route.params.node },
    query: route.query,
  })
}

onMounted(loadSubscriptions)
</script>

<template>
  <div class="flex max-w-5xl flex-col gap-6">
    <SectionHeader :title="subscriptionData?.categoryTitle || t('Subscribe users to category')">
      <BaseButton
        :label="t('Back to learning paths')"
        icon="back"
        type="plain"
        @click="goBack"
      />
    </SectionHeader>

    <div
      v-if="isLoading"
      class="rounded-2xl border border-gray-25 bg-white p-6 text-gray-60 shadow-sm"
    >
      {{ t("Loading") }}…
    </div>

    <template v-else-if="subscriptionData">
      <div class="rounded-xl border border-support-3/30 bg-support-1/20 px-4 py-3 text-sm text-gray-90">
        {{
          t(
            "Note that the inscription of users in a category will override the inscription of users in the Learning Path",
          )
        }}
      </div>

      <section class="rounded-2xl border border-gray-25 bg-white shadow-sm">
        <div class="flex flex-wrap gap-2 border-b border-gray-25 p-4">
          <BaseButton
            v-for="section in sections"
            :key="section.id"
            :label="section.label"
            :type="activeSection === section.id ? 'primary' : 'plain'"
            @click="activeSection = section.id"
          />
        </div>

        <form
          v-if="currentSection"
          class="flex flex-col gap-6 p-6"
          @submit.prevent="saveSubscriptions"
        >
          <div class="flex flex-col gap-2">
            <label
              :for="`lp-category-${currentSection.inputName}`"
              class="text-sm font-semibold text-gray-90"
            >
              {{ currentSection.fieldLabel }}
            </label>

            <MultiSelect
              :input-id="`lp-category-${currentSection.inputName}`"
              v-model="currentSelectedIds"
              :name="currentSection.inputName"
              :options="currentSection.options"
              :placeholder="t('Select')"
              display="chip"
              fluid
              filter
              option-label="title"
              option-value="id"
            >
              <template #empty>
                {{ t("No results found") }}
              </template>
              <template #emptyfilter>
                {{ t("No results found") }}
              </template>
            </MultiSelect>
          </div>

          <div class="flex justify-end gap-2">
            <BaseButton
              :label="t('Cancel')"
              type="plain"
              @click="goBack"
            />
            <BaseButton
              :disabled="isSaving"
              :label="t('Save')"
              icon="content-save"
              is-submit
              type="success"
            />
          </div>
        </form>
      </section>
    </template>
  </div>
</template>
