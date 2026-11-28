<template>
  <div v-if="!microphoneError">
    <div class="flex flex-row">
      <div
        v-if="recorderState.isRecording"
        class="flex rounded-md me-2 mb-2 py-2 px-3 border border-error"
      >
        <BaseIcon
          :class="{ 'motion-safe:animate-pulse': !recorderState.isPaused }"
          :icon="recorderState.isPaused ? 'pause' : 'microphone'"
          class="self-center me-2 text-error"
        />
        <p class="self-center font-semibold text-error">
          {{ recordedTime }}
        </p>
      </div>

      <BaseButton
        v-if="showButtons && pausable && recorderState.isRecording && !recorderState.isPaused"
        :disabled="disabled || isStarting || isStopping"
        :label="t('Pause recording')"
        class="me-2 mb-2"
        icon="pause"
        type="secondary"
        @click="pause"
      />
      <BaseButton
        v-if="showButtons && pausable && recorderState.isRecording && recorderState.isPaused"
        :disabled="disabled || isStarting || isStopping"
        :label="t('Resume recording')"
        class="me-2 mb-2"
        icon="microphone"
        type="primary"
        @click="resume"
      />
      <BaseButton
        v-if="showButtons && recorderState.isRecording"
        :disabled="disabled || isStarting || isStopping"
        :label="t('Stop recording')"
        class="me-2 mb-2"
        icon="stop"
        type="danger"
        @click="stop"
      />
      <BaseButton
        v-else-if="showButtons"
        :disabled="disabled || isStarting || isStopping"
        :label="t('Start recording')"
        class="me-2 mb-2"
        icon="microphone"
        type="primary"
        @click="record"
      />
    </div>

    <div v-if="showRecordedAudios">
      <div
        v-for="(audio, index) in recorderState.audioList"
        :key="index"
        class="py-2"
      >
        <audio
          class="max-w-full"
          controls
        >
          <source :src="window.URL.createObjectURL(audio)" />
        </audio>

        <BaseButton
          :label="$t('Attachment')"
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
      {{ microphoneError }}
    </p>
  </div>
</template>

<script setup>
import BaseButton from "./basecomponents/BaseButton.vue"
import { computed, onBeforeUnmount, onMounted, reactive, ref } from "vue"
import { RecordRTCPromisesHandler, StereoAudioRecorder } from "recordrtc"
import { useI18n } from "vue-i18n"
import BaseIcon from "./basecomponents/BaseIcon.vue"

const { t } = useI18n()

const props = defineProps({
  disabled: {
    type: Boolean,
    default: false,
  },
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
  },
  // Offers to pause and resume the recording, as many times as wanted, before stopping it
  pausable: {
    type: Boolean,
    default: false,
  },
})

const emit = defineEmits(["attach-audio", "recorded-audio"])

defineExpose({
  record,
  pause,
  resume,
  stop,
})

const recorderState = reactive({
  isRecording: false,
  isPaused: false,
  audioList: [],
  seconds: 0,
  minutes: 0,
  hours: 0,
})

const microphoneError = ref("")
const isStarting = ref(false)
const isStopping = ref(false)

const recordedTime = computed(() => {
  let hours = timeComponentToString(recorderState.hours)
  let minutes = timeComponentToString(recorderState.minutes)
  let seconds = timeComponentToString(recorderState.seconds)
  return `${hours} : ${minutes} : ${seconds}`
})

onMounted(() => {
  let isMediaDevicesSupported = navigator.mediaDevices && navigator.mediaDevices.getUserMedia

  if (!isMediaDevicesSupported) {
    console.warn(
      "Either your browser does not support microphone or your are serving your site from not secure " +
        "context, check https://developer.mozilla.org/en-US/docs/Web/API/MediaDevices/getUserMedia for more information",
    )
    microphoneError.value = t("We are sorry, your browser does not support using a microphone")
  }
})

let recorder = null
let recordingStream = null
let startCancelled = false
let disposed = false
let stopPromise = null
let stopError = null

