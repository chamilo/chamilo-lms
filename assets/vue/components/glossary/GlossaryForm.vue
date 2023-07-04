<template>
  <div>
    <form @submit.prevent="submitGlossaryForm" name="glossary" id="glossary">
      <div class="field">
        <div class="p-float-label">
          <input v-model="formData.name" id="glossary_title" name="name" type="text" class="p-inputtext p-component p-filled" />

          <label for="glossary_title">
            <span class="form_required">*</span>
            Term
          </label>
        </div>
      </div>
      <div class="field">
        <div class="p-float-label">
          <textarea v-model="formData.description" id="description" name="description"></textarea>

          <label for="description">
            <span class="form_required">*</span>
            Term definition
          </label>
        </div>
      </div>
      <div class="field 2">
        <div class="8">

          <label for="glossary_SubmitGlossary" class="h-4 ">

          </label>

          <button class="btn btn--primary" name="SubmitGlossary" type="submit" id="glossary_SubmitGlossary">
            <em class="mdi mdi-plus"></em> Save term
          </button>
        </div>
      </div>
      <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
          <span class="form_required">*</span>
          <small>Required field</small>
        </div>
      </div>
      <input name="_qf__glossary" type="hidden" value="" id="glossary__qf__glossary" />
      <input name="sec_token" type="hidden" value="1e7d47c276bfdfe308a79e1b71d58089" id="glossary_sec_token" />

    </form>
  </div>
</template>

<script>
import axios from "axios";
import { ENTRYPOINT } from "../../config/entrypoint";
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from "vue-i18n";
import { ref, onMounted } from "vue";
import {RESOURCE_LINK_PUBLISHED} from "../resource_links/visibility";

export default {
  props: {
    termId: {
      type: Number,
      default: null
    }
  },
  setup(props) {
    const route = useRoute();
    const router = useRouter();
    const { t } = useI18n();

    const parentResourceNodeId = ref(Number(route.params.node));

    const resourceLinkList = ref(
      JSON.stringify([
        {
          sid: route.query.sid,
          cid: route.query.cid,
          visibility: RESOURCE_LINK_PUBLISHED, // visible by default
        },
      ])
    );

    const formData = ref({
      name: '',
      description: '',
    });

    const fetchTerm = () => {
      if (props.termId) {
        axios.get(ENTRYPOINT + 'glossaries/' + props.termId)
          .then(response => {
            const glossary = response.data;
            formData.value.name = glossary.name;
            formData.value.description = glossary.description;
          })
          .catch(error => {
            console.error('Error fetching link:', error);
          });
      }
    };

    onMounted(() => {
      fetchTerm();
    });

    const submitGlossaryForm = () => {

      const postData = {
        name: formData.value.name,
        description: formData.value.description,
        parentResourceNodeId: parentResourceNodeId.value,
        resourceLinkList: resourceLinkList.value,
        sid: route.query.sid,
        cid: route.query.cid,
      };

      if (props.termId) {
        const endpoint = `${ENTRYPOINT}glossaries/${props.termId}`;
        axios.put(endpoint, postData)
          .then(response => {
            console.log('Glossary updated:', response.data);

            router.push({
              name: "GlossaryList",
              query: route.query,
            });
          })
          .catch(error => {
            console.error('Error updating Glossary:', error);
          });

      } else {
        const endpoint = `${ENTRYPOINT}glossaries`;
        axios.post(endpoint, postData)
          .then(response => {
            console.log('Glossary created:', response.data);

            router.push({
              name: "GlossaryList",
              query: route.query,
            });
          })
          .catch(error => {
            console.error('Error creating Glossary:', error);
          });

      }
    };

    return {
      formData,
      submitGlossaryForm,
    };
  },
};
</script>
