<template>
  <!--        :handle-submit="onSendMessageForm"-->
  <MessageForm v-model:attachments="attachments" :values="item">
    <!--          @input="v$.item.receiversTo.$touch()"-->

    <BaseAutocomplete id="to" v-model="usersTo" :label="t('To')" :search="asyncFind" is-multiple />

    <BaseAutocomplete id="cc" v-model="usersCc" :label="t('Cc')" :search="asyncFind" is-multiple />

    <div class="field">
      <TinyEditor
        v-model="item.content"
        :init="{
          skin_url: '/build/libs/tinymce/skins/ui/oxide',
          content_css: '/build/libs/tinymce/skins/content/default/content.css',
          branding: false,
          relative_urls: false,
          height: 500,
          toolbar_mode: 'sliding',
          file_picker_callback: browser,
          autosave_ask_before_unload: true,
          plugins: [
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste wordcount emoticons',
          ],
          toolbar:
            'undo redo | bold italic underline strikethrough | insertfile image media template link | fontselect fontsizeselect formatselect | alignleft aligncenter alignright alignjustify | outdent indent |  numlist bullist | forecolor backcolor removeformat | pagebreak | charmap emoticons | fullscreen  preview save print | code codesample | ltr rtl',
        }"
        required
      />
    </div>

    <BaseButton :label="t('Send')" icon="plus" type="primary" @click="onSubmit" />
  </MessageForm>
  <Loading :visible="isLoading" />
</template>

<script setup>
import { useStore } from "vuex";
import MessageForm from "../../components/message/Form.vue";
import Loading from "../../components/Loading.vue";
import { computed, ref } from "vue";
import axios from "axios";
import { ENTRYPOINT } from "../../config/entrypoint";
import BaseAutocomplete from "../../components/basecomponents/BaseAutocomplete.vue";
import BaseButton from "../../components/basecomponents/BaseButton.vue";
import { useI18n } from "vue-i18n";
import { useRoute, useRouter } from "vue-router";
import { MESSAGE_TYPE_INBOX } from "../../components/message/constants";

const store = useStore();
const router = useRouter();
const route = useRoute();
const { t } = useI18n();

const asyncFind = (query) => {
  return axios
    .get(ENTRYPOINT + "users", {
      params: {
        username: query,
      },
    })
    .then((response) => {
      let data = response.data;

      return (
        data["hydra:member"]?.map((member) => ({
          name: member.fullName,
          value: member["@id"],
        })) ?? []
      );
    })
    .catch(function (error) {
      console.log(error);
    });
};

const currentUser = computed(() => store.getters["security/getUser"]);

const item = ref({
  sender: currentUser.value["@id"],
  receivers: [],
  msgType: MESSAGE_TYPE_INBOX,
  title: "",
  content: "",
});

const attachments = ref([]);

const usersTo = ref([]);

const usersCc = ref([]);

const receiversTo = computed(() =>
  usersTo.value.map((userTo) => ({
    receiver: userTo,
    receiverType: 1,
  }))
);

const receiversCc = computed(() =>
  usersCc.value.map((userCc) => ({
    receiver: userCc,
    receiverType: 2,
  }))
);

const isLoading = computed(() => store.getters["message/isLoading"]);
const messageCreated = computed(() => store.state.message.created);

const onSubmit = () => {
  item.value.receivers = [...receiversTo.value, ...receiversCc.value];

  store.dispatch("message/create", item.value).then(() => {
    if (attachments.value.length > 0) {
      attachments.value.forEach((attachment) =>
        store.dispatch("messageattachment/createWithFormData", {
          messageId: messageCreated.value.id,
          file: attachment,
        })
      );
    }

    router.push({
      name: "MessageList",
      query: route.query,
    });
  });
};
</script>
