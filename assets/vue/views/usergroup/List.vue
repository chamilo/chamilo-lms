<template>
  <div class="p-grid p-nogutter social-groups">
    <div class="p-col-12">
      <div class="p-d-flex p-jc-between p-ai-center p-mb-4">
        <h1>Social groups</h1>
        <Button label="Create a social group" icon="pi pi-plus" class="create-group-button" @click="showCreateGroupDialog = true" />
      </div>
      <TabView class="social-group-tabs">
        <TabPanel header="Newest" headerClass="tab-header" :class="{ 'active-tab': activeTab === 'Newest' }">
          <div class="group-list">
            <div class="group-item" v-for="group in newestGroups" :key="group.id">
              <i class="mdi mdi-account-group-outline group-icon"></i>
              <div class="group-details">
                <div class="group-title">{{ group.title }}</div>
                <div class="group-info">
                  <span class="group-member-count">{{ group.memberCount }} Member</span>
                  <span class="group-description">{{ group.description }}</span>
                </div>
              </div>
            </div>
          </div>
        </TabPanel>
        <TabPanel header="Popular" headerClass="tab-header" :class="{ 'active-tab': activeTab === 'Popular' }">
          <div class="group-list">
            <div class="group-item" v-for="group in popularGroups" :key="group.id">
              <i class="mdi mdi-account-group-outline group-icon"></i>
              <div class="group-details">
                <div class="group-title">{{ group.title }}</div>
                <div class="group-info">
                  <span class="group-member-count">{{ group.memberCount }} Member</span>
                  <span class="group-description">{{ group.description }}</span>
                </div>
              </div>
            </div>
          </div>        </TabPanel>
        <TabPanel header="My groups" headerClass="tab-header" :class="{ 'active-tab': activeTab === 'My groups' }">
          <div class="group-list">
            <div class="group-item" v-for="group in myGroups" :key="group.id">
              <i class="mdi mdi-account-group-outline group-icon"></i>
              <div class="group-details">
                <div class="group-title">{{ group.title }}</div>
                <div class="group-info">
                  <span class="group-member-count">{{ group.memberCount }} Member</span>
                  <span class="group-description">{{ group.description }}</span>
                </div>
              </div>
            </div>
          </div>
        </TabPanel>
      </TabView>
    </div>
  </div>

  <Dialog header="Add" :visible="showCreateGroupDialog" :modal="true" :closable="true" @hide="showCreateGroupDialog = false">
    <form @submit.prevent="createGroup">
      <div class="p-fluid">
        <BaseInputTextWithVuelidate
          v-model="groupForm.name"
          label="Name*"
          :vuelidate-property="v$.groupForm.name"
        />

        <BaseInputTextWithVuelidate
          v-model="groupForm.description"
          label="Description"
          :vuelidate-property="v$.groupForm.description"
          as="textarea"
          rows="3"
        />

        <BaseInputTextWithVuelidate
          v-model="groupForm.url"
          label="URL"
          :vuelidate-property="v$.groupForm.url"
        />
        <BaseFileUpload
          :label="t('Add a picture')"
          accept="image"
          size="small"
          @file-selected="selectedFile = $event"
        />
        <div class="p-field mt-2">
          <label for="groupPermissions">Group Permissions</label>
          <Dropdown id="groupPermissions" v-model="groupForm.permissions" :options="permissionsOptions" optionLabel="label" placeholder="Select Permission" />
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
      <Button label="Add" icon="pi pi-check" class="p-button-rounded p-button-text" @click="createGroup" />
    </form>
  </Dialog>
</template>

<script setup>
import Button from 'primevue/button';
import TabView from 'primevue/tabview';
import TabPanel from 'primevue/tabpanel';
import { ref, onMounted } from 'vue';
import useVuelidate from '@vuelidate/core';
import { required } from '@vuelidate/validators';
import BaseInputTextWithVuelidate from "../../components/basecomponents/BaseInputTextWithVuelidate.vue"
import BaseFileUpload from "../../components/basecomponents/BaseFileUpload.vue"
import BaseCheckbox from "../../components/basecomponents/BaseCheckbox.vue"
import { useI18n } from "vue-i18n"
import axios from "axios"

const {t} = useI18n()
const newestGroups = ref([]);
const popularGroups = ref([]);
const myGroups = ref([]);
const activeTab = ref('Newest');
const showCreateGroupDialog = ref(false);
const selectedFile = ref(null)

const groupForm = ref({
  name: '',
  description: '',
  url: '',
  picture: null,
});

const v$ = useVuelidate({
  groupForm: {
    name: { required },
    description: {},
    url: {},
  }
}, { groupForm });

const permissionsOptions = [
  { label: 'Open', value: '1' },
  { label: 'Closed', value: '2' },
];

const createGroup = async () => {
  v$.value.$touch();
  if (!v$.value.$invalid) {
    const groupData = {
      title: groupForm.value.name,
      description: groupForm.value.description,
      url: groupForm.value.url,
      visibility: groupForm.value.permissions.value,
      allowMembersToLeaveGroup: groupForm.value.allowLeave ? 1 : 0,
      groupType: 1,
    };

    try {
      const response = await axios.post('/api/usergroups', groupData, {
        headers: {
          'Content-Type': 'application/json',
        },
      });

      /*if (selectedFile.value && response.data && response.data.id) {
        const formData = new FormData();
        formData.append('picture', selectedFile.value);

        await axios.post(`/social-network/upload-group-picture/${response.data.id}`, formData, {
          headers: {
            'Content-Type': 'multipart/form-data',
          },
        });
      }*/

      showCreateGroupDialog.value = false;
      await updateGroupsList();
    } catch (error) {
      console.error('Failed to create group or upload picture:', error.response.data);
    }
  }
};

const fetchGroups = async (endpoint) => {
  try {
    const response = await fetch(`/api${endpoint}`);
    if (!response.ok) {
      throw new Error('Failed to fetch groups');
    }
    const data = await response.json();

    console.log('hidra menber ::: ', data['hydra:member'])

    return data['hydra:member'];
  } catch (error) {
    console.error(error);
    return [];
  }
};

const updateGroupsList = async () => {
  newestGroups.value = await fetchGroups('/usergroup/newest');
  popularGroups.value = await fetchGroups('/usergroup/popular');
  myGroups.value = await fetchGroups('/usergroup/my');
}

onMounted(async () => {
  await updateGroupsList();
});
</script>
