import { ref } from 'vue'

const isSidebarOpen = ref(false)
const toggleSidebar = () => {
  isSidebarOpen.value = !isSidebarOpen.value
}

const isSettingsPanelOpen = ref(false)

const isSearchPanelOpen = ref(false)

const isNotificationsPanelOpen = ref(false)

export default function useState() {
  return {
    isSidebarOpen,
    toggleSidebar,
    isSettingsPanelOpen,
    isSearchPanelOpen,
    isNotificationsPanelOpen,
  }
}
