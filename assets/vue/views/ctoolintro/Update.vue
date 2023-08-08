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
import {computed, onMounted, reactive, ref, toRefs} from "vue";
import {useI18n} from "vue-i18n";
import {useRoute, useRouter} from "vue-router";
import toInteger from "lodash/toInteger";
import useVuelidate from '@vuelidate/core'
import axios from 'axios'
import { ENTRYPOINT } from '../../config/entrypoint'
import { RESOURCE_LINK_PUBLISHED } from '../../components/resource_links/visibility'
import useNotification from '../../components/Notification'

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
    const router = useRouter();
    const {showNotification} = useNotification();
    const store = useStore();
    const item = ref({});
    const cid = toInteger(route.query.cid);
    if (cid) {
      let courseIri = '/api/courses/' + cid;
      store.dispatch('course/findCourse', { id: courseIri });
    }

    let toolId = route.query.ctoolId;
    let ctoolintroId = route.query.ctoolintroIid;

    // Get the current intro text.
    axios.get(ENTRYPOINT + 'c_tool_intros/'+ctoolintroId).then(response => {
      let data = response.data;
      item.value['introText'] = data.introText;
    }).catch(function (error) {
      console.log(error);
    });

    item.value['parentResourceNodeId'] = Number(route.query.parentResourceNodeId);
    item.value['courseTool'] = '/api/c_tools/'+toolId;

    item.value['resourceLinkList'] = [{
      sid: route.query.sid,
      cid: route.query.cid,
      visibility: RESOURCE_LINK_PUBLISHED, // visible by default
    }];

    function onUpdated(val) {
      showNotification(t('Updated'));
      router.go(-1);
    }

    return {v$: useVuelidate(), item, onUpdated};
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
