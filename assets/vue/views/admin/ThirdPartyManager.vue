<template>
  <div>
    <SectionHeader :title="t('Third parties (GDPR)')">
      <BaseButton
        :label="t('Add third party')"
        icon="plus"
        type="success"
        @click="openCreate"
      />
    </SectionHeader>

    <BaseTable
      :is-loading="loading"
      :values="thirdParties"
      data-key="id"
    >
      <Column :header="t('Title')" field="title" />
      <Column :header="t('Website')" field="website" />
      <Column :header="t('Recruiter')">
        <template #body="{ data }">
          <BaseIcon :icon="data.recruiter ? 'check' : 'minus'" />
        </template>
      </Column>
      <Column :header="t('Data exchange')">
        <template #body="{ data }">
          <BaseIcon :icon="data.dataExchangeParty ? 'check' : 'minus'" />
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
            <BaseButton
              icon="send"
              size="small"
              type="secondary"
              :label="t('Send data')"
              v-if="data.dataExchangeParty"
              @click="goToDataExchange(data.id)"
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
      @confirm-clicked="submitThirdParty"
      @cancel-clicked="resetForm"
    >
      <form @submit.prevent="submitThirdParty">
        <div class="p-fluid space-y-4">
          <div class="p-field">
            <label for="title">{{ t("Title") }}</label>
            <InputText
              v-model="form.title"
              id="title"
              required
              autofocus
              :class="{ 'p-invalid': !!errors.title }"
              @blur="onTitleBlur"
            />
            <small v-if="errors.title" class="p-error">{{ errors.title }}</small>
          </div>

          <div class="p-field">
            <label for="website">{{ t("Website") }}</label>
            <InputText
              v-model.trim="form.website"
              id="website"
              :class="{ 'p-invalid': !!errors.website }"
              @blur="onWebsiteBlur"
            />
            <small v-if="errors.website" class="p-error">{{ errors.website }}</small>
          </div>

          <div class="p-field">
            <label for="address">{{ t("Address") }}</label>
            <InputText v-model.trim="form.address" id="address" />
          </div>

          <BaseTinyEditor
            v-model="form.description"
            editor-id="third-party-description"
            :title="t('Description')"
            :required="false"
            :editor-config="{
              height: 250,
              toolbar: 'bold italic underline | removeformat',
            }"
          />

          <BaseCheckbox
            id="recruiter"
            v-model="form.recruiter"
            name="recruiter"
            :label="t('Is recruiter?')"
          />

          <BaseCheckbox
            id="dataExchangeParty"
            v-model="form.dataExchangeParty"
            name="dataExchangeParty"
            :label="t('Is used for data exchange?')"
          />
        </div>
      </form>
    </BaseDialogConfirmCancel>
    <BaseDialogConfirmCancel
      v-model:is-visible="showDeleteDialog"
      :title="t('Delete third party')"
      :confirm-label="t('Delete')"
      :cancel-label="t('Cancel')"
      @confirm-clicked="confirmDeleteThirdParty"
    >
      <p class="p-3">
        {{ t('Are you sure you want to delete this third party? This action cannot be undone.') }}
        <br />
        <strong>{{ selectedThirdParty?.title }}</strong>
      </p>
    </BaseDialogConfirmCancel>
  </div>
</template>
<script setup>
import { onMounted, ref, computed } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import BaseDialogConfirmCancel from "../../components/basecomponents/BaseDialogConfirmCancel.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import BaseTable from "../../components/basecomponents/BaseTable.vue"
import adminService from "../../services/adminService"
import { useNotification } from "../../composables/notification"

const { t } = useI18n()
const router = useRouter()
const { showErrorNotification, showSuccessNotification } = useNotification()

const thirdParties = ref([])
const loading = ref(true)

const showDialog = ref(false)
const isEditing = ref(false)
const editingId = ref(null)
const showDeleteDialog = ref(false)
const selectedThirdParty = ref(null)

const form = ref({
  title: "",
  website: "",
  description: "",
  address: "",
  dataExchangeParty: false,
  recruiter: false,
})

