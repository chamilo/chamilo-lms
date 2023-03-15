<template>
  <q-layout>
    <q-tabs align="left" dense inline-label no-caps>
      <q-route-tab to="/catalog/course" :label="$t('Courses catalog')" />
      <q-route-tab to="/catalog/session" :label="$t('Sessions catalog')" />
    </q-tabs>

    <!-- this is where the Pages are injected -->
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>
