<template>
  <div v-if="sessions.length" class="grid">
    <SessionCardList :sessions="sessions"/>
  </div>

  <div v-else>
<!--    <div class="bg-gradient-to-r from-gray-100 to-gray-50 flex flex-col rounded-md text-center p-2">-->
<!--      <div class="p-10 text-center">-->
<!--        <div>-->
<!--          <v-icon-->
<!--              icon="mdi-google-classroom"-->
<!--              size="72px"-->
<!--              class="font-extrabold text-transparent bg-clip-text bg-gradient-to-br from-primary to-primary-gradient"-->
<!--          />-->
<!--        </div>-->

<!--        <div class="mt-2 font-bold">-->
<!--          {{ $t("You don't have any session yet.") }}-->
<!--        </div>-->
<!--        <div>-->
<!--          {{ $t('Go to "Explore" to find a topic of interest, or wait for someone to subscribe you.') }}-->
<!--        </div>-->
<!--      </div>-->
<!--    </div>-->
  </div>
</template>

<script>
import SessionCardList from './SessionCardList.vue';

export default {
  name: 'SessionListWrapper',
  components: {
    SessionCardList,
  },
  props: {
    sessions: Array,
  },
  setup() {
  }
}

</script>
