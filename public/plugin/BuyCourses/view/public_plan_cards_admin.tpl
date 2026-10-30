{% autoescape 'html' %}
<style>
    .bc-lang-panel[hidden] { display: none !important; }
    .bc-lang-tab[aria-selected="true"] { background: #256b9a; color: #fff; border-color: #256b9a; }
    .bc-lang-tab[aria-selected="false"] { background: #fff; color: #475569; border-color: #dae2e8; }
    .bc-lang-tab:focus-visible { outline: 2px solid #256b9a; outline-offset: 2px; }
</style>
<div class="mx-auto w-full max-w-screen-xl space-y-6 px-4 py-8 sm:px-6">
    <header class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
        <h1 class="text-2xl font-semibold text-gray-90">{{ plan_labels.title }}</h1>
        <p class="mt-2 text-sm text-gray-50">{{ plan_labels.intro }}</p>
        <div class="mt-4 flex flex-wrap gap-3">
            <a class="rounded-xl border border-gray-25 px-4 py-2 text-sm font-semibold text-primary" href="{{ plugin_url }}">{{ plan_labels.back }}</a>
            <a class="rounded-xl border border-gray-25 px-4 py-2 text-sm font-semibold text-primary" href="{{ catalog_url }}">{{ plan_labels.preview }}</a>
        </div>
    </header>

    {% if saved %}
        <div class="rounded-xl border border-success/30 bg-support-3 px-4 py-3 text-sm text-success">{{ plan_labels.saved }}</div>
    {% endif %}
    {% for error in errors %}
        <div class="rounded-xl border border-danger/30 bg-support-6 px-4 py-3 text-sm text-danger">{{ error }}</div>
    {% endfor %}

    <form method="post" class="space-y-6" id="buycourses-public-plans-form">
        <input type="hidden" name="csrf_token" value="{{ csrf_token }}">
        <section class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
            <div class="mb-5 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-90">{{ plan_labels.free }}</h2>
                    <p class="mt-1 text-sm text-gray-50">{{ plan_labels.freeHelp }}</p>
                </div>
                <label class="flex items-center gap-2 text-sm font-semibold text-gray-90">
                    <input type="checkbox" name="show_free" value="1" {% if config.show_free %}checked{% endif %}>
                    {{ plan_labels.showFree }}
                </label>
            </div>
            <div class="bc-localized-editor rounded-2xl border border-gray-25 bg-white p-4" data-bc-localized-editor data-active-language="{{ plan_current_language }}">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap gap-2" role="tablist" aria-label="{{ plan_labels.language }}" data-bc-lang-tabs>
                        {% for lang in plan_languages %}
                            <button type="button" class="bc-lang-tab rounded-lg border px-4 py-2 text-sm font-semibold" data-bc-lang-tab="{{ lang }}" role="tab" aria-selected="{{ lang == plan_current_language ? 'true' : 'false' }}">{{ plan_language_labels[lang] }}</button>
                        {% endfor %}
                    </div>
                    <span class="text-xs text-gray-50">{{ plan_labels.allLanguagesSaved }}</span>
                </div>
                <div data-bc-lang-panels>
                    {% for lang in plan_languages %}
                        <fieldset class="bc-lang-panel grid gap-4 md:grid-cols-2" data-bc-lang-panel="{{ lang }}" role="tabpanel" aria-label="{{ plan_language_labels[lang] }}" {% if lang != plan_current_language %}hidden{% endif %}>
                            <label class="block text-sm font-semibold text-gray-90 md:col-span-2">{{ 'Title'|get_lang }}
                                <input class="mt-1 w-full rounded-lg border border-gray-25 p-2" type="text" maxlength="120" name="free[title][{{ lang }}]" value="{{ config.free.title[lang]|default('') }}">
                            </label>
                            <label class="block text-sm font-semibold text-gray-90">{{ plan_labels.description }}
                                <textarea class="mt-1 w-full rounded-lg border border-gray-25 p-2" rows="4" name="free[description][{{ lang }}]">{{ config.free.description[lang]|default('') }}</textarea>
                            </label>
                            <label class="block text-sm font-semibold text-gray-90">{{ plan_labels.features }}
                                <textarea class="mt-1 w-full rounded-lg border border-gray-25 p-2" rows="4" name="free[features][{{ lang }}]">{{ config.free.features[lang]|default('') }}</textarea>
                            </label>
                        </fieldset>
                    {% endfor %}
                </div>
            </div>
            <p class="mt-3 text-xs text-gray-50">{{ plan_labels.featuresHelp }}</p>
        </section>

        <section class="space-y-4">
            <div class="rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-90">{{ plan_labels.promo }}</h2>
                        <p class="mt-1 text-sm text-gray-50">{{ plan_labels.promoHelp }}</p>
                    </div>
                    <button type="button" id="buycourses-add-plan" class="rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white">{{ plan_labels.add }}</button>
                </div>
            </div>
            <div id="buycourses-plan-rows" class="space-y-4" data-card-label="{{ plan_labels.card }}" data-next-index="{{ config.cards|length }}">
                {% for card in config.cards %}
                    {% set cardIndex = loop.index0 %}
                    <fieldset class="buycourses-plan-row space-y-4 rounded-3xl border border-gray-25 bg-white p-6 shadow-sm">
                        <div class="mb-2 flex items-center gap-2 text-base font-semibold text-gray-90">
                            <em class="mdi mdi-card-text-outline text-primary" aria-hidden="true"></em>
                            <span data-bc-card-title>{{ plan_labels.card }} {{ loop.index }}{% if card.title[plan_current_language]|default('') %} · {{ card.title[plan_current_language] }}{% elseif card.title.en_US|default('') %} · {{ card.title.en_US }}{% endif %}</span>
                        </div>
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <label class="flex items-center gap-2 text-sm font-semibold text-gray-90">
                                <input type="checkbox" value="1" name="cards[{{ loop.index0 }}][enabled]" {% if card.enabled %}checked{% endif %}>
                                {{ plan_labels.enabled }}
                            </label>
                            <label class="text-sm font-semibold text-gray-90">{{ plan_labels.position }}
                                <input class="ms-2 w-24 rounded-lg border border-gray-25 p-2" type="number" min="0" max="999" name="cards[{{ loop.index0 }}][position]" value="{{ card.position|default(10) }}">
                            </label>
                            <label class="flex items-center gap-2 text-sm text-danger">
                                <input type="checkbox" value="1" name="cards[{{ loop.index0 }}][remove]">
                                {{ plan_labels.remove }}
                            </label>
                        </div>
                        <div class="grid gap-4 lg:grid-cols-2">
                            <label class="text-sm font-semibold text-gray-90">{{ plan_labels.url }}
                                <input class="mt-1 w-full rounded-lg border border-gray-25 p-2" type="url" placeholder="https://" maxlength="2048" name="cards[{{ loop.index0 }}][url]" value="{{ card.url|default('') }}">
                            </label>
                            <label class="text-sm font-semibold text-gray-90">{{ plan_labels.imageUrl }}
                                <input class="mt-1 w-full rounded-lg border border-gray-25 p-2" type="url" placeholder="https://" maxlength="2048" name="cards[{{ loop.index0 }}][image_url]" value="{{ card.image_url|default('') }}">
                            </label>
                        </div>
                        <div class="bc-localized-editor rounded-2xl border border-gray-25 bg-white p-4" data-bc-localized-editor data-active-language="{{ plan_current_language }}">
                            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                                <div class="flex flex-wrap gap-2" role="tablist" aria-label="{{ plan_labels.language }}" data-bc-lang-tabs>
                                    {% for lang in plan_languages %}
                                        <button type="button" class="bc-lang-tab rounded-lg border px-4 py-2 text-sm font-semibold" data-bc-lang-tab="{{ lang }}" role="tab" aria-selected="{{ lang == plan_current_language ? 'true' : 'false' }}">{{ plan_language_labels[lang] }}</button>
                                    {% endfor %}
                                </div>
                                <span class="text-xs text-gray-50">{{ plan_labels.allLanguagesSaved }}</span>
                            </div>
                            <div data-bc-lang-panels>
                                {% for lang in plan_languages %}
                                    <fieldset class="bc-lang-panel grid gap-4 md:grid-cols-2" data-bc-lang-panel="{{ lang }}" role="tabpanel" aria-label="{{ plan_language_labels[lang] }}" {% if lang != plan_current_language %}hidden{% endif %}>
                                        <label class="block text-sm font-semibold text-gray-90 md:col-span-2">{{ 'Title'|get_lang }}
                                            <input class="mt-1 w-full rounded-lg border border-gray-25 p-2" type="text" maxlength="120" name="cards[{{ cardIndex }}][title][{{ lang }}]" value="{{ card.title[lang]|default('') }}">
                                        </label>
                                        <label class="block text-sm font-semibold text-gray-90">{{ plan_labels.description }}
                                            <textarea class="mt-1 w-full rounded-lg border border-gray-25 p-2" rows="4" name="cards[{{ cardIndex }}][description][{{ lang }}]">{{ card.description[lang]|default('') }}</textarea>
                                        </label>
                                        <label class="block text-sm font-semibold text-gray-90">{{ plan_labels.features }}
                                            <textarea class="mt-1 w-full rounded-lg border border-gray-25 p-2" rows="4" name="cards[{{ cardIndex }}][features][{{ lang }}]">{{ card.features[lang]|default('') }}</textarea>
                                        </label>
                                        <label class="block text-sm font-semibold text-gray-90 md:col-span-2">{{ plan_labels.button }}
                                            <input class="mt-1 w-full rounded-lg border border-gray-25 p-2" type="text" maxlength="100" name="cards[{{ cardIndex }}][button][{{ lang }}]" value="{{ card.button[lang]|default('') }}">
                                        </label>
                                    </fieldset>
                                {% endfor %}
                            </div>
                        </div>
                    </fieldset>
                {% endfor %}
            </div>
        </section>
        <div class="sticky bottom-3 flex justify-end rounded-2xl border border-gray-25 bg-white/95 p-4 shadow-lg">
            <button class="rounded-xl bg-primary px-7 py-3 text-sm font-semibold text-white" type="submit">{{ plan_labels.save }}</button>
        </div>
    </form>
</div>
<script>
(function () {
    'use strict';

    const form = document.getElementById('buycourses-public-plans-form');
    const button = document.getElementById('buycourses-add-plan');
    const container = document.getElementById('buycourses-plan-rows');
    if (!form || !button || !container) return;

    function selectLanguage(editor, language, focus) {
        const tabs = Array.from(editor.querySelectorAll('[data-bc-lang-tab]'));
        const selected = tabs.find(function (tab) { return tab.dataset.bcLangTab === language; });
        if (!selected) return;

        tabs.forEach(function (tab) {
            const isActive = tab === selected;
            tab.setAttribute('aria-selected', isActive ? 'true' : 'false');
            tab.tabIndex = isActive ? 0 : -1;
        });
        editor.querySelectorAll('[data-bc-lang-panel]').forEach(function (panel) {
            panel.hidden = panel.dataset.bcLangPanel !== language;
        });
        editor.dataset.activeLanguage = language;
        if (focus) selected.focus();
    }

    function initEditor(editor) {
        const first = editor.querySelector('[data-bc-lang-tab]');
        if (first) selectLanguage(editor, editor.dataset.activeLanguage || first.dataset.bcLangTab, false);
    }

    form.querySelectorAll('[data-bc-localized-editor]').forEach(initEditor);
    form.addEventListener('click', function (event) {
        const tab = event.target.closest('[data-bc-lang-tab]');
        if (!tab || !form.contains(tab)) return;
        const editor = tab.closest('[data-bc-localized-editor]');
        if (editor) selectLanguage(editor, tab.dataset.bcLangTab, false);
    });
    form.addEventListener('keydown', function (event) {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        const tab = event.target.closest('[data-bc-lang-tab]');
        if (!tab || !form.contains(tab)) return;
        const editor = tab.closest('[data-bc-localized-editor]');
        if (!editor) return;
        const tabs = Array.from(editor.querySelectorAll('[data-bc-lang-tab]'));
        const index = tabs.indexOf(tab);
        if (index < 0) return;
        const next = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1
            : (index + (event.key === 'ArrowRight' ? 1 : -1) + tabs.length) % tabs.length;
        event.preventDefault();
        selectLanguage(editor, tabs[next].dataset.bcLangTab, true);
    });

    container.addEventListener('input', function (event) {
        if (!/\[title\]\[[^\]]+\]$/.test(event.target.name || '')) return;
        const row = event.target.closest('.buycourses-plan-row');
        if (!row) return;
        const label = row.querySelector('[data-bc-card-title]');
        const indexMatch = event.target.name.match(/^cards\[(\d+)\]/);
        if (!label || !indexMatch) return;
        const editor = event.target.closest('[data-bc-localized-editor]');
        const lang = editor ? editor.dataset.activeLanguage : '';
        const title = event.target.value.trim();
        label.textContent = container.dataset.cardLabel + ' ' + (Number(indexMatch[1]) + 1) + (title ? ' · ' + title : '');
    });

    button.addEventListener('click', function () {
        const count = container.querySelectorAll('.buycourses-plan-row').length;
        if (count >= 8) return;
        const existing = container.querySelector('.buycourses-plan-row');
        if (!existing) return;

        const index = Number(container.dataset.nextIndex || 0);
        container.dataset.nextIndex = String(index + 1);
        const clone = existing.cloneNode(true);
        clone.querySelectorAll('[name]').forEach(function (field) {
            field.name = field.name.replace(/cards\[\d+\]/, 'cards[' + index + ']');
            if (field.type === 'checkbox') {
                field.checked = false;
            } else if (field.type !== 'number') {
                field.value = '';
            } else {
                field.value = String((index + 1) * 10);
            }
        });
        clone.querySelectorAll('[data-bc-localized-editor]').forEach(initEditor);
        const clonedTitle = clone.querySelector('[data-bc-card-title]');
        if (clonedTitle) clonedTitle.textContent = container.dataset.cardLabel + ' ' + (index + 1);
        container.appendChild(clone);
        clone.scrollIntoView({behavior: 'smooth', block: 'start'});
    });
}());
</script>
{% endautoescape %}
