<template>
  <q-card
    bordered
    class="mb-4"
    flat
  >
    <q-item>
      <q-item-section side>
        <q-avatar>
          <q-img :alt="message.sender.username" :src="message.sender.illustrationUrl" />
        </q-avatar>
      </q-item-section>

      <q-item-section>
        <q-item-label
          v-if="!message.firstReceiver || (message.firstReceiver && message.sender['@id'] === message.firstReceiver.receiver['@id'])"
        >
          <router-link :to="{ name: 'SocialNetworkWall', query: { id: message.sender['@id']} }">
            {{ message.sender.username }}
          </router-link>
        </q-item-label>

        <q-item-label v-else>
          <router-link :to="{ name: 'SocialNetworkWall', query: { id: message.sender['@id']} }">
            {{ message.sender.username }}
          </router-link>
          &raquo;
          <router-link :to="{ name: 'SocialNetworkWall', query: { id: message.firstReceiver.receiver['@id']} }">
            {{ message.firstReceiver.receiver.username }}
          </router-link>
        </q-item-label>

        <q-item-label caption>
          {{ $filters.abbreviatedDatetime(message.sendDate) }}
        </q-item-label>
      </q-item-section>
    </q-item>

    <q-img
      v-if="containsImage"
      :alt="attachment.comment"
      :src="attachment.contentUrl"
    />

    <q-video
      v-if="containsVideo"
      :alt="attachment.comment"
      :src="attachment.contentUrl"
    />
    <q-card-section v-html="message.content" />
  </q-card>
</template>

<script>
export default {
  name: "SocialNetworkPost",
  props: {
    message: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const attachment = props.message.attachments.length ? props.message.attachments[0] : null;

    const containsImage = attachment && attachment.resourceNode.resourceFile.mimeType.includes('image/');
    const containsVideo = attachment && attachment.resourceNode.resourceFile.mimeType.includes('video/');

    return {
      attachment,
      containsImage,
      containsVideo
    }
  }
}
</script>
