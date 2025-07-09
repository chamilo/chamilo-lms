<template>
  <div>
    <SectionHeader :title="t('Third parties (GDPR)')">
      <BaseButton
        :label="t('Add third party')"
        icon="plus"
        type="success"
        @click="showCreateDialog = true"
      />
    </SectionHeader>

    <DataTable
      :value="thirdParties"
      :loading="loading"
      data-key="id"
      class="mb-5"
      striped-rows
    >
      <Column
        :header="t('Name')"
        field="name"
      />
      <Column
        :header="t('Website')"
        field="website"
      />
      <Column :header="t('Recruiter')">
        <template #body="{ data }">
          <BaseIcon :icon="data.recruiter ? 'check' : 'minus'" />
        </template>
      </Column>
      <Column :header="t('Data Exchange')">
        <template #body="{ data }">
          <BaseIcon :icon="data.dataExchangeParty ? 'check' : 'minus'" />
        </template>
      </Column>
      <Column :header="t('Actions')">
        <template #body="{ data }">
          <BaseButton
            icon="send"
            size="small"
            type="secondary"
            :label="t('Send data')"
            v-if="data.dataExchangeParty"
            @click="goToDataExchange(data.id)"
          />
        </template>
      </Column>
    </DataTable>

    <BaseDialogConfirmCancel
      v-model:is-visible="showCreateDialog"
      :title="t('Add third party')"
      :confirm-label="t('Save')"
      :cancel-label="t('Cancel')"
      @confirm-clicked="createThirdParty"
      @cancel-clicked="resetForm"
    >
      <form @submit.prevent="createThirdParty">
        <div class="p-fluid space-y-4">
          <div class="p-field">
            <label for="name">{{ t("Name") }}</label>
            <InputText
              v-model.trim="form.name"
              id="name"
              required
              autofocus
            />
          </div>

          <div class="p-field">
            <label for="website">{{ t("Website") }}</label>
            <InputText
              v-model.trim="form.website"
              id="website"
            />
          </div>

          <div class="p-field">
            <label for="address">{{ t("Address") }}</label>
            <InputText
              v-model.trim="form.address"
              id="address"
            />
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
  </div>
</template>
<script setup>
import { ref, onMounted } from "vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import BaseDialogConfirmCancel from "../../components/basecomponents/BaseDialogConfirmCancel.vue"
import BaseIcon from "../../components/basecomponents/BaseIcon.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import SectionHeader from "../../components/layout/SectionHeader.vue"
import BaseTinyEditor from "../../components/basecomponents/BaseTinyEditor.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import adminService from "../../services/adminService"

const { t } = useI18n()
const router = useRouter()
const thirdParties = ref([])
const loading = ref(true)
const showCreateDialog = ref(false)
const form = ref({
  name: "",
  website: "",
  description: "",
  address: "",
  dataExchangeParty: false,
  recruiter: false,
})

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

const createThirdParty = async () => {
  try {
    await adminService.createThirdParty(form.value)
    showCreateDialog.value = false
    resetForm()
    await fetchThirdParties()
  } catch (error) {
    console.error("Error creating third party", error)
  }
}

const resetForm = () => {
  form.value = {
    name: "",
    website: "",
    description: "",
    address: "",
    dataExchangeParty: false,
    recruiter: false,
  }
  showCreateDialog.value = false
}

const goToDataExchange = (thirdPartyId) => {
  router.push({
    name: "DataExchangeManager",
    query: { thirdPartyId },
  })
}

onMounted(fetchThirdParties)
</script>
