<template>
  <div class="p-grid p-nogutter social-groups">
    <div class="p-col-12">
      <div class="p-d-flex p-jc-between p-ai-center p-mb-4">
        <h1>Social groups</h1>
        <Button
          class="create-group-button"
          icon="pi pi-plus"
          label="Create a social group"
          @click="showCreateGroupDialog = true"
        />
      </div>
      <TabView class="social-group-tabs">
        <TabPanel
          :class="{ 'active-tab': activeTab === 'Newest' }"
          header="Newest"
          header-class="tab-header"
        >
          <div class="group-list">
            <div
              v-for="group in newestGroups"
              :key="group['@id']"
              class="group-item"
            >
              <img
                v-if="group.pictureUrl"
                :src="group.pictureUrl"
                alt="Group Image"
                class="group-image"
              />
              <i
                v-else
                class="mdi mdi-account-group-outline group-icon"
              ></i>
              <div class="group-details">
                <a
                  :href="`/resources/usergroups/show/${extractGroupId(group)}`"
                  class="group-title"
                  >{{ group.title }}</a
                >
                <div class="group-info">
                  <span class="group-member-count">{{ group.memberCount }} {{ t("Member") }}</span>
                  <span class="group-description">{{ group.description }}</span>
                </div>
              </div>
            </div>
          </div>
        </TabPanel>
        <TabPanel
          :class="{ 'active-tab': activeTab === 'Popular' }"
          header="Popular"
          header-class="tab-header"
        >
          <div class="group-list">
            <div
              v-for="group in popularGroups"
              :key="group['@id']"
              class="group-item"
            >
              <img
                v-if="group.pictureUrl"
                :src="group.pictureUrl"
                alt="Group Image"
                class="group-image"
              />
              <i
                v-else
                class="mdi mdi-account-group-outline group-icon"
              ></i>
              <div class="group-details">
                <a
                  :href="`/resources/usergroups/show/${extractGroupId(group)}`"
                  class="group-title"
                  >{{ group.title }}</a
                >
                <div class="group-info">
                  <span class="group-member-count">{{ group.memberCount }} {{ t("Member") }}</span>
                  <span class="group-description">{{ group.description }}</span>
                </div>
              </div>
            </div>
          </div>
        </TabPanel>
        <TabPanel
          :class="{ 'active-tab': activeTab === 'My groups' }"
          header="My groups"
          header-class="tab-header"
        >
          <div class="group-list">
            <div
              v-for="group in myGroups"
              :key="group['@id']"
              class="group-item"
            >
              <img
                v-if="group.pictureUrl"
                :src="group.pictureUrl"
                alt="Group Image"
                class="group-image"
              />
              <i
                v-else
                class="mdi mdi-account-group-outline group-icon"
              ></i>
              <div class="group-details">
                <a
                  :href="`/resources/usergroups/show/${extractGroupId(group)}`"
                  class="group-title"
                  >{{ group.title }}</a
                >
                <div class="group-info">
                  <span class="group-member-count">{{ group.memberCount }} {{ t("Member") }}</span>
                  <span class="group-description">{{ group.description }}</span>
                </div>
              </div>
            </div>
          </div>
        </TabPanel>
      </TabView>
    </div>
  </div>

  <Dialog
    v-model:visible="showCreateGroupDialog"
    closable="true"
    header="Add"
    modal="true"
  >
    <form @submit.prevent="createGroup">
      <div class="p-fluid">
        <BaseInputTextWithVuelidate
          v-model="groupForm.name"
          :vuelidate-property="v$.groupForm.name"
          label="Name*"
        />

        <BaseInputTextWithVuelidate
          v-model="groupForm.description"
          :vuelidate-property="v$.groupForm.description"
          as="textarea"
          label="Description"
          rows="3"
        />

        <BaseInputTextWithVuelidate
          v-model="groupForm.url"
          :vuelidate-property="v$.groupForm.url"
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
            v-model="groupForm.permissions"
            :options="permissionsOptions"
            option-label="label"
            placeholder="Select Permission"
          />
        </div>
        <div class="p-field-checkbox mt-2">
          <BaseCheckbox
            id="leaveGroup"
            v-model="groupForm.allowLeave"
            :label="$t('Allow members to leave group')"
            name="leaveGroup"
          />
        </div>
      </div>
      <Button
        class="p-button-rounded p-button-text"
        icon="pi pi-check"
        label="Add"
        @click="createGroup"
      />
      <Button
        class="p-button-text"
        label="Close"
        @click="showCreateGroupDialog = false"
      />
    </form>
  </Dialog>
</template>

<script setup>
import Button from "primevue/button"
import TabView from "primevue/tabview"
import TabPanel from "primevue/tabpanel"
import { onMounted, ref } from "vue"
import useVuelidate from "@vuelidate/core"
import { required } from "@vuelidate/validators"
import BaseInputTextWithVuelidate from "../../components/basecomponents/BaseInputTextWithVuelidate.vue"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import { useI18n } from "vue-i18n"
import usergroupService from "../../services/usergroupService"

const { t } = useI18n()
const newestGroups = ref([])
const popularGroups = ref([])
const myGroups = ref([])
const activeTab = ref("Newest")
const showCreateGroupDialog = ref(false)
const selectedFile = ref(null)

const groupForm = ref({
  name: "",
  description: "",
  url: "",
  picture: null,
})
const v$ = useVuelidate(
  {
    groupForm: {
      name: { required },
      description: {},
      url: {},
    },
  },
  { groupForm },
)
const permissionsOptions = [
  { label: "Open", value: "1" },
  { label: "Closed", value: "2" },
]
const createGroup = async () => {
  v$.value.$touch()
  if (!v$.value.$invalid) {
    const groupData = {
      title: groupForm.value.name,
      description: groupForm.value.description,
      url: groupForm.value.url,
      visibility: groupForm.value.permissions.value,
      allowMembersToLeaveGroup: groupForm.value.allowLeave ? 1 : 0,
      groupType: 1,
    }
    try {
      const newGroup = await usergroupService.createGroup(groupData)

      if (selectedFile.value && newGroup && newGroup.id) {
        await usergroupService.uploadPicture(newGroup.id, {
          picture: selectedFile.value,
        })
      }

      showCreateGroupDialog.value = false
      resetForm()
      updateGroupsList()
    } catch (error) {
      console.error("Failed to create group or upload picture:", error.response.data)
    }
  }
}

const updateGroupsList = () => {
  usergroupService.listNewest().then((newest) => (newestGroups.value = newest))
  usergroupService.listPopular().then((popular) => (popularGroups.value = popular))
  usergroupService.listMine().then((mine) => (myGroups.value = mine))
}

const extractGroupId = (group) => {
  const match = group["@id"].match(/\/api\/usergroup\/(\d+)/)
  return match ? match[1] : null
}
const redirectToGroupDetails = (groupId) => {
  router.push({ name: "UserGroupShow", params: { group_id: groupId } })
}
onMounted(async () => {
  updateGroupsList()
})

const closeDialog = () => {
  showCreateGroupDialog.value = false
}
const resetForm = () => {
  groupForm.value = {
    name: "",
    description: "",
    url: "",
    picture: null,
    permissions: "",
    allowLeave: false,
  }
  selectedFile.value = null
  v$.value.$reset()
}
</script>
