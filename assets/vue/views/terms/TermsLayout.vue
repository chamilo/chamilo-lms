<template>
  <div class="terms-layout">
    <header>
      <h1>{{ t('Terms and conditions') }}</h1>
    </header>

    <main>
      <router-view />
    </main>

    <footer>
    </footer>
  </div>
</template>

<script setup>
import { useI18n } from "vue-i18n"
const { t } = useI18n()

</script>
