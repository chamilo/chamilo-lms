<template>
  <BaseButton v-if="recorderState.isRecording" :label="t('Stop recording')" icon="stop" type="danger" @click="stop" />
  <BaseButton v-else :label="t('Start recording')" icon="microphone" type="primary" @click="record" />

  <div v-for="(audio, index) in recorderState.audioList" :key="index" class="py-2">
    <audio class="max-w-full" controls>
      <source :src="window.URL.createObjectURL(audio)" />
    </audio>

    <BaseButton
      :label="$t('Attach')"
      class="my-1"
      icon="attachment"
      size="small"
      type="success"
      @click="attachAudio(audio)"
    />
  </div>
</template>

<script setup>
import BaseButton from "./basecomponents/BaseButton.vue";
import { reactive } from "vue";
import { RecordRTCPromisesHandler, StereoAudioRecorder } from "recordrtc";
import { useI18n } from "vue-i18n";

const props = defineProps({
  multiple: {
    type: Boolean,
    required: false,
    default: true,
  },
});

const emit = defineEmits(["attach-audio"]);

const { t } = useI18n();

const recorderState = reactive({
  isRecording: false,
  audioList: [],
});

let recorder = null;

async function record() {
  if (!navigator.mediaDevices.getUserMedia) {
    return;
  }

  let stream = await navigator.mediaDevices.getUserMedia({ video: false, audio: true });
  recorder = new RecordRTCPromisesHandler(stream, {
    recorderType: StereoAudioRecorder,
    type: "audio",
    mimeType: "audio/wav",
    numberOfAudioChannels: 2,
  });
  recorder.startRecording();

  recorderState.isRecording = true;
}

async function stop() {
  if (!recorder) {
    return;
  }

  if (false === props.multiple && recorderState.audioList.length > 0) {
    recorderState.audioList.shift();
  }

  await recorder.stopRecording();

  const audioBlob = await recorder.getBlob();

  recorderState.audioList.push(audioBlob);

  recorderState.isRecording = false;
}

function attachAudio(audio) {
  emit("attach-audio", audio);

  const index = recorderState.audioList.indexOf(audio);

  if (index >= 0) {
    recorderState.audioList.splice(index, 1);
  }
}
</script>
