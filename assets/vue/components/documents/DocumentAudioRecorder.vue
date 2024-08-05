<template>
  <div class="flex flex-col flex-wrap justify-center content-center text-center">
    <BaseInputText
      v-if="audio"
      v-model="recordName"
      :error-text="recordError"
      :is-invalid="recordError !== ''"
      :label="t('Enter filename here')"
      class="max-w-full self-center mb-4 w-60"
    />

    <audio
      v-if="audio"
      class="max-w-full self-center mb-4"
      controls
    >
      <source :src="window.URL.createObjectURL(audio)" />
    </audio>

    <AudioRecorder
      ref="audioRecorder"
      :multiple="false"
      :show-buttons="showAudioRecorderButtons"
      :show-recorded-audios="false"
      class="self-center mr-2 mb-4"
      @recorded-audio="processAudio($event)"
    />

    <div
      v-if="audio"
      class="flex"
    >
      <BaseButton
        :label="t('Start recording')"
        class="mr-2"
        icon="restart"
        type="black"
        @click="recordAudio"
      />
      <BaseButton
        :label="t('Save recorded audio')"
        class="mr-2"
        icon="send"
        type="success"
        @click="saveAudio"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from "vue"
import { useI18n } from "vue-i18n"
import BaseInputText from "../basecomponents/BaseInputText.vue"
import AudioRecorder from "../AudioRecorder.vue"
import BaseButton from "../basecomponents/BaseButton.vue"
import { RESOURCE_LINK_PUBLISHED } from "../../constants/entity/resourcelink"
import { useCidReq } from "../../composables/cidReq"
import documentsService from "../../services/documents"

const { t } = useI18n()
const queryParams = useCidReq()

const props = defineProps({
  parentResourceNodeId: {
    type: String,
    required: true,
  },
})

const emit = defineEmits(["document-saved", "document-not-saved"])

const recordName = ref("")
const recordError = ref("")
const audioRecorder = ref(null)
const showAudioRecorderButtons = ref(true)
const audio = ref()

const processAudio = (recordedAudio) => {
  audio.value = recordedAudio
  showAudioRecorderButtons.value = false
}

const recordAudio = () => {
  recordError.value = ""
  audio.value = null
  showAudioRecorderButtons.value = true
  audioRecorder.value.record()
}

const saveAudio = async () => {
  if (recordName.value === "") {
    recordError.value = t("It is necessary a file name before save recorded audio")
    return
  }

  let fileName = recordName.value + ".wav"
  let uploadFile = new File([audio.value], fileName)
  let data = {
    title: fileName,
    filetype: "file",
    uploadFile: uploadFile,
    parentResourceNodeId: props.parentResourceNodeId,
    resourceLinkList: JSON.stringify([
      {
        ...queryParams,
        visibility: RESOURCE_LINK_PUBLISHED,
      },
    ]),
  }

  try {
    await documentsService.createWithFormData(data)
    emit("document-saved")
  } catch (error) {
    emit("document-not-saved", error)
  }
}
</script>
