<template>
  <SessionTabs class="mb-4"/>
<!--  <SessionListWrapper :sessions="sessions"/>-->
  <SessionCategoryView :result-sessions="sessions"/>
</template>

<script setup>
import {computed} from "vue"
import {useStore} from 'vuex'
import {DateTime} from "luxon"
import SessionCategoryView from '../../../components/session/SessionCategoryView'
import SessionTabs from '../../../components/session/SessionTabs.vue'
import {useSession} from "./session"


const store = useStore();

let user = computed(() => store.getters['security/getUser']);

let start = DateTime.local().toISO()

const {sessions} = useSession(user, start)
</script>
