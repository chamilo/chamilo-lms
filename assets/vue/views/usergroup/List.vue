<template>
  <div class="p-4 social-groups">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-semibold">{{ t('Social groups') }}</h1>
      <BaseButton
        class="self-end"
        type="secondary"
        icon="plus"
        :label="$t('Create group')"
        @click="showCreateGroupDialog = true"
      />
    </div>
    <div class="tabs mb-4">
      <button
        :class="['tab', { 'tab-active': activeTab === 'Newest' }]"
        @click="activeTab = 'Newest'"
      >
        {{ t('Newest') }}
      </button>
      <button
        :class="['tab', { 'tab-active': activeTab === 'Popular' }]"
        @click="activeTab = 'Popular'"
      >
        {{ t('Popular') }}
      </button>
      <button
        :class="['tab', { 'tab-active': activeTab === 'My groups' }]"
        @click="activeTab = 'My groups'"
      >
        {{ t('My groups') }}
      </button>
      <button
        :class="['tab', { 'tab-active': activeTab === 'Search Groups' }]"
        @click="activeTab = 'Search Groups'"
      >
        {{ t('Search groups') }}
      </button>
    </div>
    <div v-show="activeTab === 'Newest'">
      <div class="group-list">
        <div
          v-for="group in newestGroups"
          :key="group['@id']"
          class="group-item flex items-center p-4 bg-white shadow-md rounded-md mb-4"
        >
          <img
            v-if="group.pictureUrl"
            :src="group.pictureUrl"
            :alt="t('Group image')"
            class="w-16 h-16 rounded-full mr-4"
          />
          <i
            v-else
            class="mdi mdi-account-group-outline text-4xl text-gray-500 mr-4"
          ></i>
          <div class="group-details">
            <a
              :href="`/resources/usergroups/show/${extractGroupId(group)}`"
              class="text-lg font-semibold text-blue-600 hover:underline"
              >{{ group.title }}</a
            >
            <div class="group-info text-gray-500">
              <span class="group-member-count">{{ group.memberCount }} {{ t('Members') }}</span>
              <span class="group-description">{{ group.description }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div v-show="activeTab === 'Popular'">
      <div class="group-list">
        <div
          v-for="group in popularGroups"
          :key="group['@id']"
          class="group-item flex items-center p-4 bg-white shadow-md rounded-md mb-4"
        >
          <img
            v-if="group.pictureUrl"
            :src="group.pictureUrl"
            :alt="t('Group image')"
            class="w-16 h-16 rounded-full mr-4"
          />
          <i
            v-else
            class="mdi mdi-account-group-outline text-4xl text-gray-500 mr-4"
          ></i>
          <div class="group-details">
            <a
              :href="`/resources/usergroups/show/${extractGroupId(group)}`"
              class="text-lg font-semibold text-blue-600 hover:underline"
              >{{ group.title }}</a
            >
            <div class="group-info text-gray-500">
              <span class="group-member-count">{{ group.memberCount }} {{ t('Members') }}</span>
              <span class="group-description">{{ group.description }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div v-show="activeTab === 'My groups'">
      <div class="group-list">
        <div
          v-for="group in myGroups"
          :key="group['@id']"
          class="group-item flex items-center p-4 bg-white shadow-md rounded-md mb-4"
        >
          <img
            v-if="group.pictureUrl"
            :src="group.pictureUrl"
            :alt="t('Group image')"
            class="w-16 h-16 rounded-full mr-4"
          />
          <i
            v-else
            class="mdi mdi-account-group-outline text-4xl text-gray-500 mr-4"
          ></i>
          <div class="group-details">
            <a
              :href="`/resources/usergroups/show/${extractGroupId(group)}`"
              class="text-lg font-semibold text-blue-600 hover:underline"
              >{{ group.title }}</a
            >
            <div class="group-info text-gray-500">
              <span class="group-member-count">{{ group.memberCount }} {{ t('Members') }}</span>
              <span class="group-description">{{ group.description }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div v-show="activeTab === 'Search Groups'">
      <GroupSearch />
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
          :label="t('Name')"
        />

        <BaseInputTextWithVuelidate
          v-model="groupForm.description"
          :vuelidate-property="v$.groupForm.description"
          as="textarea"
          :label="t('Description')"
          rows="3"
        />

        <BaseInputTextWithVuelidate
          v-model="groupForm.url"
          :vuelidate-property="v$.groupForm.url"
          :label="t('URL')"
        />
        <BaseFileUpload
          :label="t('Add a picture')"
          accept="image/*"
          size="small"
          @file-selected="selectedFile = $event"
        />
        <div class="p-field mt-2">
          <label for="groupPermissions">{{ t('Group permissions') }}</label>
          <Dropdown
            id="groupPermissions"
            v-model="groupForm.permissions"
            :options="permissionsOptions"
            option-label="label"
            :placeholder="t('Select permission')"
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
      <BaseButton
        class="self-end"
        type="secondary"
        icon="check"
        :label="$t('Add')"
        @click="createGroup"
      />
      <BaseButton
        class="self-end"
        type="secondary"
        icon="cross"
        :label="$t('Close')"
        @click="showCreateGroupDialog = false"
      />
    </form>
  </Dialog>
</template>

<script setup>
import Button from "primevue/button"
import { onMounted, ref } from "vue"
import useVuelidate from "@vuelidate/core"
import { required } from "@vuelidate/validators"
import BaseInputTextWithVuelidate from "../../components/basecomponents/BaseInputTextWithVuelidate.vue"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import { useI18n } from "vue-i18n"
import usergroupService from "../../services/usergroupService"
import GroupSearch from "../../components/usergroup/GroupSearch.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"

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
