<template>
  <component
    :is="layout"
    v-if="!platformConfigurationStore.isLoading"
    :show-breadcrumb="route.meta.showBreadcrumb"
  >
    <Toast
      position="top-center"
    >
      <template #message="slotProps">
        <span
          :class="{
            'mdi-close-outline': 'error' === slotProps.message.severity,
            'mdi-information-outline': 'info' === slotProps.message.severity,
            'mdi-check-outline': 'success' === slotProps.message.severity,
            'mdi-alert-outline': 'warn' === slotProps.message.severity
          }"
          class="p-toast-message-icon mdi"
        />
        <div class="p-toast-message-text">
          <span
            v-if="slotProps.message.summary"
            class="p-toast-summary"
            v-text="slotProps.message.summary"
          />
          <div
            class="p-toast-detail"
            v-html="slotProps.message.detail"
          />
        </div>
      </template>
    </Toast>
    <slot />
    <div
      id="legacy_content"
      ref="legacyContainer"
    />
    <ConfirmDialog />
  </component>
</template>

<script setup>
import {computed, onMounted, provide, ref, watch, watchEffect} from 'vue';
import {useRoute, useRouter} from 'vue-router';
import {DefaultApolloClient} from '@vue/apollo-composable';
import {ApolloClient, createHttpLink, InMemoryCache} from '@apollo/client/core';
import {useStore} from "vuex";
import axios from "axios";
import {isEmpty} from "lodash";
import ConfirmDialog from "primevue/confirmdialog";
import Toast from 'primevue/toast';
import { useToast } from 'primevue/usetoast';

const apolloClient = new ApolloClient({
  link: createHttpLink({
    uri: '/api/graphql',
  }),
  cache: new InMemoryCache(),
});

provide(DefaultApolloClient, apolloClient);

const route = useRoute();
const router = useRouter();

const toast = useToast();

const layout = computed(
  () => {
    const queryParams = new URLSearchParams(window.location.search);

    if (queryParams.has('lp')
      || (queryParams.has('origin') && 'learnpath' === queryParams.get('origin'))
    ) {
      return 'EmptyLayout';
    }

    return `${router.currentRoute.value.meta.layout ?? 'Dashboard'}Layout`;
  }
);

const legacyContainer = ref(null);

watch(
  route,
  () => {
    if (legacyContainer.value) {
      legacyContainer.value.innerHTML = '';
    }
  }
);

watchEffect(() => {
  if (!legacyContainer.value) {
    return;
  }

  const content = document.querySelector("#sectionMainContent");

  if (content) {
    legacyContainer.value.appendChild(content);

    const chEditors = window.chEditors || []
    chEditors.forEach((editorConfig) => tinymce.init(editorConfig))

    content.style.display = "block";
  }
});

watchEffect(
  async () => {
    try {
      const component = `${route.meta.layout}.vue`;
      layout.value = component?.default || 'Dashboard';
    } catch (e) {
      layout.value = 'Dashboard';
    }
  }
);

const user = ref({});

let isAuthenticated = false;

if (!isEmpty(window.user)) {
  user.value = window.user;
  isAuthenticated = true;
}

const store = useStore();

const payload = {isAuthenticated, user};

store.dispatch('security/onRefresh', payload);

onMounted(() => {
  const app = document.getElementById('app');

  if (!(app && app.dataset.flashes)) {
    return;
  }

  const flashes = JSON.parse(app.dataset.flashes);

  for (const key in flashes) {
    for (const flashText in flashes[key]) {
      toast.add({
        severity: key,
        detail: flashes[key][flashText],
        life: 3500,
      });
    }
  }
});

axios.interceptors.response.use(
  undefined,
  (error) => new Promise(() => {
    if (401 === error.response.status) {
      toast.add({
        severity: 'warn',
        detail: error.response.data.error,
        life: 3500,
      });
    } else if (500 === error.response.status) {
      toast.add({
        severity: 'warn',
        detail: error.response.data.detail,
        life: 3500,
      });
    }

    throw error;
  })
);

import {usePlatformConfig} from './store/platformConfig';

const platformConfigurationStore = usePlatformConfig();
platformConfigurationStore.initialize();
</script>
