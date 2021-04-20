<template>
  <q-layout>
    <q-tabs align="left" dense inline-label no-caps>
      <q-route-tab to="/courses" label="My courses" />
      <q-route-tab to="/sessions" label="My sessions" />
    </q-tabs>

    <!-- this is where the Pages are injected -->
    <q-page-container>
      <router-view></router-view>
    </q-page-container>
  </q-layout>
</template>