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
import {computed, onMounted, ref} from "vue";
import useVuelidate from "@vuelidate/core";
import {useRoute, useRouter} from "vue-router";
import isEmpty from "lodash/isEmpty";
import {RESOURCE_LINK_PUBLISHED} from "../../components/resource_links/visibility.js";
import axios from 'axios'
import { ENTRYPOINT } from '../../config/entrypoint'
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
    const store = useStore();
    const route = useRoute();

    let id = route.params.id;
    if (isEmpty(id)) {
      id = route.query.id;
    }

    let toolId = route.params.courseTool;

    // Get the current intro text.
    axios.get(ENTRYPOINT + 'c_tool_intros/'+toolId).then(response => {
      let data = response.data;
      item.value['introText'] = data.introText;
    }).catch(function (error) {
      console.log(error);
    });

    const currentUser = computed(() => store.getters['security/getUser']);
    item.value['parentResourceNodeId'] = currentUser.value.resourceNode['id'];
    item.value['courseTool'] = '/api/c_tools/'+toolId;

    console.log('parentResourceNodeId : ' + item.value['parentResourceNodeId']);

    return {v$: useVuelidate(), users, isLoadingSelect, item};
  },
  computed: {
    ...mapFields(['error', 'isLoading', 'created', 'violations']),
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'currentUser': 'security/getUser',
    }),
  },
  created() {
    /*console.log('In created() : ' + this.$route.params.node);
    this.item.parentResourceNodeId = this.$route.params.node;
    this.item.resourceLinkList = JSON.stringify([{
      gid: this.$route.query.gid,
      sid: this.$route.query.sid,
      cid: this.$route.query.cid,
      visibility: RESOURCE_LINK_PUBLISHED, // visible by default
    }]);*/
  },
  methods: {
    ...mapActions('ctoolintro', ['create', 'createWithFormData'])
  }
};
</script>
