import { defineStore } from "pinia";
import axios from "axios";
import { ref } from "vue";

export const usePlatformConfig = defineStore("platformConfig", () => {
  const isLoading = ref(false);
  const settings = ref(null);
  const studentView = ref(null);

  function getSetting(variable) {
    if (settings.value && settings.value[variable]) {
      return settings.value[variable];
    }

    return null;
  }

  async function findSettingsRequest() {
    isLoading.value = true;

    const { data } = await axios.get("/platform-config/list");

    settings.value = data.settings;
    studentView.value = data.studentview;

    isLoading.value = false;
  }

  async function initialize() {
    await findSettingsRequest();
  }

  return {
    isLoading,
    settings,
    studentView,
    initialize,
    getSetting,
  };
});
