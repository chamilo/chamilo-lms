<template>
  <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    <figure>
      <figcaption class="font-semibold mb-2">{{ t("Login page (visitors who are not signed in)") }}</figcaption>
      <svg
        :aria-label="t('Login page (visitors who are not signed in)')"
        class="schema"
        role="img"
        viewBox="0 0 420 280"
      >
        <rect class="z-frame" height="278" rx="6" width="418" x="1" y="1" />

        <!-- Top bar: category pages shown as menu entries only -->
        <rect class="z-panel" height="30" width="418" x="1" y="1" />
        <rect class="z-ph" height="14" rx="3" width="56" x="12" y="9" />
        <rect class="z-zone" height="18" rx="9" width="52" x="232" y="7" />
        <text class="z-label-sm" text-anchor="middle" x="258" y="19">index</text>
        <rect class="z-zone" height="18" rx="9" width="44" x="290" y="7" />
        <text class="z-label-sm" text-anchor="middle" x="312" y="19">faq</text>
        <rect class="z-zone" height="18" rx="9" width="52" x="342" y="7" />
        <text class="z-label-sm" text-anchor="middle" x="368" y="19">demo</text>

        <!-- Main area: index pages -->
        <rect class="z-zone" height="150" rx="4" width="250" x="12" y="44" />
        <text class="z-label" text-anchor="middle" x="137" y="124">index</text>

        <!-- Login form -->
        <rect class="z-panel" height="150" rx="4" width="140" x="272" y="44" />
        <rect class="z-ph" height="14" rx="3" width="116" x="284" y="62" />
        <rect class="z-ph" height="14" rx="3" width="116" x="284" y="86" />
        <rect class="z-ph-dark" height="16" rx="3" width="60" x="284" y="114" />

        <!-- Footer -->
        <rect class="z-zone" height="62" rx="4" width="400" x="12" y="208" />
        <text class="z-label" text-anchor="middle" x="212" y="244">footer_public</text>
      </svg>
    </figure>

    <figure>
      <figcaption class="font-semibold mb-2">{{ t("Interface of signed-in users") }}</figcaption>
      <svg
        :aria-label="t('Interface of signed-in users')"
        class="schema"
        role="img"
        viewBox="0 0 420 280"
      >
        <rect class="z-frame" height="278" rx="6" width="418" x="1" y="1" />

        <!-- Top bar -->
        <rect class="z-panel" height="30" width="418" x="1" y="1" />
        <rect class="z-ph" height="14" rx="3" width="56" x="12" y="9" />
        <circle class="z-ph" cx="396" cy="16" r="8" />

        <!-- Side menu -->
        <rect class="z-panel" height="248" width="130" x="1" y="31" />
        <text class="z-label-sm z-bold" x="12" y="48">{{ t("Menu") }}</text>
        <rect class="z-zone" height="20" rx="3" width="106" x="12" y="56" />
        <text class="z-label-sm z-bold" x="20" y="70">{{ t("Home") }}</text>
        <rect class="z-ph" height="10" rx="3" width="80" x="20" y="86" />
        <rect class="z-ph" height="10" rx="3" width="64" x="20" y="104" />
        <rect class="z-zone" height="38" rx="3" width="106" x="12" y="126" />
        <text class="z-label-sm z-bold" text-anchor="middle" x="65" y="149">menu_links</text>
        <rect class="z-zone" height="52" rx="3" width="106" x="12" y="218" />
        <text class="z-label-sm z-bold" text-anchor="middle" x="65" y="248">footer_private</text>

        <!-- Home area -->
        <rect class="z-zone" height="100" rx="4" width="268" x="144" y="44" />
        <text class="z-label" text-anchor="middle" x="278" y="98">home</text>
        <rect class="z-ph-light" height="10" rx="3" width="200" x="144" y="162" />
        <rect class="z-ph-light" height="60" rx="3" width="268" x="144" y="182" />
      </svg>
    </figure>
  </div>
</template>

<script setup>
import { useI18n } from "vue-i18n"

const { t } = useI18n()
</script>

<style scoped>
.schema {
  width: 100%;
  height: auto;
  max-width: 560px;
}
.z-frame {
  fill: #ffffff;
  stroke: #9ca3af;
}
.z-panel {
  fill: #f3f4f6;
  stroke: #d1d5db;
}
.z-zone {
  fill: #dbeafe;
  stroke: #3b82f6;
}
.z-ph {
  fill: #d1d5db;
}
.z-ph-dark {
  fill: #9ca3af;
}
.z-ph-light {
  fill: #e5e7eb;
}
.z-label,
.z-label-sm {
  fill: #1f2937;
  font-family: sans-serif;
}
.z-label {
  font-size: 13px;
  font-weight: 600;
}
.z-label-sm {
  font-size: 10px;
}
.z-bold {
  font-weight: 600;
}
</style>
