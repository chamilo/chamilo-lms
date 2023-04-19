<template>
  <MegaMenu :model="menuItems" class="app-topbar">
    <template #start>
      <img
        alt="Chamilo LMS"
        src="/build/css/themes/chamilo/images/header-logo.svg"
      />
    </template>

    <template #item="{ item }">
      <router-link v-if="item.to" :to="item.to" class="p-menuitem-link">
        <span :class="item.icon" class="p-menuitem-icon mx-0" />
        <span class="p-menuitem-text hidden">{{ item.label }}</span>
      </router-link>
      <a
        v-if="item.url"
        :href="item.url"
        aria-controls="user-submenu"
        aria-haspopup="true"
        class="p-menuitem-link"
      >
        <span :class="item.icon" class="p-menuitem-icon mx-0" />
        <span class="p-menuitem-text hidden">{{ item.label }}</span>
      </a>
    </template>

    <template #end>
      <Avatar
        :image="currentUser.illustrationUrl"
        class="cursor-pointer"
        shape="circle"
        @click="toogleUserMenu"
      />
    </template>
  </MegaMenu>

  <Menu
    id="user-submenu"
    ref="elUserSubmenu"
    :model="userSubmenuItems"
    :popup="true"
    class="app-topbar__user-submenu"
  />
</template>

<script setup>
import { ref } from "vue";
import { useRoute } from "vue-router";

import MegaMenu from "primevue/megamenu";
import Avatar from "primevue/avatar";
import Menu from "primevue/menu";
import { usePlatformConfig } from "../../store/platformConfig";

// eslint-disable-next-line no-undef
const props = defineProps({
  currentUser: {
    required: true,
    type: Object,
  },
});

const route = useRoute();

const platformConfigStore = usePlatformConfig();

const menuItems = ref([
  {
    label: "Tickets",
    icon: "pi pi-fw pi-ticket",
    url: (function () {
      const queryParams = new URLSearchParams(window.location.href);

      const cid =
        route.query.cid || route.params.id || queryParams.get("cid") || 0;
      const sid = route.query.sid || queryParams.get("sid") || 0;
      const gid = route.query.gid || queryParams.get("gid") || 0;

      return `/main/ticket/tickets.php?project_id=1&cid=${cid}&sid=${sid}&gid=${gid}`;
    })(),
    visible:
      "true" !==
      platformConfigStore.getSetting("display.show_link_ticket_notification"),
    items: [],
  },
  {
    label: "Profile",
    icon: "pi pi-fw pi-user",
    to: "/account/home",
    items: [],
  },
  {
    label: "Inbox",
    icon: "pi pi-fw pi-inbox",
    to: "/resources/messages",
    items: [],
  },
]);

const elUserSubmenu = ref(null);
const userSubmenuItems = [
  {
    label: props.currentUser.fullName,
    items: [
      {
        label: "Settings",
        url: "/account/edit",
      },
    ],
  },
];

function toogleUserMenu(event) {
  elUserSubmenu.value.toggle(event);
}
</script>
