<template>
    <Toolbar
        :handle-send="onSendForm"
    />
    <CCalendarEventForm
      ref="createForm"
      :values="item"
      :errors="violations"
    >
    </CCalendarEventForm>
    <Loading :visible="isLoading" />
</template>

<style src="vue-multiselect/dist/vue-multiselect.css"></style>

<script>
import {mapActions, mapGetters, useStore} from 'vuex';
import { createHelpers } from 'vuex-map-fields';
import CCalendarEventForm from '../../components/ccalendarevent/Form.vue';
import Loading from '../../components/Loading.vue';
import Toolbar from '../../components/Toolbar.vue';
import CreateMixin from '../../mixins/CreateMixin';
import {computed, ref} from "vue";
import axios from "axios";
import {ENTRYPOINT} from "../../config/entrypoint";
import useVuelidate from "@vuelidate/core";
import {useRoute, useRouter} from "vue-router";
import isEmpty from "lodash/isEmpty";
const servicePrefix = 'Message';

const { mapFields } = createHelpers({
  getterType: 'message/getField',
  mutationType: 'message/updateField'
});

export default {
  name: 'CCalendarEventCreate',
  servicePrefix,
  mixins: [CreateMixin],
  components: {
    CCalendarEventForm,
    Loading,
    Toolbar,
  },
  setup () {
    const users = ref([]);
    const item = ref([]);
    const isLoadingSelect = ref(false);
    const parentResourceNodeId = ref(null);
    const route = useRoute();
    const router = useRouter();
    const store = useStore();
    /*const user = computed(() => store.getters['security/getUser']);
    parentResourceNodeId.value = user.value.resourceNode['id']


    let id = route.params.id;
    if (isEmpty(id)) {
      id = route.query.id;
    }
    let message = find(decodeURIComponent(id));

    console.log(id);
    console.log(message);
    item.value.title = message.title;*/

    return {v$: useVuelidate(), users, isLoadingSelect};
  },
  created() {
    this.item.parentResourceNodeId = this.currentUser.resourceNode['id'];
  },
  data() {
    return {
      item: {},
    };
  },
  computed: {
    ...mapFields(['error', 'isLoading', 'created', 'violations']),
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'currentUser': 'security/getUser',
    }),
  },
  methods: {
    ...mapActions('ccalendarevent', ['create', 'createWithFormData', 'reset'])
  }
};
</script>
