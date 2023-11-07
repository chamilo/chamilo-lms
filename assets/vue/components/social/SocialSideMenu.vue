<template>
  <BaseCard class="mt-4">
    <div class="text-center text-lg font-bold mb-4">{{ t("Social Network") }}</div>
    <ul class="nav nav-pills nav-stacked">
      <li class="home-icon active">
        <a href="/social">
          <i class="mdi mdi-home" aria-hidden="true"></i>
          {{ t("Social Wall") }}
        </a>
      </li>
      <li class="messages-icon">
        <a href="resources/messages">
          <i class="mdi mdi-email" aria-hidden="true"></i>
          {{ t("Messages") }} <span class="badge badge-warning"></span>
        </a>
      </li>
      <li class="shared-profile-icon">
        <a href="/account/home">
          <i class="mdi mdi-account-circle" aria-hidden="true"></i>
          {{ t("My Profile") }}
        </a>
      </li>
      <li class="friends-icon">
        <a href="/resources/friends">
          <i class="mdi mdi-handshake" aria-hidden="true"></i>
          {{ t("My Friends") }}
        </a>
      </li>
    </ul>
  </BaseCard>
</template>

<script setup>
import BaseCard from "../basecomponents/BaseCard.vue";
import { useI18n } from "vue-i18n"

const { t } = useI18n()
</script>

<style scoped>
.menu-list {
  list-style: none;
  padding: 0;
}

.menu-item {
  display: block;
  padding: 0.5rem 1rem;
  color: #333;
  text-decoration: none;
  transition: background-color 0.2s;
}

.menu-item + .menu-item {
  margin-top: 1rem; /* Adds space between menu items */
}

.nav-pills .nav-link {
  display: flex;
  align-items: center;
}

.fa {
  margin-right: 0.5em;
}
</style>
