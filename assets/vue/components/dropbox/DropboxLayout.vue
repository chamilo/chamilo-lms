<template>
  <div class="dbx">
    <BaseToolbar
      class="dbx-toolbar"
      :showTopBorder="true"
    >
      <template #start>
        <h2 class="text-lg font-semibold m-0">{{ t("Dropbox") }}</h2>
      </template>

      <template #end>
        <RouterLink
          class="nav-link"
          :to="{ name: 'DropboxListReceived', params: $route.params, query: { ...$route.query, tab: 'received' } }"
        >
          {{ t("Received") }}
        </RouterLink>
        <RouterLink
          class="nav-link"
          :to="{ name: 'DropboxListSent', params: $route.params, query: { ...$route.query, tab: 'sent' } }"
        >
          {{ t("Sent") }}
        </RouterLink>
      </template>
    </BaseToolbar>

    <section class="dbx-body">
      <RouterView />
    </section>
  </div>
</template>

<script setup>
import BaseToolbar from "../basecomponents/BaseToolbar.vue"
import { useI18n } from "vue-i18n"

const { t } = useI18n()
</script>

<style scoped>
.dbx {
  width: 100%;
  min-height: 100%;
  display: flex;
  flex-direction: column;
  background: var(--surface-ground, #fff);
}
.dbx-toolbar {
  position: sticky;
  top: 0;
  z-index: 20;
  background: var(--surface-card, #fff);
}
.dbx-toolbar :deep(.p-toolbar) {
  border-radius: 0;
}
.dbx-body {
  padding: 16px 24px;
}
.nav-link {
  padding: 0.25rem 0.75rem;
  border-radius: 0.5rem;
  color: var(--text-color, #334155);
  text-decoration: none;
  font-weight: 500;
}
.nav-link:hover {
  background: color-mix(in srgb, var(--primary-color, #2563eb) 10%, transparent);
  color: var(--primary-color, #2563eb);
}
a.router-link-active.nav-link {
  background: color-mix(in srgb, var(--primary-color, #2563eb) 15%, transparent);
  color: var(--primary-color, #2563eb);
}
.dbx-body :deep(.p-datatable),
.dbx-body :deep(.uppy-Dashboard-inner) {
  width: 100%;
}
</style>
