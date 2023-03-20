<template>
  <div class="flex justify-between items-center">
    <h2 v-t="'My Courses'" />
    <Button
      v-if="isTeacher"
      class="p-button-secondary md:hidden"
      icon="pi pi-plus"
    />
    <Button
      v-if="isTeacher"
      :label="t('Course')"
      class="p-button-secondary hidden md:inline-flex"
      icon="pi pi-plus"
    />
  </div>
  <hr>
  <router-view />
</template>

<script setup>
import Button from 'primevue/button';
import { computed } from 'vue';
import { useStore } from 'vuex';
import { useI18n } from 'vue-i18n';

const store = useStore();
const { t } = useI18n();

const isTeacher = computed(() => store.getters['security/hasRole']('ROLE_TEACHER'));
</script>
