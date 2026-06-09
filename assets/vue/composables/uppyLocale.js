import { computed } from "vue"
import de_DE from "@uppy/locales/lib/de_DE"
import en_US from "@uppy/locales/lib/en_US"
import es_ES from "@uppy/locales/lib/es_ES"
import fr_FR from "@uppy/locales/lib/fr_FR"
import it_IT from "@uppy/locales/lib/it_IT"
import pl_PL from "@uppy/locales/lib/pl_PL"
import pt_PT from "@uppy/locales/lib/pt_PT"
import { useLocale } from "./locale"

const uppyLocales = {
  de: de_DE,
  en: en_US,
  es: es_ES,
  fr: fr_FR,
  it: it_IT,
  pl: pl_PL,
  pt: pt_PT,
}

function resolveUppyLocale(localeName) {
  const normalizedLocale = String(localeName || "en_US")
    .replace("-", "_")
    .toLowerCase()
  const localePrefix = normalizedLocale.split("_")[0]

  return uppyLocales[localePrefix] || en_US
}

export function useUppyLocale() {
  const { appLocale } = useLocale()
  const uppyLocale = computed(() => resolveUppyLocale(appLocale.value))

  return {
    uppyLocale,
  }
}
