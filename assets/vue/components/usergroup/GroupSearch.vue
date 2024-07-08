<template>
  <BaseCard class="mb-2">
    <template #header>
      <div class="px-4 py-2 -mb-2 bg-gray-15">
        <h2 class="text-h5">{{ headerTitle }}</h2>
      </div>
    </template>
    <div class="flex flex-col items-end">
      <div class="w-full flex justify-between items-center mb-2">
        <label for="search-query" class="mr-2">{{ t('Groups') }}</label>
        <BaseInputText
          id="search-query"
          v-model="query"
          class="flex-grow"
          label=""
        />
      </div>
      <BaseButton
        label="Search"
        icon="search"
        @click="handleFormSearch"
        type="secondary"
        class="self-end"
      />
    </div>
  </BaseCard>

  <BaseCard v-if="groups.length" class="mb-2">
    <template #header>
      <div class="px-4 py-2 -mb-2 bg-gray-15">
        <h2 class="text-h5">{{ t('Groups') }}</h2>
      </div>
    </template>
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 p-4">
      <div v-for="group in groups" :key="group.id" class="group-card">
        <div class="group-image flex justify-center">
          <img :src="group.image" class="rounded w-16 h-16" />
        </div>
        <div class="group-info text-center">
          <h3>{{ group.name }}</h3>
          <p>{{ group.description }}</p>
          <a :href="group.url">
            <BaseButton label="See more" type="secondary" class="mt-2" icon="" />
          </a>
        </div>
      </div>
    </div>
  </BaseCard>
</template>

<script setup>
import { ref, computed } from "vue";
import BaseCard from "../../components/basecomponents/BaseCard.vue";
import BaseInputText from "../../components/basecomponents/BaseInputText.vue";
import BaseButton from "../../components/basecomponents/BaseButton.vue";
import { useI18n } from "vue-i18n";
import { useNotification } from "../../composables/notification";
import { useSocialInfo } from "../../composables/useSocialInfo";

const query = ref('');
const { t } = useI18n();
const notification = useNotification();
const { user, loadGroup, groupInfo, isLoading } = useSocialInfo();
const groups = ref([]);

const headerTitle = computed(() => {
  return query.value ? `${t('Results for')} "${query.value}"` : t('Search Groups');
});

const handleFormSearch = async () => {
  if (!query.value.trim()) {
    notification.showWarningNotification('Please enter a search term.');
    return;
  }
  try {
    const response = await fetch(`/social-network/search?query=${query.value}&type=group`);
    const data = await response.json();
    if (!response.ok) {
      throw new Error(data.message || 'Server response error');
    }
    groups.value = data.results;
  } catch (error) {
    console.error('There has been a problem with your fetch operation:', error);
  }
};
</script>
