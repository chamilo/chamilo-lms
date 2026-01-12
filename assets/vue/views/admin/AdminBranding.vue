<template>
  <div>
    <h4 class="mb-4">{{ t('Branding (logos)') }}</h4>
    <p class="mb-8 text-sm opacity-80">
      {{ t('Upload optional header and email logos. SVG is preferred; PNG is used as fallback.') }}
    </p>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
      <div class="p-6 rounded-2xl shadow-sm border border-gray-200 bg-white space-y-6">
        <div>
          <h5 class="font-semibold text-base">{{ t('Header logo') }}</h5>
          <p class="text-xs opacity-70 mt-1">
            {{ t('Preferred: SVG. PNG max size: {0}px.', ['190×60']) }}
          </p>
        </div>

        <section class="rounded-xl border bg-gray-20 p-4">
          <div class="text-[11px] font-medium uppercase tracking-wide opacity-70 mb-3">
            {{ t('Effective preview') }}
          </div>
          <div class="flex items-center gap-4">
            <img
              :key="headerImgKey"
              :src="headerPreviewUrl"
              alt="Header logo"
              class="h-10 object-contain"
              @error="onImgError"
            />
            <span v-if="!hasHeaderCustom" class="text-xs opacity-70">
              {{ t('Using default theme logo') }}
            </span>
          </div>
        </section>

        <div class="border-t border-gray-200"></div>

        <section class="space-y-3">
          <div class="text-[11px] uppercase tracking-wide opacity-70 mb-1">
            {{ t('Files in current theme') }}
          </div>

          <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] uppercase">SVG</span>
            <img
              :key="'h-s-'+cacheBust"
              :src="headerSvgStrict"
              class="h-6 object-contain"
              alt="Header SVG"
              @load="markExists('headerSvg', true)"
              @error="markExists('headerSvg', false)"
            />
            <span v-if="headerSvgExists === false" class="text-xs opacity-60">{{ t('No SVG uploaded') }}</span>
            <button
              v-if="headerSvgExists === true"
              class="btn btn--danger ml-auto"
              @click="removeFile('header_svg')"
              :disabled="isSaving"
            >
              {{ t('Remove SVG') }}
            </button>
          </div>

          <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] uppercase">PNG</span>
            <img
              :key="'h-p-'+cacheBust"
              :src="headerPngStrict"
              class="h-6 object-contain"
              alt="Header PNG"
              @load="markExists('headerPng', true)"
              @error="markExists('headerPng', false)"
            />
            <span v-if="headerPngExists === false" class="text-xs opacity-60">{{ t('No PNG uploaded') }}</span>
            <button
              v-if="headerPngExists === true"
              class="btn btn--danger ml-auto"
              @click="removeFile('header_png')"
              :disabled="isSaving"
            >
              {{ t('Remove PNG') }}
            </button>
          </div>
        </section>

        <div class="border-t border-gray-200"></div>

        <section class="grid grid-cols-1 gap-3">
          <div>
            <label class="text-xs block mb-1">{{ t('Upload SVG logo to platform header') }}</label>
            <input type="file" accept=".svg,image/svg+xml" @change="onPick($event, 'headerSvg')" />
          </div>
          <div>
            <label class="text-xs block mb-1">{{ t('Upload PNG logo to platform header ({0})', ['≤190×60']) }}</label>
            <input type="file" accept="image/png" @change="onPick($event, 'headerPng', { maxW:190, maxH:60 })" />
          </div>
        </section>

        <div class="flex items-center gap-2">
          <button class="btn btn--primary" @click="uploadHeader" :disabled="isSaving">
            {{ isSaving ? t('Saving...') : t('Save header logo') }}
          </button>
        </div>
      </div>

      <div class="p-6 rounded-2xl shadow-sm border border-gray-200 bg-white space-y-6">
        <div>
          <h5 class="font-semibold text-base">{{ t('Email logo') }}</h5>
          <p class="text-xs opacity-70 mt-1">
            {{ t('Preferred: SVG. PNG recommended width: {0}px.', ['540']) }}
          </p>
        </div>

        <section class="rounded-xl border bg-gray-20 p-4">
          <div class="text-[11px] font-medium uppercase tracking-wide opacity-70 mb-3">
            {{ t('Effective preview') }}
          </div>
          <div class="flex items-center gap-4">
            <img
              :key="emailImgKey"
              :src="emailPreviewUrl"
              alt="Email logo"
              class="h-10 object-contain"
              @error="onImgError"
            />
            <span v-if="!hasEmailCustom" class="text-xs opacity-70">
              {{ t('Using default theme logo') }}
            </span>
          </div>
        </section>

        <div class="border-t border-gray-200"></div>

        <section class="space-y-3">
          <div class="text-[11px] uppercase tracking-wide opacity-70 mb-1">
            {{ t('Files in current theme') }}
          </div>

          <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] uppercase">SVG</span>
            <img
              :key="'e-s-'+cacheBust"
              :src="emailSvgStrict"
              class="h-6 object-contain"
              alt="Email SVG"
              @load="markExists('emailSvg', true)"
              @error="markExists('emailSvg', false)"
            />
            <span v-if="emailSvgExists === false" class="text-xs opacity-60">{{ t('No SVG uploaded') }}</span>
            <button
              v-if="emailSvgExists === true"
              class="btn btn--danger ml-auto"
              @click="removeFile('email_svg')"
              :disabled="isSaving"
            >
              {{ t('Remove SVG') }}
            </button>
          </div>

          <div class="flex items-center gap-3">
            <span class="inline-flex items-center rounded-full border px-2 py-0.5 text-[11px] uppercase">PNG</span>
            <img
              :key="'e-p-'+cacheBust"
              :src="emailPngStrict"
              class="h-6 object-contain"
              alt="Email PNG"
              @load="markExists('emailPng', true)"
              @error="markExists('emailPng', false)"
            />
            <span v-if="emailPngExists === false" class="text-xs opacity-60">{{ t('No PNG uploaded') }}</span>
            <button
              v-if="emailPngExists === true"
              class="btn btn--danger ml-auto"
              @click="removeFile('email_png')"
              :disabled="isSaving"
            >
              {{ t('Remove PNG') }}
            </button>
          </div>
        </section>

        <div class="border-t border-gray-200"></div>

        <section class="grid grid-cols-1 gap-3">
          <div>
            <label class="text-xs block mb-1">{{ t('Upload SVG logo for e-mails') }}</label>
            <input type="file" accept=".svg,image/svg+xml" @change="onPick($event, 'emailSvg')" />
          </div>
          <div>
            <label class="text-xs block mb-1">{{ t('Upload PNG logo for e-mails (~{0}px width)', ['540']) }}</label>
            <input type="file" accept="image/png" @change="onPick($event, 'emailPng', { recommendW:540 })" />
          </div>
        </section>

        <div class="flex items-center gap-2">
          <button class="btn btn--primary" @click="uploadEmail" :disabled="isSaving">
            {{ isSaving ? t('Saving...') : t('Save email logo') }}
          </button>
        </div>
      </div>

    </div>
  </div>
