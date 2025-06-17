<template>
  <div class="w-64 h-full bg-white border-r border-gray-25 p-4 space-y-1">
    <RouterLink
      v-for="item in items"
      :key="item.name"
      :to="{ name: item.route }"
      class="flex items-center gap-3 px-4 py-2 rounded-xl transition-colors duration-200 group"
      :class="{
        'bg-primary/10 text-primary font-semibold border border-primary': route.name === item.route,
        'text-gray-90 hover:bg-gray-15 hover:text-primary': route.name !== item.route
      }"
    >
      <i :class="`pi ${item.icon} text-base group-hover:text-primary`" />
      <span class="text-sm">{{ t(item.label) }}</span>
    </RouterLink>
  </div>
</template>

<script setup>
import { useRoute } from "vue-router"
import { useI18n } from "vue-i18n"

const route = useRoute()
const { t } = useI18n()

const items = [
  { name: "Dashboard", route: "AdminDashboard", icon: "pi-home", label: "Dashboard" },
  { name: "Favorites", route: "AdminFavoritesCourses", icon: "pi-star", label: "Favorite courses" },
  { name: "Completed", route: "AdminCompletedCourses", icon: "pi-check-circle", label: "Completed courses" },
  { name: "Incomplete", route: "AdminIncompleteCourses", icon: "pi-times-circle", label: "Incomplete courses" },
  { name: "Restart", route: "AdminRestartCourses", icon: "pi-refresh", label: "Courses to restart" },
]
</script>
