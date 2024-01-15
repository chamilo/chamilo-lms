<template>
  <router-view></router-view>
</template>

<script setup>
import { computed, ref, watchEffect } from "vue";
import { useRoute, useRouter } from "vue-router"
import { useStore } from "vuex";
import { useI18n } from "vue-i18n";
import { useCidReq } from "../../composables/cidReq";

const route = useRoute();
const router = useRouter()
const store = useStore();
const { t } = useI18n();

const { cid, sid, gid } = useCidReq();

const isCurrentTeacher = computed(() => store.getters["security/isCurrentTeacher"]);

const menu = ref(null);

const menuItems = ref([]);

const toggleCourseTMenu = (event) => {
  menu.value.toggle(event);
};
</script>
