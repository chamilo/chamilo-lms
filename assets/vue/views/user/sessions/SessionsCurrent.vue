<template>
  <StickyCourses/>
  <SessionTabs class="mb-4"/>

  <!-- All sessions -->
  <!--  <SessionListWrapper :sessions="sessionList"/>-->

  <SessionCategoryView :result-sessions="sessions"/>
</template>

<script setup>
import {computed} from "vue"
import {useStore} from 'vuex'
import SessionTabs from '../../../components/session/SessionTabs.vue'
import StickyCourses from '../../../views/user/courses/StickyCourses.vue'
import SessionCategoryView from "../../../components/session/SessionCategoryView"
import {useSession} from "./session"

const store = useStore()

let user = computed(() => store.getters['security/getUser'])
const {sessions} = useSession(user)
</script>
