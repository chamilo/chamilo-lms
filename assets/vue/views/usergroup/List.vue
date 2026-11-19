<template>
  <div class="p-4 social-groups">
    <div class="flex justify-between items-center mb-4">
      <h1 class="text-2xl font-semibold">{{ t("Social groups") }}</h1>
      <BaseButton
        class="self-end"
        type="success"
        icon="plus"
        :label="$t('Create group')"
        @click="showCreateGroupDialog = true"
      />
    </div>
    <div
      class="tabs mb-4"
      role="tablist"
      :aria-label="t('Social groups')"
    >
      <button
        id="social-groups-tab-newest"
        role="tab"
        aria-controls="social-groups-panel-newest"
        :aria-selected="activeTab === 'Newest'"
        :tabindex="activeTab === 'Newest' ? 0 : -1"
        :class="['tab', { 'tab-active': activeTab === 'Newest' }]"
        @click="activeTab = 'Newest'"
        @keydown="onGroupTabKeydown"
      >
        {{ t("Newest") }}
      </button>
      <button
        id="social-groups-tab-popular"
        role="tab"
        aria-controls="social-groups-panel-popular"
        :aria-selected="activeTab === 'Popular'"
        :tabindex="activeTab === 'Popular' ? 0 : -1"
        :class="['tab', { 'tab-active': activeTab === 'Popular' }]"
        @click="activeTab = 'Popular'"
        @keydown="onGroupTabKeydown"
      >
        {{ t("Popular") }}
      </button>
      <button
        id="social-groups-tab-mine"
        role="tab"
        aria-controls="social-groups-panel-mine"
        :aria-selected="activeTab === 'My groups'"
        :tabindex="activeTab === 'My groups' ? 0 : -1"
        :class="['tab', { 'tab-active': activeTab === 'My groups' }]"
        @click="activeTab = 'My groups'"
        @keydown="onGroupTabKeydown"
      >
        {{ t("My groups") }}
      </button>
      <button
        id="social-groups-tab-search"
        role="tab"
        aria-controls="social-groups-panel-search"
        :aria-selected="activeTab === 'Search Groups'"
        :tabindex="activeTab === 'Search Groups' ? 0 : -1"
        :class="['tab', { 'tab-active': activeTab === 'Search Groups' }]"
        @click="activeTab = 'Search Groups'"
        @keydown="onGroupTabKeydown"
      >
        {{ t("Search groups") }}
      </button>
    </div>
    <div
      v-show="activeTab === 'Newest'"
      id="social-groups-panel-newest"
      role="tabpanel"
      aria-labelledby="social-groups-tab-newest"
      tabindex="0"
    >
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
            class="w-16 h-16 rounded-full me-4"
          />
          <i
            v-else
            class="mdi mdi-account-group-outline text-4xl text-gray-500 me-4"
          ></i>
          <div class="group-details">
            <a
              :href="`/resources/usergroups/show/${extractGroupId(group)}`"
              class="text-lg font-semibold text-blue-600 hover:underline"
              >{{ group.title }}</a
            >
            <div class="group-info text-gray-500">
              <span class="group-member-count">{{ group.memberCount }} {{ t("Members") }}</span>
              <span class="group-description">{{ group.description }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div
      v-show="activeTab === 'Popular'"
      id="social-groups-panel-popular"
      role="tabpanel"
      aria-labelledby="social-groups-tab-popular"
      tabindex="0"
    >
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
            class="w-16 h-16 rounded-full me-4"
          />
          <i
            v-else
            class="mdi mdi-account-group-outline text-4xl text-gray-500 me-4"
          ></i>
          <div class="group-details">
            <a
              :href="`/resources/usergroups/show/${extractGroupId(group)}`"
              class="text-lg font-semibold text-blue-600 hover:underline"
              >{{ group.title }}</a
            >
            <div class="group-info text-gray-500">
              <span class="group-member-count">{{ group.memberCount }} {{ t("Members") }}</span>
              <span class="group-description">{{ group.description }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div
      v-show="activeTab === 'My groups'"
      id="social-groups-panel-mine"
      role="tabpanel"
      aria-labelledby="social-groups-tab-mine"
      tabindex="0"
    >
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
            class="w-16 h-16 rounded-full me-4"
          />
          <i
            v-else
            class="mdi mdi-account-group-outline text-4xl text-gray-500 me-4"
          ></i>
          <div class="group-details">
            <a
              :href="`/resources/usergroups/show/${extractGroupId(group)}`"
              class="text-lg font-semibold text-blue-600 hover:underline"
              >{{ group.title }}</a
            >
            <div class="group-info text-gray-500">
              <span class="group-member-count">{{ group.memberCount }} {{ t("Members") }}</span>
              <span class="group-description">{{ group.description }}</span>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div
      v-show="activeTab === 'Search Groups'"
      id="social-groups-panel-search"
      role="tabpanel"
      aria-labelledby="social-groups-tab-search"
      tabindex="0"
    >
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
          aria-required="true"
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
          <label
            id="groupPermissions-label"
            for="groupPermissions"
          >
            {{ t("Group permissions") }}
            <span
              aria-hidden="true"
              class="text-red-500"
            >
              *
            </span>
          </label>
          <Dropdown
            v-model="groupForm.permissions"
            input-id="groupPermissions"
            name="permissions"
            :invalid="v$.groupForm.permissions.$error"
            aria-labelledby="groupPermissions-label"
            :pt="{
              label: {
                'aria-describedby': v$.groupForm.permissions.$error ? 'groupPermissions-error' : undefined,
                'aria-required': 'true',
              },
            }"
            :options="permissionsOptions"
            option-label="label"
            :placeholder="t('Select permission')"
          />
          <small
            v-if="v$.groupForm.permissions.$error"
            id="groupPermissions-error"
            class="mt-1 block text-error"
            role="alert"
          >
            {{ t("Value is required") }}
          </small>
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
        type="success"
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

defineOptions({ name: "SocialGroupList" })

const { t } = useI18n()
const newestGroups = ref([])
const popularGroups = ref([])
const myGroups = ref([])
const activeTab = ref("Newest")

const onGroupTabKeydown = (event) => {
  const { key } = event
  if (!["ArrowRight", "ArrowLeft", "Home", "End"].includes(key)) {
    return
  }

  const tabs = Array.from(event.currentTarget.parentElement.querySelectorAll('[role="tab"]'))
  const currentIndex = tabs.indexOf(event.currentTarget)
  if (currentIndex === -1) {
    return
  }

  event.preventDefault()
  let nextIndex
  if (key === "Home") {
    nextIndex = 0
  } else if (key === "End") {
    nextIndex = tabs.length - 1
  } else {
    const direction = key === "ArrowRight" ? 1 : -1
    nextIndex = (currentIndex + direction + tabs.length) % tabs.length
  }

  tabs[nextIndex].focus()
}

const showCreateGroupDialog = ref(false)
const selectedFile = ref(null)

const groupForm = ref({
  name: "",
  description: "",
  url: "",
  picture: null,
  permissions: null,
  allowLeave: false,
})
const v$ = useVuelidate(
  {
    groupForm: {
      name: { required },
      description: {},
      url: {},
      permissions: { required },
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
onMounted(async () => {
  updateGroupsList()
})

const resetForm = () => {
  groupForm.value = {
    name: "",
    description: "",
    url: "",
    picture: null,
    permissions: null,
    allowLeave: false,
  }
  selectedFile.value = null
  v$.value.$reset()
}
</script>
