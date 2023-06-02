<template>
  <div v-if="isMicrophoneSupported">
    <div class="flex flex-row">
      <div v-if="recorderState.isRecording" class="flex rounded-md mr-2 mb-2 py-2 px-3 border border-error">
        <BaseIcon class="self-center mr-2 text-error motion-safe:animate-pulse" icon="microphone"/>
        <p class="self-center font-semibold text-error">
          {{ recordedTime }}
        </p>
      </div>

      <BaseButton
        v-if="showButtons && recorderState.isRecording"
        :label="t('Stop recording')"
        class="mr-2 mb-2"
        icon="stop"
        type="danger"
        @click="stop"
      />
      <BaseButton
        v-else-if="showButtons"
        :label="t('Start recording')"
        class="mr-2 mb-2"
        icon="microphone"
        type="primary"
        @click="record"
      />
    </div>

    <div v-if="showRecordedAudios">
      <div v-for="(audio, index) in recorderState.audioList" :key="index" class="py-2">
        <audio class="max-w-full" controls>
          <source :src="window.URL.createObjectURL(audio)"/>
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
    </div>
  </div>
  <div v-else>
      <p>
        {{ t('We\'re sorry, your browser does not support using a microphone') }}
      </p>
  </div>
</template>

<script setup>
import BaseButton from "./basecomponents/BaseButton.vue";
import { computed, reactive } from "vue";
import { RecordRTCPromisesHandler, StereoAudioRecorder } from "recordrtc";
import { useI18n } from "vue-i18n";
import BaseIcon from "./basecomponents/BaseIcon.vue";

const { t } = useI18n();

const props = defineProps({
  multiple: {
    type: Boolean,
    required: false,
    default: true,
  },
  showButtons: {
    type: Boolean,
    default: true,
  },
  showRecordedAudios: {
    type: Boolean,
    default: true,
  }
});

const emit = defineEmits(["attach-audio"]);

defineExpose({
  record,
  stop,
});

const recorderState = reactive({
  isRecording: false,
  audioList: [],
  seconds: 0,
  minutes: 0,
  hours: 0,
});

const recordedTime = computed(() => {
  let hours = timeComponentToString(recorderState.hours)
  let minutes = timeComponentToString(recorderState.minutes)
  let seconds = timeComponentToString(recorderState.seconds)
  return `${hours} : ${minutes} : ${seconds}`
})

const isMicrophoneSupported = computed(() => {
  let isMediaDevicesSupported = navigator.mediaDevices && navigator.mediaDevices.getUserMedia
  if (!isMediaDevicesSupported) {
    console.warn('Either your browser does not support microphone or your are serving your site from not secure ' +
      'context, check https://developer.mozilla.org/en-US/docs/Web/API/MediaDevices/getUserMedia for more information')
  }
  return isMediaDevicesSupported
})

let recorder = null;

async function record() {
  if (!isMicrophoneSupported.value) {
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
  startTimer()

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
  stopTimer()
}

function attachAudio(audio) {
  emit("attach-audio", audio);

  const index = recorderState.audioList.indexOf(audio);

  if (index >= 0) {
    recorderState.audioList.splice(index, 1);
  }
}

let timer = null;
function startTimer() {
  recorderState.seconds = 0
  recorderState.minutes = 0
  recorderState.hours = 0

  timer = setInterval(() => {
    recorderState.seconds = recorderState.seconds + 1
    if (recorderState.seconds > 59) {
      recorderState.minutes = recorderState.minutes + 1
      recorderState.seconds = 0
    }
    if (recorderState.minutes > 59) {
      recorderState.hours = recorderState.hours + 1
      recorderState.minutes = 0
    }
  }, 1000)
}

function stopTimer() {
  recorderState.seconds = 0
  recorderState.minutes = 0
  recorderState.hours = 0

  clearInterval(timer)
  timer = null
}

function timeComponentToString(value) {
  return value.toString().padStart(2, '0')
}
</script>
