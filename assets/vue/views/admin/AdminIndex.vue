<template>
  <div class="space-y-4 admin-index">
    <AdminBlock
      v-for="(block, index) in blocks"
      :key="index"
      :class="block.className"
      :description="block.description"
      :icon="block.icon"
      :items="block.items"
      :search-url="block.searchUrl"
      :title="block.title"
    />

    <div v-if="isAdmin" class="block-admin-version p-4 rounded-lg shadow-lg space-y-3">
      <h4 v-t="'Version Check'" />

      <div v-if="'false' === platformConfigurationStore.getSetting('platform.registered')" class="admin-block-version">
        <i18n-t
          class="mb-3"
          keypath="In order to enable the automatic version checking you have to register your portal on chamilo.org. The information obtained by clicking this button is only for internal use and only aggregated data will be publicly available (total number of portals, total number of Chamilo course, total number of Chamilo users, ...) (see {0}). When registering you will also appear on the worldwide list ({1}). If you do not want to appear in this list you have to check the checkbox below. The registration is as easy as it can be: you only have to click this button:"
          tag="p"
        >
          <a href="https://www.chamilo.org/stats/" target="_blank" v-text="'https://www.chamilo.org/stats/'" />
          <a
            href="https://www.chamilo.org/community.php"
            target="_blank"
            v-text="'https://www.chamilo.org/community.php'"
          />
        </i18n-t>

        <form
          id="VersionCheck"
          class="version-checking"
          method="post"
          name="VersionCheck"
          @submit.prevent="checkVersionOnSubmit"
        >
          <div class="field-checkbox">
            <Checkbox v-model="doNotListCampus" binary input-id="checkbox" name="donotlistcampus" />
            <label v-t="'Hide campus from public platforms list'" for="checkbox" />
          </div>

          <Button id="register" :label="t('Enable version check')" name="Register" severity="secondary" type="submit" />
        </form>
      </div>
      <div ref="blockAdminVersionCheck" class="block-admin-version_check" />
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import { useStore } from "vuex";
import { useToast } from "primevue/usetoast";
import Button from "primevue/button";
import Checkbox from "primevue/checkbox";
import AdminBlock from "../../components/admin/AdminBlock";
import axios from "axios";

import { usePlatformConfig } from "../../store/platformConfig";

import { useBlockUsersItems } from "../../components/admin/items/blockUsersItems";
import { useBlockSkillsItems } from "../../components/admin/items/blockSkillsItems";
import { useBlockCoursesItems } from "../../components/admin/items/blockCoursesItems";
import { useBlockChamiloItems } from "../../components/admin/items/blockChamiloItems";
import { useBlockPlatformItems } from "../../components/admin/items/blockPlatformItems";
import { useBlockSessionsItems } from "../../components/admin/items/blockSessionsItems";
import { useBlockSettingsItems } from "../../components/admin/items/blockSettingsItems";

const { t } = useI18n();

const store = useStore();
const platformConfigurationStore = usePlatformConfig();

const toast = useToast();

const isAdmin = computed(() => store.getters["security/isAdmin"]);

const blocks = ref([]);

// Users
blocks.value.push({
  className: "block-admin-users",
  icon: "account",
  title: t("User management"),
  description: t("Here you can manage registered users within your platform"),
  searchUrl: "/main/admin/user_list.php",
  editable: isAdmin.value,
  items: useBlockUsersItems(),
});

if (isAdmin.value) {
  // Courses
  blocks.value.push({
    className: "block-admin-courses",
    icon: "book-open-page-variant",
    title: t("Course management"),
    description: t("Create and manage your courses in a simple way"),
    searchUrl: "/main/admin/course_list.php",
    editable: true,
    items: useBlockCoursesItems(),
  });
}

// Sessions
blocks.value.push({
  className: "block-admin-sessions",
  icon: "google-classroom",
  title: t("Sessions management"),
  description: t("Create course packages for a certain time with training sessions"),
  searchUrl: "/main/session/session_list.php",
  editable: isAdmin.value,
  items: useBlockSessionsItems(),
});

if (isAdmin.value) {
  // SKills
  if ("true" === platformConfigurationStore.getSetting("skill.allow_skills_tool")) {
    blocks.value.push({
      className: "block-admin-skills",
      icon: "certificate",
      title: t("Skills and gradebook"),
      description: t("Manage the skills of your users, through courses and badges"),
      editable: false,
      items: useBlockSkillsItems(),
    });
  }

  if ("true" === platformConfigurationStore.getSetting("gradebook.gradebook_dependency")) {
    blocks.value.push({
      className: "block-admin-gradebook",
      icon: "certificate",
      title: t("Assessments"),
      editable: false,
      items: [],
    });
  }

  // Platform
  blocks.value.push({
    className: "block-admin-platform",
    icon: "cogs",
    title: t("Platform management"),
    description: t("Configure your platform, view reports, publish and send announcements globally"),
    searchUrl: "/admin/settings/search_settings/",
    editable: true,
    items: useBlockPlatformItems(),
  });

  // Settings
  blocks.value.push({
    className: "block-admin-settings",
    icon: "tools",
    title: t("System"),
    description: t("View the status of your server, perform performance tests"),
    editable: false,
    items: useBlockSettingsItems(),
  });

  // Chamilo.org
  blocks.value.push({
    className: "block-admin-chamilo",
    icon: "cogs",
    title: "Chamilo.org",
    description: t("Learn more about Chamilo and its use, official references links"),
    editable: false,
    items: useBlockChamiloItems(),
  });
}

const doNotListCampus = ref(false);

function checkVersionOnSubmit() {
  axios
    .post("/admin/register-campus", {
      donotlistcampus: doNotListCampus.value,
    })
    .then(() =>
      toast.add({
        severity: "success",
        detail: t("Version check enabled"),
      })
    );
}

const blockAdminVersionCheck = ref();

onMounted(() => {
  if ("false" === platformConfigurationStore.getSetting("admin.admin_chamilo_announcements_disable")) {
    axios
      .get("/main/inc/ajax/admin.ajax.php?a=get_latest_news")
      .then(({ data }) => toast.add({ severity: "info", detail: data }));
  }

  axios.get("/main/inc/ajax/admin.ajax.php?a=version").then(({ data }) => {
    if (blockAdminVersionCheck.value) {
      blockAdminVersionCheck.value.innerHTML += data;
    }
  });
});
</script>
