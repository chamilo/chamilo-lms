<template>
  <Toolbar
      :handle-submit="onSendForm"
  />

  <ToolIntroForm
      ref="createForm"
      :values="item"
      :errors="violations"
  />
  <Loading :visible="isLoading" />
</template>

<script>
import {mapActions, mapGetters, useStore} from 'vuex';
import { createHelpers } from 'vuex-map-fields';
import ToolIntroForm from '../../components/ctoolintro/Form.vue';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import CreateMixin from '../../mixins/CreateMixin';
import {computed, onMounted, reactive, ref, toRefs} from "vue";
import useVuelidate from "@vuelidate/core";
import {useRoute, useRouter} from "vue-router";
import isEmpty from "lodash/isEmpty";
import {RESOURCE_LINK_PUBLISHED} from "../../components/resource_links/visibility.js";
import axios from 'axios'
import { ENTRYPOINT } from '../../config/entrypoint'
import useNotification from "../../components/Notification";
import {useI18n} from "vue-i18n";
import toInteger from "lodash/toInteger";
const servicePrefix = 'ctoolintro';

const { mapFields } = createHelpers({
  getterType: 'ctoolintro/getField',
  mutationType: 'ctoolintro/updateField'
});

export default {
  name: 'ToolIntroCreate',
  servicePrefix,
  mixins: [CreateMixin],
  components: {
    Loading,
    Toolbar,
    ToolIntroForm
  },
  setup() {
    const users = ref([]);
    const isLoadingSelect = ref(false);
    const item = ref({});
    const route = useRoute();
    const router = useRouter();
    const {showNotification} = useNotification();
    const { t } = useI18n();
    const store = useStore();

    let id = route.params.id;
    if (isEmpty(id)) {
      id = route.query.id;
    }

    const cid = toInteger(route.query.cid);
    if (cid) {
      let courseIri = '/api/courses/' + cid;
      store.dispatch('course/findCourse', { id: courseIri });
    }

    let toolId = route.params.courseTool;

    // Get the current intro text.
    axios.get(ENTRYPOINT + 'c_tool_intros/' + toolId).then(response => {
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

    function onCreated(item) {
      showNotification(t('Updated'));
      axios.post('/course/'+cid+'/addToolIntro', {
        iid: item.iid,
        cid: route.query.cid,
        sid: route.query.sid
      }).then(response => {

      }).catch(function (error) {
        console.log(error);
      });
      router.go(-1);
    }

    return {v$: useVuelidate(), users, isLoadingSelect, item, onCreated};
  },
  computed: {
    ...mapFields(['error', 'isLoading', 'created', 'violations']),
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'currentUser': 'security/getUser',
    }),
  },
  methods: {
    ...mapActions('ctoolintro', ['create', 'createWithFormData'])
  }
};
</script>
