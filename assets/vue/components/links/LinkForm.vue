<template>
  <form @submit.prevent="submitForm">
    <div class="field">
      <div class="p-float-label">
        <input v-model="formData.url" name="url" type="text" id="link_url" class="p-inputtext p-component p-filled" />
        <label for="link_url">
          <span class="form_required">*</span>
          URL
        </label>
      </div>
    </div>
    <div class="field">
      <div class="p-float-label">
        <input v-model="formData.title" name="title" type="text" id="link_title" class="p-inputtext p-component p-filled" />
        <label for="link_title">
          <span class="form_required">*</span>
          Link name
        </label>
      </div>
    </div>
    <div class="field">
      <div class="p-float-label">
        <textarea v-model="formData.description" id="description" name="description"></textarea>
        <label for="description">
          Description
        </label>
      </div>
    </div>

    <div class="field">
      <div class="p-float-label">
        <select
          v-model="formData.category"
          name="category_id"
          id="link_category_id"
          class="p-dropdown p-component p-inputwrapper p-inputwrapper-filled"
        >
          <option value="0">--</option>
          <option
            v-for="categoryItem in categories"
            :value="categoryItem.iid"
            :key="categoryItem.iid"
          >
            {{ categoryItem.categoryTitle }}
          </option>
        </select>
        <label for="link_category_id">Category</label>
      </div>
    </div>

    <div class="field 2">
      <div class="8">
        <label for="qf_88d91d" class="h-4"></label>
        <div id="on_homepage" class="field-checkbox">
          <input v-model="formData.showOnHomepage" class="appearance-none checked:bg-support-4 outline-none" name="on_homepage" type="checkbox" value="1" id="qf_88d91d" />
          <label for="qf_88d91d">
            Show link on course homepage
          </label>
        </div>
      </div>
    </div>
    <div class="field">
      <div class="p-float-label">
        <select v-model="formData.target" name="target" id="link_target" class="p-dropdown p-component p-inputwrapper p-inputwrapper-filled">
          <option value="_self">Open self</option>
          <option value="_blank">Open blank</option>
          <option value="_parent">Open parent</option>
          <option value="_top">Open top</option>
        </select>
        <label for="link_target">
          Link's target
        </label>
      </div>
      <small>Select the target which shows the link on the homepage of the course</small>
    </div>
    <div class="field 2">
      <div class="8">
        <label for="link_submitLink" class="h-4"></label>
        <button class="btn btn--primary" name="submitLink" type="submit" id="link_submitLink">
          <em class="mdi mdi-check"></em> Save links
        </button>
      </div>
    </div>
    <div class="form-group">
      <div class="col-sm-offset-2 col-sm-10">
        <span class="form_required">*</span>
        <small>Required field</small>
      </div>
    </div>
  </form>
</template>

<script>
import axios from "axios";
import { ENTRYPOINT } from "../../config/entrypoint";
import { RESOURCE_LINK_PUBLISHED } from "../resource_links/visibility";
import { useRoute, useRouter } from 'vue-router';
import { useI18n } from "vue-i18n";
import { ref, onMounted } from "vue";

export default {
  props: {
    linkId: {
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
      url: 'http://',
      title: '',
      description: '',
      category: 0,
      showOnHomepage: false,
      target: '_blank',
    });

    const categories = ref([]);
    const currentCategory = ref(0);

    const fetchCategories = () => {
      axios.get(ENTRYPOINT + 'link_categories')
        .then(response => {
          categories.value = response.data['hydra:member']
        })
        .catch(error => {
          console.error('Error fetching categories:', error);
        });
    };

    const fetchLink = () => {
      if (props.linkId) {
        axios.get(ENTRYPOINT + 'links/' + props.linkId)
          .then(response => {

            console.log("fetchLink");
            console.log(response.data);

            formData.value = response.data;
            if (response.data.category) {
              formData.value.category = parseInt(response.data.category["@id"].split("/").pop());
            }
            console.log('currentCategory', currentCategory.value);
            console.log('formData.category', formData.value.category);
          })
          .catch(error => {
            console.error('Error fetching link:', error);
          });
      }
    };

    onMounted(() => {
      fetchCategories();
      fetchLink();
    });

    const submitForm = () => {
      const postData = {
        url: formData.value.url,
        title: formData.value.title,
        description: formData.value.description,
        category: formData.value.category,
        showOnHomepage: formData.value.showOnHomepage,
        target: formData.value.target,
        parentResourceNodeId: parentResourceNodeId.value,
        resourceLinkList: resourceLinkList.value,
      };

      if (props.linkId) {
        const endpoint = `${ENTRYPOINT}links/${props.linkId}`;
        postData.id = props.linkId;
        axios.put(endpoint, postData)
          .then(response => {
            console.log('Link updated:', response.data);

            router.push({
              name: "LinksList",
              query: route.query,
            });
          })
          .catch(error => {
            console.error('Error updating link:', error);
          });
      } else {
        const endpoint = `${ENTRYPOINT}links`;

        axios.post(endpoint, postData)
          .then(response => {
            console.log('Link created:', response.data);

            router.push({
              name: "LinksList",
              query: route.query,
            });
          })
          .catch(error => {
            console.error('Error creating link:', error);
          });
      }
    };

    return {
      formData,
      categories,
      currentCategory,
      submitForm
    };
  },
};
</script>
