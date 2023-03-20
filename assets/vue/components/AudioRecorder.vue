<template>
  <div class="py-4">
    <q-btn
      v-if="!isRecording"
      :label="$t('Start recording')"
      color="primary"
      icon="mic"
      @click="record()"
    />

    <q-btn
      v-if="isRecording"
      :label="$t('Stop recording')"
      color="red"
      icon="stop"
      @click="stop()"
    />

    <div
      v-for="(audio, index) in audioList"
      :key="index"
      class="py-2"
    >
      <audio
        class="max-w-full"
        controls
      >
        <source
          :src="URL.createObjectURL(audio)"
        >
      </audio>
      <q-btn
        :label="$t('Attach')"
        class="my-1"
        color="green"
        icon="attachment"
        size="sm"
        @click="attachAudio(audio)"
      />
    </div>
  </div>
</template>

<script>
import {reactive, toRefs} from "vue";
import {RecordRTCPromisesHandler, StereoAudioRecorder} from "recordrtc";

export default {
  name: "AudioRecorder",
  props: {
    multiple: {
      type: Boolean,
      required: false,
      default: true
    },
  },
  setup(props, {emit}) {
    const recorderState = reactive({
      isRecording: false,
      audioList: [],
    });

    let recorder = null;

    async function record() {
      if (!navigator.mediaDevices.getUserMedia) {
        return;
      }

      let stream = await navigator.mediaDevices.getUserMedia({video: false, audio: true});
      recorder = new RecordRTCPromisesHandler(stream, {
        recorderType: StereoAudioRecorder,
        type: 'audio',
        mimeType: 'audio/wav',
        numberOfAudioChannels: 2
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
      emit('attach-audio', audio);

      const index = recorderState.audioList.indexOf(audio);

      if (index >= 0) {
        recorderState.audioList.splice(index, 1);
      }
    }

    return {
      record,
      stop,
      ...toRefs(recorderState),
      attachAudio,
      URL
    };
  },
  emits: ['attachAudio'],
}
</script>

<style scoped>

</style>
