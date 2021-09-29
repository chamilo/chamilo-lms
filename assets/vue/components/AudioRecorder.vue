<template>
    <div class="py-4">
        <q-btn v-if="!isRecording" color="primary" icon="mic" :label="$t('Start recording')" @click="record()"/>

        <q-btn v-if="isRecording" :label="$t('Stop recording')" color="red" icon="stop" @click="stop()"/>

        <div v-for="(audio, index) in audioList" :key="index" class="py-2">
            <audio controls class="max-w-full">
                <source :src="URL.createObjectURL(audio)">
            </audio>
            {{ $t('Size') }} {{ audio.size }}
            <q-btn :label="$t('Attach')" class="my-1" color="green" icon="attachment" size="sm"
                   @click="attachAudio(audio)"/>
        </div>
    </div>
</template>

<script>
import {reactive, toRefs} from "vue";

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
                        stream.getAudioTracks()[0].stop();

                        const audioItem = new Blob(mediaChunks, {type: 'audio/ogg; codecs=opus'});

                        mediaChunks = [];

                        recorderState.audioList.push(audioItem);
                    };
                    mediaRecorder.start();

                    recorderState.isRecording = true;
                })
                .catch(console.log);
        }

        function stop() {
            if (!mediaRecorder) {
                return;
            }

            if (false === props.multiple && recorderState.audioList.length > 0) {
                recorderState.audioList.shift();
            }

            mediaRecorder.stop();
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
    }
}
</script>

<style scoped>

</style>
