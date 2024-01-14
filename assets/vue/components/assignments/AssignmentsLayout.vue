<template>
  <div class="flex gap-2">
    <h2 v-t="'Assignments'" class="mr-auto" />

    <StudentViewButton />

    <BaseButton
      v-if="isCurrentTeacher && menuItems.length > 0"
      icon="cog"
      only-icon
      popup-identifier="course-tmenu"
      type="black"
      @click="toggleCourseTMenu"
    />

    <BaseMenu v-if="isCurrentTeacher && menuItems.length > 0" id="course-tmenu" ref="menu" :model="menuItems" />
  </div>

  <hr />

  <router-view></router-view>
</template>

<script setup>
import StudentViewButton from "../StudentViewButton.vue";
import BaseButton from "../basecomponents/BaseButton.vue";
import BaseMenu from "../basecomponents/BaseMenu.vue";
import { computed, ref, watchEffect } from "vue";
import { useRoute } from "vue-router";
import { useStore } from "vuex";
import { useI18n } from "vue-i18n";
import { useCidReq } from "../../composables/cidReq";

const route = useRoute();
const store = useStore();
const { t } = useI18n();

const { cid, sid, gid } = useCidReq();

const isCurrentTeacher = computed(() => store.getters["security/isCurrentTeacher"]);

const menu = ref(null);

const menuItems = ref([]);

const toggleCourseTMenu = (event) => {
  menu.value.toggle(event);
};

watchEffect(() => {
  menuItems.value = [];

  if ("AssigmnentsList" === route.name) {
    menuItems.value = [
      {
        label: t("Create assignment"),
        to: {
          name: "AssigmnentsCreate",
          query: { cid, sid, gid },
        },
      },
    ];
  }
});
</script>
