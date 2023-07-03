<template>
  <form @submit.prevent="submitForm">
    <div class="field">
      <div class="p-float-label">
        <input
          id="link_url"
          v-model="formData.url"
          name="url"
          type="text"
          class="p-inputtext p-component p-filled"
        />
        <label for="link_url">
          <span class="form_required">*</span>
          URL
        </label>
      </div>
    </div>
    <div class="field">
      <div class="p-float-label">
        <input
          id="link_title"
          v-model="formData.title"
          name="title"
          type="text"
          class="p-inputtext p-component p-filled"
        />
        <label for="link_title">
          <span class="form_required">*</span>
          Link name
        </label>
      </div>
    </div>
    <div class="field">
      <div class="p-float-label">
        <textarea id="description" v-model="formData.description" name="description"></textarea>
        <label for="description">
          Description
        </label>
      </div>
    </div>

    <div class="field">
      <div class="p-float-label">
        <select
          id="link_category_id"
          v-model="formData.category"
          name="category_id"
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
          <input
            id="qf_88d91d"
            v-model="formData.showOnHomepage"
            class="appearance-none checked:bg-support-4 outline-none"
            name="on_homepage"
            type="checkbox" value="1"
          />
          <label for="qf_88d91d">
            Show link on course homepage
          </label>
        </div>
      </div>
    </div>
    <div class="field">
      <div class="p-float-label">
        <select
          id="link_target"
          v-model="formData.target"
          name="target"
          class="p-dropdown p-component p-inputwrapper p-inputwrapper-filled"
        >
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
        <button id="link_submitLink" class="btn btn--primary" name="submitLink" type="submit">
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

<script setup>
import {RESOURCE_LINK_PUBLISHED} from "../resource_links/visibility";
import linkService from "../../services/linkService";
import {useRoute, useRouter} from 'vue-router';
import {useI18n} from "vue-i18n";
import {onMounted, ref} from "vue";
import {useCidReq} from "../../composables/cidReq";

const {t} = useI18n();
const {cid, sid} = useCidReq()
const router = useRouter()
const route = useRoute()

const props = defineProps({
  linkId: {
    type: Number,
    default: null
  },
})

const parentResourceNodeId = ref(Number(route.params.node));

const resourceLinkList = ref(
  JSON.stringify([
    {
      sid,
      cid,
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

onMounted(() => {
  fetchCategories();
  fetchLink();
});

const fetchCategories = async () => {
  try {
    categories.value = await linkService.getCategories()
  } catch (error) {
    console.error('Error fetching categories:', error)
  }
};

const fetchLink = async () => {
  if (props.linkId) {
    try {
      const response = await linkService.getLink(props.linkId)
      formData.value = response
      if (response.category) {
        formData.value.category = parseInt(response.data.category["@id"].split("/").pop())
      }
    } catch (error) {
      console.error('Error fetching link:', error)
    }
  }
}

const submitForm = async () => {
  const postData = {
    url: formData.value.url,
    title: formData.value.title,
    description: formData.value.description,
    category: formData.value.category,
    showOnHomepage: formData.value.showOnHomepage,
    target: formData.value.target,
    parentResourceNodeId: parentResourceNodeId.value,
    resourceLinkList: resourceLinkList.value,
  }
  try {
    if (props.linkId) {
      await linkService.updateLink(props.linkId, postData)
    } else {
      await linkService.createLink(postData)
    }

    await router.push({
      name: "LinksList",
      query: route.query,
    })
  } catch (error) {
    console.error('Error updating link:', error)
  }
}
</script>
