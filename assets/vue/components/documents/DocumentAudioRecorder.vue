<template>
  <div class="flex flex-col flex-wrap justify-center content-center text-center">
    <BaseInputText
      v-if="audio"
      v-model="recordName"
      :label="t('Enter filename here')"
      :help-text="recordError"
      :is-invalid="recordError !== ''"
      class="max-w-full self-center mb-4 w-60"
    />

    <audio v-if="audio" class="max-w-full self-center mb-4" controls>
      <source :src="window.URL.createObjectURL(audio)"/>
    </audio>

    <AudioRecorder
      ref="audioRecorder"
      :multiple="false"
      :show-buttons="showAudioRecorderButtons"
      :show-recorded-audios="false"
      class="self-center mr-2 mb-4"
      @recorded-audio="processAudio($event)"
    />

    <div v-if="audio" class="flex">
      <BaseButton
        :label="t('Start recording')"
        type="black"
        icon="restart"
        class="mr-2"
        @click="recordAudio"
      />
      <BaseButton
        :label="t('Save recorded audio')"
        type="secondary"
        icon="send"
        class="mr-2"
        @click="saveAudio"
      />
    </div>
  </div>
</template>

<script setup>
import {ref} from "vue";
import { useStore } from "vuex";
import {useI18n} from "vue-i18n";
import BaseInputText from "../basecomponents/BaseInputText.vue";
import AudioRecorder from "../AudioRecorder.vue";
import BaseButton from "../basecomponents/BaseButton.vue";
import {RESOURCE_LINK_PUBLISHED} from "../resource_links/visibility";
import {useCidReq} from "../../composables/cidReq";

const {t} = useI18n()
const queryParams = useCidReq()
const store = useStore()

const props = defineProps({
  parentResourceNodeId: {
    type: Object,
    required: true,
  }
})

const emit = defineEmits(['document-saved', 'document-not-saved'])

const recordName = ref('')
const recordError = ref('')
const audioRecorder = ref(null)
const showAudioRecorderButtons = ref(true)
const audio = ref()

const processAudio = (recordedAudio) => {
  audio.value = recordedAudio
  showAudioRecorderButtons.value = false
}

const recordAudio = () => {
  recordError.value = ''
  audio.value = null
  showAudioRecorderButtons.value = true
  audioRecorder.value.record()
}

const saveAudio = () => {
  if (recordName.value === '') {
    recordError.value = t('It is necessary a file name before save recorded audio')
    return
  }

  let fileName = recordName.value + '.wav';
  let uploadFile = new File([audio.value], fileName)
  let data = {
    title: fileName,
    filetype: 'file',
    uploadFile: uploadFile,
    parentResourceNodeId: props.parentResourceNodeId,
    resourceLinkList: JSON.stringify([{
      ...queryParams,
      visibility: RESOURCE_LINK_PUBLISHED,
    }]),
  }
  store
    .dispatch("documents/createWithFormData", data)
    .then(() => emit('document-saved'))
    .catch((error) => emit('document-not-saved', error))
}
</script>
