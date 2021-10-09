<template>
  <q-tabs align="left" dense inline-label no-caps>
    <q-route-tab :to="{name: 'MySessionsPast'}" label="Past"/>
    <q-route-tab :to="{name: 'MySessions'}" label="Current"/>
    <q-route-tab :to="{name: 'MySessionsUpcoming'}" label="Upcoming"/>
  </q-tabs>
</template>

<script>

export default {
  name: 'SessionTabs',
  setup() {
  }
}

</script>