</template>
<script setup>
import { ref, computed } from 'vue'
import { storeToRefs } from 'pinia'
import { useI18n } from 'vue-i18n'
import themeLogoService from '../../services/themeLogoService'
import { usePlatformConfig } from '../../store/platformConfig'

const { t } = useI18n()
const platformConfigStore = usePlatformConfig()
const { visualTheme } = storeToRefs(platformConfigStore)

const DEFAULT_THEME = 'chamilo'
const slug = computed(() => visualTheme.value || DEFAULT_THEME)

const cacheBust = ref(Date.now())

const headerImgKey = ref(0)
const emailImgKey  = ref(0)

const headerSvgExists = ref(null)
const headerPngExists = ref(null)
const emailSvgExists  = ref(null)
const emailPngExists  = ref(null)

const u = (name, path, { strict = false } = {}) => {
  const base = `/themes/${encodeURIComponent(name)}/${path}`
  const qs = []
  if (strict) qs.push('strict=1')
  qs.push(`t=${cacheBust.value}`)
  return `${base}?${qs.join('&')}`
}

const headerSvgStrict = computed(() => u(slug.value, 'images/header-logo.svg', { strict: true }))
const headerPngStrict = computed(() => u(slug.value, 'images/header-logo.png', { strict: true }))
const emailSvgStrict  = computed(() => u(slug.value, 'images/email-logo.svg', { strict: true }))
const emailPngStrict  = computed(() => u(slug.value, 'images/email-logo.png', { strict: true }))

const headerSvgDefault = computed(() => u(DEFAULT_THEME, 'images/header-logo.svg'))
const headerPngDefault = computed(() => u(DEFAULT_THEME, 'images/header-logo.png'))
const emailSvgDefault  = computed(() => u(DEFAULT_THEME, 'images/email-logo.svg'))
const emailPngDefault  = computed(() => u(DEFAULT_THEME, 'images/email-logo.png'))

