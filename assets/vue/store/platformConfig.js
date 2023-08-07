import { defineStore } from "pinia";
import axios from "axios";
import { ref } from "vue";

export const usePlatformConfig = defineStore("platformConfig", () => {
  const isLoading = ref(false);
  const settings = ref(null);
  const studentView = ref('teacherview');
  const plugins = ref([])

  function getSetting(variable) {
    if (settings.value && settings.value[variable]) {
      return settings.value[variable];
    }

    return null;
  }

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

  function isStudentViewActive() {
    return 'studentview' === studentView.value
  }

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
