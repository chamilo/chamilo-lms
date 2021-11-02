<template>
  <q-card bordered class="mb-4" flat>
    <q-card-section>
      <q-form
        class="q-gutter-md"
      >
        <q-input
          v-model="content"
          :error="v$.content.$error"
          :label="$t('What are you thinking about?')"
          autogrow
        />

        <div class="row justify-between mt-0">
          <q-file
            v-model="attachment"
            :label="$t('File upload')"
            accept="image/*"
            clearable
            dense
            @change="v$.attachment.$touch()"
          >
            <template v-slot:prepend>
              <q-icon name="attach_file" />
            </template>
          </q-file>

          <q-btn
            :label="$t('Post')"
            icon="send"
            @click="sendPost"
          />
        </div>
      </q-form>
    </q-card-section>
  </q-card>
</template>

<script>
import {reactive, toRefs} from "vue";
import {useStore} from "vuex";
import {MESSAGE_TYPE_WALL} from "../message/msgType";
import useVuelidate from "@vuelidate/core";
import {required} from "@vuelidate/validators";

export default {
  name: "SocialNetworkForm",
  setup() {
    const store = useStore();

    const currentUser = store.getters['security/getUser'];

    const postState = reactive({
      content: '',
      attachment: null,
    });

    const v$ = useVuelidate({
      content: {required},
    }, postState);

    async function sendPost() {
      v$.value.$touch();

      if (v$.value.$invalid) {
        return;
      }

      const messagePayload = {
        title: 'Post',
        content: postState.content,
        msgType: MESSAGE_TYPE_WALL,
        sender: `/api/users/${currentUser.id}`,
        receivers: [
          {
            receiver: `/api/users/${currentUser.id}`,
            receiverType: 1
          }
        ]
      };

      await store.dispatch('message/create', messagePayload);

      if (postState.attachment) {
        const message = store.state.message.created;
        const attachmentPayload = {
          messageId: message.id,
          file: postState.attachment
        };

        await store.dispatch('messageattachment/createWithFormData', attachmentPayload);
      }

      postState.content = '';
      postState.attachment = null;
    }

    return {
      ...toRefs(postState),
      sendPost,
      v$
    }
  }
}
</script>
