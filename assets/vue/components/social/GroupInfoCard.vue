<template>
  <BaseCard plain>
    <div class="p-4 text-center">
      <img
        :src="groupInfo.image"
        alt="Group picture"
        class="mb-4 w-24 h-24 mx-auto rounded-full"
      />
      <hr />
      <BaseButton
        v-if="groupInfo.isModerator"
        :label="t('Edit this group')"
        class="mt-4"
        icon="edit"
        type="primary"
        @click="showEditGroupDialog = true"
      />
    </div>
  </BaseCard>

  <Dialog
    v-model:visible="showEditGroupDialog"
    :closable="true"
    :modal="true"
    header="Edit Group"
  >
    <form @submit.prevent="submitGroupEdit">
      <div class="p-fluid">
        <BaseInputTextWithVuelidate
          v-model="editGroupForm.name"
          :vuelidate-property="v$.editGroupForm.name"
          label="Name*"
        />

        <BaseInputTextWithVuelidate
          v-model="editGroupForm.description"
          :vuelidate-property="v$.editGroupForm.description"
          as="textarea"
          label="Description"
          rows="3"
        />

        <BaseInputTextWithVuelidate
          v-model="editGroupForm.url"
          :vuelidate-property="v$.editGroupForm.url"
          label="URL"
        />

        <BaseFileUpload
          :label="t('Add a picture')"
          accept="image"
          size="small"
          @file-selected="selectedFile = $event"
        />

        <div class="p-field mt-2">
          <label for="groupPermissions">Group Permissions</label>
          <Dropdown
            id="groupPermissions"
            v-model="editGroupForm.permissions"
            :options="permissionsOptions"
            option-label="label"
            placeholder="Select Permission"
          />
        </div>

        <div class="p-field-checkbox mt-2">
          <BaseCheckbox
            id="leaveGroup"
            v-model="editGroupForm.allowLeave"
            :label="$t('Allow members to leave group')"
            name="leaveGroup"
          />
        </div>
      </div>
      <Button
        class="p-button-rounded p-button-text"
        icon="pi pi-check"
        label="Save"
        @click="submitGroupEdit"
      />
      <Button
        class="p-button-text"
        label="Close"
        @click="closeEditDialog"
      />
    </form>
  </Dialog>
</template>

<script setup>
import { inject, ref } from "vue"
import BaseCard from "../basecomponents/BaseCard.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import { useRouter } from "vue-router"
import BaseInputTextWithVuelidate from "../basecomponents/BaseInputTextWithVuelidate.vue"
import BaseCheckbox from "../basecomponents/BaseCheckbox.vue"
import BaseFileUpload from "../basecomponents/BaseFileUpload.vue"
import useVuelidate from "@vuelidate/core"
import { required } from "@vuelidate/validators"
import axios from "axios"
import { ENTRYPOINT } from "../../config/entrypoint"

const { t } = useI18n()
const router = useRouter()
const groupInfo = inject("group-info")

const showEditGroupDialog = ref(false)
const selectedFile = ref(null)

const permissionsOptions = [
  { label: "Open", value: 1 },
  { label: "Closed", value: 2 },
]

const editGroupForm = ref({
  name: groupInfo.value.title,
  description: groupInfo.value.description,
  url: groupInfo.value.url,
  permissions: permissionsOptions.find((option) => option.value === groupInfo.value.visibility),
  allowLeave: Boolean(groupInfo.value.allowMembersToLeaveGroup),
})

const v$ = useVuelidate(
  {
    editGroupForm: {
      name: { required },
      description: {},
      url: {},
      permissions: { required },
    },
  },
  { editGroupForm },
)

const submitGroupEdit = () => {
  v$.value.$touch()
  if (!v$.value.$invalid) {
    const updatedGroupData = {
      title: editGroupForm.value.name,
      description: editGroupForm.value.description,
      url: editGroupForm.value.url,
      visibility: String(editGroupForm.value.permissions.value),
      allowMembersToLeaveGroup: editGroupForm.value.allowLeave ? 1 : 0,
    }

    axios
      .put(`${ENTRYPOINT}usergroups/${groupInfo.value.id}`, updatedGroupData, {
        headers: {
          "Content-Type": "application/json",
        },
      })
      .then((response) => {
        if (selectedFile.value && response.data && response.data.id) {
          const formData = new FormData()
          formData.append("picture", selectedFile.value)
          return axios.post(`/social-network/upload-group-picture/${response.data.id}`, formData, {
            headers: {
              "Content-Type": "multipart/form-data",
            },
          })
        }
      })
      .then(() => {
        showEditGroupDialog.value = false
        router.push("/dummy").then(() => {
          router.go(-1)
        })
      })
      .catch((error) => {
        console.error("Error updating group:", error)
      })
  }
}

const closeEditDialog = () => {
  showEditGroupDialog.value = false
}
</script>
