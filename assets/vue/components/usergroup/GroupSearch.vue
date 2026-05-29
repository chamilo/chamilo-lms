<template>
  <BaseCard class="mb-2">
    <template #header>
      <div class="px-4 py-2 -mb-2 bg-gray-15">
        <h2 class="text-h5">{{ headerTitle }}</h2>
      </div>
    </template>
    <div class="flex flex-col items-end">
      <div class="w-full flex justify-between items-center mb-2">
        <label
          class="mr-2"
          for="search-query"
          >{{ t("Groups") }}</label
        >
        <BaseInputText
          id="search-query"
          v-model="query"
          class="flex-grow"
          label=""
        />
      </div>
      <BaseButton
        class="self-end"
        icon="search"
        :label="$t('Search')"
        type="secondary"
        @click="handleFormSearch"
      />
    </div>
  </BaseCard>

  <BaseCard
    v-if="groups.length"
    class="mb-2"
  >
    <template #header>
      <div class="px-4 py-2 -mb-2 bg-gray-15">
        <h2 class="text-h5">{{ t("Groups") }}</h2>
      </div>
    </template>
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4 p-4">
      <div
        v-for="group in groups"
        :key="group.id"
        class="rounded-2xl border border-gray-25 bg-white p-4 text-center shadow-sm"
      >
        <div class="flex justify-center">
          <img
            :src="group.image || defaultGroupImage"
            :alt="group.name"
            class="h-20 w-20 rounded-full object-cover"
            loading="lazy"
          />
        </div>
        <div class="mt-3 text-center">
          <h3 class="font-semibold text-gray-90">{{ group.name }}</h3>
          <p class="mt-1 text-sm text-gray-60">{{ group.description }}</p>
          <a :href="group.url">
            <BaseButton
              class="mt-3"
              icon=""
              :label="t('See more')"
              type="secondary"
            />
          </a>
        </div>
      </div>
    </div>
  </BaseCard>
</template>

<script setup>
import { computed, ref } from "vue"
import BaseCard from "../../components/basecomponents/BaseCard.vue"
import BaseInputText from "../../components/basecomponents/BaseInputText.vue"
import BaseButton from "../../components/basecomponents/BaseButton.vue"
import { useI18n } from "vue-i18n"
import { useNotification } from "../../composables/notification"
import baseService from "../../services/baseService"

const query = ref("")
const { t } = useI18n()
const notification = useNotification()
const groups = ref([])
const defaultGroupImage = "/img/icons/64/group_na.png"

const headerTitle = computed(() => {
  return query.value ? `${t("Results for")} "${query.value}"` : t("Search groups")
})

const handleFormSearch = async () => {
  if (!query.value.trim()) {
    notification.showWarningNotification(t("Please enter a search term."))
    return
  }
  try {
    const data = await baseService.get("/social-network/search", { query: query.value, type: "group" })
    groups.value = data.results
  } catch (error) {
    console.error("There has been a problem with your fetch operation:", error)
  }
}
</script>
