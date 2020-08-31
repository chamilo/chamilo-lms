<template>
  <b-alert
    v-model="show"
    :variant="color"
    dismissible
  >
    {{ text }}
    <template v-if="subText">
      <p>{{ subText }}</p>
    </template>
  </b-alert>

  <!--    <v-snackbar-->
  <!--      v-model="show"-->
  <!--      :color="color"-->
  <!--      :multi-line="true"-->
  <!--      :timeout="timeout"-->
  <!--      right-->
  <!--      top-->
  <!--    >-->
  <!--      {{ text }}-->
  <!--      <template v-if="subText">-->
  <!--        <p>{{ subText }}</p>-->
  <!--      </template>-->
  <!--      <v-btn-->
  <!--        dark-->
  <!--        text-->
  <!--        @click.native="close"-->
  <!--      >-->
  <!--        {{ $t('Close') }}-->
  <!--      </v-btn>-->
  <!--    </v-snackbar>-->
</template>

<script>
import { mapFields } from 'vuex-map-fields';

export default {
  computed: {
    ...mapFields('notifications', ['color', 'show', 'subText', 'text', 'timeout'])
  },

  methods: {
    close() {
      this.show = false;
    }
  }
};
</script>