const errors = ref({
  title: "",
  website: "",
})

const dialogTitle = computed(() =>
  isEditing.value ? t("Edit") : t("Add"),
)

const validateTitle = () => {
  errors.value.title = ""
  const v = (form.value.title || "").trim()
  if (!v) {
    errors.value.title = t("This field cannot be empty")
    return false
  }
  return true
}

const onTitleBlur = () => {
  form.value.title = (form.value.title || "").trim()
  validateTitle()
}

const normalizeUrl = (value) => {
  if (!value) return ""
  const trimmed = value.trim()
  if (/^https?:\/\//i.test(trimmed)) return trimmed
  return `https://${trimmed}`
}

const isValidHttpUrl = (value) => {
  if (!value || value.trim() === "") return true
  try {
    const u = new URL(value)
    return u.protocol === "http:" || u.protocol === "https:"
  } catch {
    return false
  }
}

const validateWebsite = () => {
  errors.value.website = ""
  if (!form.value.website) return true
  const candidate = form.value.website
  if (!isValidHttpUrl(candidate)) {
    errors.value.website = t("This is not a valid URL. Example: https://example.com")
    return false
  }
  return true
}

const onWebsiteBlur = () => {
  if (!form.value.website) {
    errors.value.website = ""
    return
  }
  form.value.website = normalizeUrl(form.value.website)
  validateWebsite()
}

const isFormValid = () => {
  form.value.title = (form.value.title || "").trim()
  if (form.value.website) {
    form.value.website = normalizeUrl(form.value.website)
  }

  const okTitle = validateTitle()
  const okWebsite = validateWebsite()
  return okTitle && okWebsite
}

const fetchThirdParties = async () => {
  loading.value = true
  try {
    thirdParties.value = await adminService.fetchThirdParties()
  } catch (error) {
    console.error("Error loading third parties", error)
  } finally {
    loading.value = false
  }
}

const openCreate = () => {
  resetForm()
  isEditing.value = false
  showDialog.value = true
}

const openEdit = (row) => {
  isEditing.value = true
  editingId.value = row.id
  form.value = {
    title: row.title || "",
    website: row.website || "",
    description: row.description || "",
    address: row.address || "",
    dataExchangeParty: !!row.dataExchangeParty,
    recruiter: !!row.recruiter,
  }
  errors.value.title = ""
  errors.value.website = ""
  showDialog.value = true
}

const submitThirdParty = async () => {
  if (!isFormValid()) {
    showErrorNotification(t("Please fix the errors before saving."))
    return
  }
  try {
    if (isEditing.value) {
      await adminService.updateThirdParty(editingId.value, form.value)
      showSuccessNotification(t("Third party updated successfully."))
    } else {
      await adminService.createThirdParty(form.value)
      showSuccessNotification(t("Third party created successfully."))
    }
    showDialog.value = false
    resetForm()
    await fetchThirdParties()
  } catch (error) {
    console.error(error)
    showErrorNotification(
      isEditing.value
        ? t("Error updating third party. Please verify the data.")
        : t("Error creating third party. Please verify the data."),
    )
  }
}

const resetForm = () => {
  form.value = {
    title: "",
    website: "",
    description: "",
    address: "",
    dataExchangeParty: false,
    recruiter: false,
  }
  errors.value.title = ""
  errors.value.website = ""
  isEditing.value = false
  editingId.value = null
  showDialog.value = false
}

const openDelete = (row) => {
  selectedThirdParty.value = row
  showDeleteDialog.value = true
}

const confirmDeleteThirdParty = async () => {
  try {
    await adminService.deleteThirdParty(selectedThirdParty.value.id)
    showSuccessNotification(t("Third party deleted."))
    showDeleteDialog.value = false
    selectedThirdParty.value = null
    await fetchThirdParties()
  } catch (e) {
    console.error(e)
    showErrorNotification(t("Error deleting third party."))
  }
}

const goToDataExchange = (thirdPartyId) => {
  router.push({
    name: "DataExchangeManager",
    query: { thirdPartyId },
  })
}

onMounted(fetchThirdParties)
</script>
