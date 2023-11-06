<template>
  <div class="app-topbar">
    <Menubar
      :model="menuItems"
    >
      <template #start>
        <img
          alt="Chamilo LMS"
          :src="headerLogo"
        >
      </template>
    </Menubar>
  </div>
</template>

<script setup>
import {ref} from 'vue';
import Menubar from 'primevue/menubar';
import headerLogoPath from "../../../../assets/css/themes/chamilo/images/header-logo.svg";

function setLanguage(event) {
  const {label} = event.item;

  const selectorIndex = menuItems.value.findIndex(item => 'language_selector' === item.key);

  menuItems.value[selectorIndex] ? menuItems.value[selectorIndex].label = label : null;
}

const menuItems = ref([
  {
    label: 'Register oneself',
    url: '/main/auth/inscription.php',
  },
  {
    label: 'FAQ',
    url: '/web/faq',
  },
  {
    label: 'Contact',
    url: '/web/contact'
  },
  {
    key: 'language_selector',
    label: 'English',
    items: [
      {
        label: 'English',
        command: setLanguage,
      },
      {
        label: 'French',
        command: setLanguage,
      },
      {
        label: 'Spanish',
        command: setLanguage,
      },
    ],
  }
]);

const headerLogo = headerLogoPath;
</script>
