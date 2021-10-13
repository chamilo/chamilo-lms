<template>
  <q-tabs align="left" dense inline-label no-caps>
    <q-route-tab :to="{name: 'MySessionsPast'}" :label="$t('Past')"/>
    <q-route-tab :to="{name: 'MySessions'}" :label="$t('Current')"/>
    <q-route-tab :to="{name: 'MySessionsUpcoming'}" :label="$t('Upcoming')"/>
  </q-tabs>
</template>

<script>

export default {
  name: 'SessionTabs',
  setup() {
  }
}

</script>
