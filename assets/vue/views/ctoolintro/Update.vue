<template>
  <Toolbar
      :handle-submit="onSendForm"
  />
  <ToolIntroForm
      ref="updateForm"
      v-if="item"
      :values="item"
      :errors="violations"
  />
  <Loading :visible="isLoading || deleteLoading"/>
</template>

<script>
import {mapActions, mapGetters, useStore} from 'vuex';
import { mapFields } from 'vuex-map-fields';
import ToolIntroForm from '../../components/ctoolintro/Form.vue';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import UpdateMixin from '../../mixins/UpdateMixin';
import {useI18n} from "vue-i18n";
import {useRoute, useRouter} from "vue-router";
import toInteger from "lodash/toInteger";

const servicePrefix = 'ctoolintro';

export default {
  name: 'ToolIntroUpdate',
  servicePrefix,
  mixins: [UpdateMixin],
  components: {
    Loading,
    Toolbar,
    ToolIntroForm
  },
  setup() {
    const route = useRoute();
    const store = useStore();
    const cid = toInteger(route.query.cid);
    if (cid) {
      let courseIri = '/api/courses/' + cid;
      store.dispatch('course/findCourse', { id: courseIri });
    }

    /*function updated(val) {
      showNotification(t('Updated'));
      router.go(-1);
    }*/
    //return {updated};
    return;
  },
  computed: {
    ...mapFields('ctoolintro', {
      deleteLoading: 'isLoading',
      isLoading: 'isLoading',
      error: 'error',
      updated: 'updated',
      violations: 'violations'
    }),
    ...mapGetters('ctoolintro', ['find']),
    ...mapGetters({
      'isCurrentTeacher': 'security/isCurrentTeacher',
    }),
  },
  methods: {
    ...mapActions('ctoolintro', {
      createReset: 'resetCreate',
      deleteItem: 'del',
      delReset: 'resetDelete',
      retrieve: 'load',
      update: 'update',
      updateWithFormData: 'updateWithFormData',
      updateReset: 'resetUpdate'
    })
  }
};
</script>
