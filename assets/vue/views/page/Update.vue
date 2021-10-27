<template>
  <Toolbar :handle-submit="onSendForm" :handle-reset="resetForm"></Toolbar>
  <PageForm ref="updateForm" :values="item" :errors="violations" />


  <Loading :visible="isLoading" />
</template>

<script>
import { mapActions, mapGetters } from 'vuex';
import { mapFields } from 'vuex-map-fields';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import UpdateMixin from '../../mixins/UpdateMixin';

const servicePrefix = 'Page';
import PageForm from '../../components/page/Form.vue';
import useVuelidate from "@vuelidate/core";

export default {
  name: 'PageUpdate',
  servicePrefix,
  mixins: [UpdateMixin],
  setup () {
    return { v$: useVuelidate() }
  },
  components: {
    Loading,
    Toolbar,
    PageForm
  },

  computed: {
    ...mapFields('page', {
      deleteLoading: 'isLoading',
      isLoading: 'isLoading',
      error: 'error',
      updated: 'updated',
      violations: 'violations'
    }),
    ...mapGetters('page', ['find'])

  },

  methods: {
    ...mapActions('page', {
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
