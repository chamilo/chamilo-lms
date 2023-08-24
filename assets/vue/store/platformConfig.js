import { defineStore } from "pinia";
import axios from "axios";
import { computed, ref } from "vue"

export const usePlatformConfig = defineStore("platformConfig", () => {
  const isLoading = ref(false);
  const settings = ref([]);
  const studentView = ref('teacherview');
  const plugins = ref([])

  async function findSettingsRequest() {
    isLoading.value = true;

    try {
      const { data } = await axios.get("/platform-config/list")

      settings.value = data.settings

      studentView.value = data.studentview

      plugins.value = data.plugins
    } catch (e) {
      console.log(e)
    } finally {
      isLoading.value = false
    }
  }

  async function initialize() {
    await findSettingsRequest();
  }

  const getSetting = computed(
    () => (variable) => (settings.value && settings.value[variable] ? settings.value[variable] : null),
  )

  const isStudentViewActive = computed(() => "studentview" === studentView.value)

  return {
    isLoading,
    settings,
    studentView,
    plugins,
    initialize,
    getSetting,
    isStudentViewActive,
  };
});
