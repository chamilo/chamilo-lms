<template>
  <div class="search-container social-groups">
    <div class="search-header">
      <h2>{{ t('Results and feedback') }} {{ searchTerm }}</h2>
      <div class="p-inputgroup">
        <BaseInputText
          v-model="searchTerm"
          :placeholder="t('Search')"
          class="search-term-input"
          label="Search term ..."/>
        <BaseButton
          label="Search"
          icon="pi pi-search"
          @click="performSearch"
          type="button"/>
      </div>
    </div>
    <div class="p-grid search-results">
      <div class="p-col-12 p-md-4" v-for="group in searchResults" :key="group.id">
        <div class="group-card">
          <div class="group-image">
            <i v-if="!group.pictureUrl" class="pi pi-users large-icon"></i>
            <img v-else :src="group.pictureUrl" alt="Group" />
          </div>
          <div class="group-details">
            <h4 class="group-title">{{ group.title }}</h4>
            <p class="group-description">{{ group.description }}</p>
            <a :href="`/resources/usergroups/show/${extractGroupId(group)}`" class="group-title">{{ t('See more') }}</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { onMounted, ref } from "vue"
import { useI18n } from 'vue-i18n'
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import axios from 'axios'
import { useRoute } from "vue-router"

const { t } = useI18n()
const route = useRoute()
const searchTerm = ref('')
const searchResults = ref([])
onMounted(() => {
  if (route.query.q) {
    searchTerm.value = route.query.q
    performSearch()
  }
})
const performSearch = async () => {
  try {
    const response = await axios.get('/api/usergroups/search', {
      params: { search: searchTerm.value },
    })
    searchResults.value = response.data['hydra:member']
  } catch (error) {
    console.error('Error performing search:', error)
    searchResults.value = []
  }
}
const extractGroupId = (group) => {
  const match = group['@id'].match(/\/api\/usergroup\/(\d+)/)
  return match ? match[1] : null
}
</script>
