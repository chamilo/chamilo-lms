import { createI18n } from 'vue-i18n';

function loadLocaleMessages() {
    const locales = require.context(
        "./../locales",
        true,
        /[A-Za-z0-9-_,\s]+\.json$/i
    );
    const messages = {};

    locales.keys().forEach(key => {
        const matched = key.match(/([A-Za-z0-9-_]+)\./i);
        if (matched && matched.length > 1) {
            const locale = matched[1];
            messages[locale] = locales(key);
        }
    });

    return messages;
}

let locale = document.querySelector('html').lang;
if (!locale) {
    locale = 'en'
}

export default createI18n({
    locale: locale,
    fallbackLocale: process.env.VUE_APP_I18N_FALLBACK_LOCALE || 'en',
    messages: loadLocaleMessages()
});