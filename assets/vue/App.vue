<template>
  <component
    :is="layout"
    :show-breadcrumb="route.meta.showBreadcrumb"
  >
    <slot />
    <div
      id="legacy_content"
      v-html="legacyContent"
    />
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
import showMessage from "./utils/showMessage";

const apolloClient = new ApolloClient({
  link: createHttpLink({
    uri: '/api/graphql',
  }),
  cache: new InMemoryCache(),
});

provide(DefaultApolloClient, apolloClient);

const route = useRoute();
const router = useRouter();

const layout = computed(
  () => {
    const queryParams = new URLSearchParams(window.location.href);

    if (queryParams.has('lp')
      || (queryParams.has('origin') && 'learnpath' === queryParams.get('origin'))
    ) {
      return 'EmptyLayout';
    }

    return `${router.currentRoute.value.meta.layout ?? 'Dashboard'}Layout`;
  }
);

const legacyContent = ref('');
let isFirstTime = false;

onMounted(
  () => {
    isFirstTime = true;
  }
);

watch(
  route,
  () => {
    legacyContent.value = '';

    const currentUrl = window.location.href;

    if (currentUrl.indexOf('main/') > 0) {
      if (isFirstTime) {
        const content = document.querySelector('#sectionMainContent');

        if (content) {
          content.style.display = 'block';
          document.querySelector('#sectionMainContent').remove();
          legacyContent.value = content.outerHTML;
        }
      } else {
        document.querySelector('#sectionMainContent')?.remove();

        window.location.replace(currentUrl);
      }
    } else {
      if (isFirstTime) {
        const content = document.querySelector("#sectionMainContent");

        if (content) {
          content.style.display = 'block';
          document.querySelector("#sectionMainContent").remove();
          legacyContent.value = content.outerHTML;
        }
      } else {
        document.querySelector("#sectionMainContent")?.remove();

        legacyContent.value = '';
      }
    }

    isFirstTime = false;
  }
);

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

const app = document.getElementById('app');

if (app && app.hasAttribute('data-flashes')) {
  const flashes = JSON.parse(app.getAttribute('data-flashes'));

  for (const key in flashes) {
    for (const flashText in flashes[key]) {
      showMessage(flashes[key][flashText], key);
    }
  }
}

axios.interceptors.response.use(
  undefined,
  (error) => new Promise(() => {
    if (401 === error.response.status) {
      showMessage(error.response.data.error, 'warning');
    } else if (500 === error.response.status) {
      if (error.response) {
        showMessage(error.response.data.detail, 'warning');
      }
    }

    throw error;
  })
);
</script>
