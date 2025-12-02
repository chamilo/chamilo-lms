<template>
  <div
    class="flex flex-wrap items-center"
    role="tablist"
  >
    <BaseAppLink
      v-for="(tab, index) in tabs"
      :key="tab.title"
      :aria-selected="selectedTab === index ? 'true' : 'false'"
      :class="{
        'text-primary border-b-2 border-primary': selectedTab === index,
        'text-gray-50 border-b-2 border-gray-50 hover:text-primary hover:border-b-2 hover:border-primary':
          selectedTab !== index,
      }"
      :to="tab.to"
      class="px-4 py-2 font-semibold mr-2 sm:mr-3 last:mr-0"
      role="tab"
    >
      {{ tab.title }}
    </BaseAppLink>
  </div>
</template>

<script setup>
/**
 * Component that will render a tab interface WITHOUT content. Every tab should be a router link. So, when user
 * change tab the route of the url will change
 */
defineProps({
  tabs: {
    type: Array,
    required: true,
    validator: (value) => {
      let isTabsCorrect = value.every((e) => Object.hasOwn(e, "title") && Object.hasOwn(e, "to"))
      if (!isTabsCorrect) {
        return false
      }
      let titles = value.map((e) => e.title)
      return new Set(titles).size === titles.length
    },
  },
  selectedTab: {
    type: Number,
    required: true,
  },
})
</script>
