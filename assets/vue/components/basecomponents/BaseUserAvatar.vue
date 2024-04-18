<template>
  <Avatar
    :image="`${imageUrl}?w=${imageSize}&h=${imageSize}&fit=crop`"
    :shape="shape"
    :size="size"
    class="rounded-full"
    :class="avatarClass"
  />
</template>

<script setup>
import Avatar from "primevue/avatar";
import { computed } from "vue";

const props = defineProps({
  imageUrl: {
    type: String,
    require: true,
    default: "",
  },
  size: {
    type: String,
    require: false,
    default: "normal",
    validator: (value) => ["normal", "large", "xlarge"].includes(value),
  },
  shape: {
    type: String,
    require: false,
    default: "circle",
    validator: (value) => ["circle", "square"].includes(value),
  },
});

const imageSize = computed(() => {
  // these numbers are aproximate, they were calculated with a size
  // that allow to see the image clear
  if (props.size === "xlarge") {
    return 250;
  } else if (props.size === "large") {
    return 125;
  }
  return 75;
});

const avatarClass = computed(() => {
  let clazz = ""
  if (props.size === "xlarge") {
    clazz += "h-28 w-28 "
  } else if (props.size === "large") {
    clazz += "h-16 w-16 "
  } else if (props.size === "normal") {
    clazz += "h-10 w-10 "
  }
  return clazz
})
</script>
