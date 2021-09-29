<template>
  <Toolbar
      :handle-submit="onSendForm"
      :handle-reset="resetForm"
  />

  <ToolIntroForm
      ref="createForm"
      :values="item"
      :errors="violations"
  />
  <Loading :visible="isLoading" />
</template>

<script>
import { mapActions, mapGetters } from 'vuex'
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
const servicePrefix = 'ToolIntro';

const { mapFields } = createHelpers({
  getterType: 'ctoolintro/getField',
  mutationType: 'ctoolintro/updateField'
});

export default {
  name: 'ToolIntroCreate',
  servicePrefix,
  components: {
    Loading,
    Toolbar,
    ToolIntroForm
  },
  mixins: [CreateMixin],
  data() {
    return {
      item: {}
    };
  },
  computed: {
    ...mapFields(['error', 'isLoading', 'created', 'violations']),
    ...mapGetters({
      'isAuthenticated': 'security/isAuthenticated',
      'currentUser': 'security/getUser',
    }),
  },
  created() {
    this.item.parentResourceNodeId = this.$route.params.node;
    this.item.resourceLinkList = JSON.stringify([{
      gid: this.$route.query.gid,
      sid: this.$route.query.sid,
      cid: this.$route.query.cid,
      visibility: RESOURCE_LINK_PUBLISHED, // visible by default
    }]);
  },
  methods: {
    ...mapActions('ctoolintro', ['create', 'createWithFormData'])
  }
};
</script>
