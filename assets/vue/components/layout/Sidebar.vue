<template>
  <aside class="app-sidebar">
    <div class="app-sidebar__container">
      <h3 class="app-sidebar__top">
        {{ t('Menu') }}
      </h3>
      <div class="app-sidebar__panel">
        <PanelMenu :model="items" />
      </div>
      <div class="app-sidebar__bottom">
        <p>{{ t('Created with Chamilo &copy; {year}', { 'year': 2022 }) }}</p>
      </div>
      <a
        v-if="isAuthenticated"
        href="/logout"
        class="app-sidebar__logout-link"
      >
        <span class="pi pi-fw pi-sign-out" />
        <span class="logout-text">{{ t('Sign out') }}</span>
      </a>
    </div>
    <ToggleButton
      v-model="sidebarIsOpen"
      class="app-sidebar__button"
      off-icon="pi pi-fw pi-chevron-right"
      on-icon="pi pi-fw pi-chevron-left"
    />
  </aside>
  
  <Teleport to=".p-megamenu .p-megamenu-end">
    <a
      tabindex="0"
      class="app-sidebar__topbar-button"
      @click="sidebarIsOpen = !sidebarIsOpen"
    >
      <i class="pi pi-times" />
    </a>
  </Teleport>
</template>

<script setup>
import {computed, ref, watch} from 'vue';
import PanelMenu from 'primevue/panelmenu';
import ToggleButton from 'primevue/togglebutton';
import {useI18n} from "vue-i18n";
import {useStore} from "vuex";

const store = useStore();
const {t} = useI18n();

const isAuthenticated = computed(() => store.getters['security/isAuthenticated']);
const isAdmin = computed(() => store.getters['security/isAdmin']);
const isBoss = computed(() => store.getters['security/isBoss']);
const isStudent = computed(() => store.getters['security/isStudent']);

const items = ref([
  {
    label: t('Home'),
    to: '/home',
    icon: 'pi pi-fw pi-home',
  },

  {
    label: t('Courses'),
    icon: 'pi pi-fw pi-book',
    visible: isAuthenticated,
    items: [
      {
        label: t('My courses'),
        to: '/courses',
      },
      {
        label: t('My sessions'),
        to: '/sessions',
      },
    ],
  },
  {
    label: t('Events'),
    to: '/resources/ccalendarevent',
    icon: 'pi pi-fw pi-calendar',
    visible: isAuthenticated,
  },
  {
    label: t('My progress'),
    url: '/main/auth/my_progress.php',
    icon: 'pi pi-fw pi-chart-line',
    visible: isAuthenticated,
  },
  {
    label: t('Social network'),
    to: '/social',
    icon: 'pi pi-fw pi-sitemap',
    visible: isAuthenticated,
  },

  {
    label: t('Diagnosis'),
    icon: 'pi pi-fw pi-search',
    visible: isBoss.value || isStudent.value,
    items: [
      {
        label: t('Management'),
        url: '/main/search/load_search.php',
        visible: isBoss,
      },
      {
        label: t('Search'),
        url: '/main/search/search.php',
        visible: isBoss.value || isStudent.value,
      },
    ],
  },

  {
    label: t('Administration'),
    icon: 'pi pi-fw pi-table',
    visible: isAdmin,
    items: [
      {
        label: t('Administration'),
        url: '/main/admin/index.php',
      },
      {
        label: t('Users'),
        url: '/main/admin/user_list.php',
      },
      {
        label: t('Courses'),
        url: '/main/admin/course_list.php',
      },
      {
        label: t('Sessions'),
        url: '/main/admin/session_list.php',
      },
      {
        label: t('Reporting'),
        url: '/main/mySpace/index.php',
      },
    ],
  },
]);

const sidebarIsOpen = ref(
  window.localStorage.getItem('sidebarIsOpen') === 'true'
);

watch(
  sidebarIsOpen,
  (newValue) => {
    const appEl = document.querySelector('#app');

    window.localStorage.setItem('sidebarIsOpen', newValue.toString());

    appEl.classList.toggle('app--sidebar-inactive', !newValue);
  },
  {
    immediate: true,
  }
);
</script>
