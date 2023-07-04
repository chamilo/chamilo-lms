<template>
  <form @submit.prevent="submitForm">
    <div class="field 2">
      <div class="8">
        <label for="glossary_file" class="h-4">
          File
        </label>
        <input class="mt-1" :ref="fileInputRef" name="file" type="file" id="glossary_file" />
      </div>
    </div>
    <div class="field">
      <label>File type</label>
      <div class="field-radiobutton">
        <input name="file_type" value="csv" type="radio" id="qf_85f94d" v-model="fileType" checked="checked" />
        <label for="qf_85f94d" class="">CSV</label>
      </div>
      <div class="field-radiobutton">
        <input name="file_type" value="xls" type="radio" id="qf_bff468" v-model="fileType" />
        <label for="qf_bff468" class="">XLS</label>
      </div>
    </div>
    <div class="field 2">
      <div class="8">
        <div id="replace" class="field-checkbox">
          <input class="appearance-none checked:bg-support-4 outline-none" name="replace" type="checkbox" value="1" id="qf_5b8df0" v-model="replace" />
          <label for="qf_5b8df0" class="">
            Delete all terms before import.
          </label>
        </div>
      </div>
    </div>
    <div class="field 2">
      <div class="8">
        <div id="update" class="field-checkbox">
          <input class="appearance-none checked:bg-support-4 outline-none" name="update" type="checkbox" value="1" id="qf_594e6e" v-model="update" />
          <label for="qf_594e6e" class="">
            Update existing terms.
          </label>
        </div>
      </div>
    </div>
    <div class="field 2">
      <div class="8">
        <button class="btn btn--primary" name="SubmitImport" type="submit" id="glossary_SubmitImport">
          <em class="mdi mdi-check"></em> Import
        </button>
      </div>
    </div>
  </form>
</template>

<script>
import axios from 'axios';
import { ENTRYPOINT } from "../../config/entrypoint";
import { useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { ref } from "vue";
import { RESOURCE_LINK_PUBLISHED } from "../resource_links/visibility";

export default {
  setup() {
    const route = useRoute();
    const router = useRouter();
    const { t } = useI18n();

    const fileInputRef = ref(null);
    const fileType = ref("csv");
    const replace = ref(false);
    const update = ref(false);
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

    const submitForm = async () => {
      const fileInput = document.getElementById('glossary_file');
      const file = fileInput.files[0];
      const formData = new FormData();
      formData.append("file", file);
      formData.append("file_type", fileType.value);
      formData.append("replace", replace.value);
      formData.append("update", update.value);
      formData.append("sid", route.query.sid);
      formData.append("cid", route.query.cid);
      formData.append("parentResourceNodeId", parentResourceNodeId.value);
      formData.append("resourceLinkList", resourceLinkList.value);

      console.log('formData', formData);

      console.log(ENTRYPOINT + 'glossaries/import');
      try {
        // eslint-disable-next-line no-unused-vars
        const response = await axios.post(ENTRYPOINT + 'glossaries/import', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });

        router.push({
          name: "GlossaryList",
          query: route.query,
        });
      } catch (error) {
        fileInputRef.value = null;
        fileType.value = "csv";
        replace.value = false;
        update.value = false;
      }
    };

    return {
      fileInputRef,
      fileType,
      replace,
      update,
      submitForm,
    };
  },
};
</script>