const headerPreviewUrl = computed(() => headerSvgStrict.value)
const emailPreviewUrl  = computed(() => emailSvgStrict.value)

const hasHeaderCustom = computed(() => headerSvgExists === true || headerPngExists === true)
const hasEmailCustom  = computed(() => emailSvgExists === true  || emailPngExists === true)

function onImgError(e) {
  const src = e.target.src || ''

  // HEADER
  if (src.includes('header-logo.svg') && src.includes('strict=1')) {
    e.target.style.display = ''
    e.target.src = headerPngStrict.value
    return
  }
  if (src.includes('header-logo.png') && src.includes('strict=1')) {
    e.target.style.display = ''
    e.target.src = headerSvgDefault.value
    return
  }
  if (src.includes('header-logo.svg') && !src.includes('strict=1')) {
    e.target.style.display = ''
    e.target.src = headerPngDefault.value
    return
  }

  // EMAIL
  if (src.includes('email-logo.svg') && src.includes('strict=1')) {
    e.target.style.display = ''
    e.target.src = emailPngStrict.value
    return
  }
  if (src.includes('email-logo.png') && src.includes('strict=1')) {
    e.target.style.display = ''
    e.target.src = emailSvgDefault.value
    return
  }
  if (src.includes('email-logo.svg') && !src.includes('strict=1')) {
    e.target.style.display = ''
    e.target.src = emailPngDefault.value
    return
  }

  e.target.style.display = 'none'
}

function markExists(which, value) {
  switch (which) {
    case 'headerSvg': headerSvgExists.value = value; break
    case 'headerPng': headerPngExists.value = value; break
    case 'emailSvg':  emailSvgExists.value  = value; break
    case 'emailPng':  emailPngExists.value  = value; break
  }
}

const files = ref({ headerSvg: null, headerPng: null, emailSvg: null, emailPng: null })
const isSaving = ref(false)

function onPick(e, key, opts = {}) {
  const f = e.target.files?.[0]
  if (!f) return

  if (key.endsWith('Png') && f.type !== 'image/png') {
    alert(t('PNG format required'))
    e.target.value = ''
    return
  }
  if (key.endsWith('Svg') && !(f.type === 'image/svg+xml' || f.name.toLowerCase().endsWith('.svg'))) {
    alert(t('SVG format required'))
    e.target.value = ''
    return
  }

  if (key === 'headerPng' || key === 'emailPng') {
    const img = new Image()
    img.onload = () => {
      if (key === 'headerPng') {
        if (img.width > 190 || img.height > 60) {
          alert(t('Header PNG must be \u2264 {0}px.', ['190x60']))
          e.target.value = ''
          return
        }
      }
      if (key === 'emailPng' && opts.recommendW && img.width !== opts.recommendW) {
        console.warn(`Email PNG width is ${img.width}, recommended ${opts.recommendW}px`)
      }
      files.value[key] = f
    }
    img.src = URL.createObjectURL(f)
    return
  }

  files.value[key] = f
}

async function uploadHeader() {
  if (!files.value.headerSvg && !files.value.headerPng) {
    alert(t('Select at least one file'))
    return
  }
  try {
    isSaving.value = true
    await themeLogoService.upload(slug.value, {
      headerSvg: files.value.headerSvg,
      headerPng: files.value.headerPng,
    })
    clearLocal('header')
    bust()
  } finally {
    isSaving.value = false
  }
}

async function uploadEmail() {
  if (!files.value.emailSvg && !files.value.emailPng) {
    alert(t('Select at least one file'))
    return
  }
  try {
    isSaving.value = true
    await themeLogoService.upload(slug.value, {
      emailSvg: files.value.emailSvg,
      emailPng: files.value.emailPng,
    })
    clearLocal('email')
    bust()
  } finally {
    isSaving.value = false
  }
}

async function removeFile(type) {
  try {
    isSaving.value = true
    await themeLogoService.remove(slug.value, type)
    bust()
  } finally {
    isSaving.value = false
  }
}

function clearLocal(which) {
  if (which === 'header') { files.value.headerSvg = null; files.value.headerPng = null }
  if (which === 'email')  { files.value.emailSvg  = null; files.value.emailPng  = null }
}

function bust() {
  cacheBust.value = Date.now()
  headerImgKey.value++
  emailImgKey.value++
  headerSvgExists.value = headerPngExists.value = null
  emailSvgExists.value  = emailPngExists.value  = null
}
</script>