function releaseStream() {
  recordingStream?.getTracks().forEach((track) => track.stop())
  recordingStream = null
}

async function record() {
  if (
    microphoneError.value ||
    props.disabled ||
    disposed ||
    isStarting.value ||
    isStopping.value ||
    recorderState.isRecording
  ) {
    return
  }

  isStarting.value = true
  startCancelled = false
  stopError = null

  try {
    recordingStream = await navigator.mediaDevices.getUserMedia({ video: false, audio: true })
    // Permission may be granted after the question has already expired or disappeared.
    if (startCancelled || disposed || props.disabled) {
      releaseStream()
      return
    }

    recorder = new RecordRTCPromisesHandler(recordingStream, {
      recorderType: StereoAudioRecorder,
      type: "audio",
      mimeType: "audio/wav",
      numberOfAudioChannels: 2,
    })
    await recorder.startRecording()
    if (startCancelled || disposed || props.disabled) {
      await recorder.stopRecording()
      recorder = null
      releaseStream()
      return
    }

    startTimer()
    recorderState.isRecording = true
  } catch (error) {
    recorder = null
    releaseStream()
    if (startCancelled || disposed) {
      return
    }

    console.warn(
      "Either the user denied permission or microphone is not available, " +
        "check https://developer.mozilla.org/en-US/docs/Web/API/MediaDevices/getUserMedia",
    )
    if (error.name === "NotAllowedError") {
      microphoneError.value = t(
        "Permission to use the microphone is not enabled, please enable it in your browser to record audio",
      )
    } else {
      microphoneError.value = t("We are sorry, your browser does not support using a microphone")
    }
  } finally {
    isStarting.value = false
  }
}

async function stop() {
  startCancelled = true
  if (stopPromise) {
    return stopPromise
  }
  if (stopError) {
    throw stopError
  }
  if (!recorder || !recorderState.isRecording) {
    return
  }

  isStopping.value = true
  stopTimer()
  // A manual stop and the question timeout must await the same WAV finalization.
  stopPromise = (async () => {
    try {
      await recorder.stopRecording()
      const audioBlob = await recorder.getBlob()
      if (!audioBlob?.size) {
        throw new Error("Could not finalize recorded audio")
      }

      if (!disposed) {
        if (false === props.multiple && recorderState.audioList.length > 0) {
          recorderState.audioList.shift()
        }
        recorderState.audioList.push(audioBlob)
        emit("recorded-audio", audioBlob)
      }
    } catch (error) {
      // Do not silently advance a timed question after losing its recording.
      stopError = error
      throw error
    } finally {
      recorderState.isRecording = false
      recorderState.isPaused = false
      recorder = null
      releaseStream()
      isStopping.value = false
      stopPromise = null
    }
  })()

  return stopPromise
}

onBeforeUnmount(() => {
  disposed = true
  stopTimer()
  void stop().catch((error) => console.warn("Could not stop audio recording", error))
})

async function pause() {
  if (props.disabled || isStopping.value || !recorder || !recorderState.isRecording || recorderState.isPaused) {
    return
  }

  await recorder.pauseRecording()
  if (isStopping.value || !recorderState.isRecording) {
    return
  }
  clearInterval(timer)
  timer = null
  recorderState.isPaused = true
}

async function resume() {
  if (props.disabled || isStopping.value || !recorder || !recorderState.isPaused) {
    return
  }

  await recorder.resumeRecording()
  if (isStopping.value || !recorderState.isRecording) {
    return
  }
  runTimer()
  recorderState.isPaused = false
}

function attachAudio(audio) {
  emit("attach-audio", audio)

  const index = recorderState.audioList.indexOf(audio)

  if (index >= 0) {
    recorderState.audioList.splice(index, 1)
  }
}

let timer = null

function startTimer() {
  recorderState.seconds = 0
  recorderState.minutes = 0
  recorderState.hours = 0

  runTimer()
}

// Counts on from the current time, so that resuming after a pause does not start again from zero
function runTimer() {
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
  return value.toString().padStart(2, "0")
}
</script>
