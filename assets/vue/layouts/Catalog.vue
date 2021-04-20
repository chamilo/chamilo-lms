<template>
  <q-layout>
    <q-tabs align="left" dense inline-label no-caps>
      <q-route-tab to="/catalog/course" label="Courses" />
      <q-route-tab to="/catalog/session" label="Sessions" />
    </q-tabs>

    <!-- this is where the Pages are injected -->
    <q-page-container>
      <router-view></router-view>
    </q-page-container>
  </q-layout>
</template>