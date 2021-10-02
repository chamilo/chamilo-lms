<template>
  <div>
    <v-card
        class="mx-auto"
    >
      <CourseForm
          ref="updateForm"
          v-if="item"
          :values="item"
          :errors="violations"
      />
      <Loading :visible="isLoading || deleteLoading" />
      <v-footer>
        <Toolbar
            :handle-submit="onSendForm"
            :handle-reset="resetForm"
            :handle-delete="del"
        />
      </v-footer>
    </v-card>
  </div>
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import ToolIntroForm from '../../components/ctoolintro/Form.vue';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import UpdateMixin from '../../mixins/UpdateMixin';

const servicePrefix = 'ToolIntro';

export default {
  name: 'ToolIntroUpdate',
  servicePrefix,
  mixins: [UpdateMixin],
  components: {
    Loading,
    Toolbar,
    ToolIntroForm
  },

  computed: {
    ...mapFields('ctoolintro', {
      deleteLoading: 'isLoading',
      isLoading: 'isLoading',
      error: 'error',
      updated: 'updated',
      violations: 'violations'
    }),
    ...mapGetters('ctoolintro', ['find'])

  },

  methods: {
    ...mapActions('ctoolintro', {
      createReset: 'resetCreate',
      deleteItem: 'del',
      delReset: 'resetDelete',
      retrieve: 'load',
      update: 'update',
      updateReset: 'resetUpdate'
    })
  }
};
</script>
