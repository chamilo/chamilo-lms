<template>
    <div class="py-2">
        <q-btn v-if="!isRecording" color="primary" icon="mic" :label="$t('Start recording')" @click="record()"/>

        <q-btn v-if="isRecording" color="red" icon="stop" :label="$t('Stop recording')" @click="stop()"/>

        <div v-for="(audio, index) in audioList" :key="index" class="py-2">
            <audio controls style="max-width: 100%;">
                <source :src="audio">
            </audio>
        </div>
    </div>
</template>

<script>
import {ref} from "vue";

export default {
    name: "AudioRecorder",

    setup() {
        const isRecording = ref(false)
        const audioList = ref([]);

        let mediaRecorder = null;
        let mediaChunks = [];

        function record() {
            if (!navigator.mediaDevices.getUserMedia) {
                return;
            }

            navigator.mediaDevices.getUserMedia({audio: true})
                .then((stream) => {
                    mediaRecorder = new MediaRecorder(stream);
                    mediaRecorder.ondataavailable = e => {
                        mediaChunks.push(e.data)
                    };
                    mediaRecorder.onstop = e => {
                        const blob = new Blob(mediaChunks, {type: 'audio/ogg; codecs=opus'});
                        const audioUrl = URL.createObjectURL(blob);

                        mediaChunks = [];

                        audioList.value.push(audioUrl);
                    };
                    mediaRecorder.start();

                    isRecording.value = true;
                })
                .catch(console.log);
        }

        function stop() {
            if (!mediaRecorder) {
                return;
            }

            mediaRecorder.stop();
            mediaRecorder.stream.getAudioTracks()[0].stop();
            isRecording.value = false;
        }

        return {
            record,
            stop,
            audioList,
            isRecording
        };
    }
}
</script>

<style scoped>

</style>
