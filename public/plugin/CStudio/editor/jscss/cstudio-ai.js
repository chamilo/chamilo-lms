;(function () {
    'use strict'

    var config = window.cstudioAiConfig || {}
    var capabilities = { text: false, image: false, quiz: false }
    var capabilitiesLoaded = false
    var requestRunning = false
    var activeImageTarget = null

    function translate(key) {
        if (typeof window.returnTradTerm === 'function') {
            return window.returnTradTerm(key)
        }

        return key
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;')
    }

    function selectedType(nameObj) {
        if (nameObj === 'teachdoctext') {
            return 'text'
        }

        if (nameObj === 'image') {
            return 'image'
        }

        if (nameObj === 'qcmbarre') {
            return 'quiz'
        }

        return ''
    }

    function currentType() {
        return selectedType(typeof window.tmpNameObj === 'string' ? window.tmpNameObj : '')
    }

    function normalizeLegacyTarget() {
        var target = window.tmpObjDom

        if (target && target.jquery) {
            target = target.get(0)
        }

        if (!target || target.nodeType !== 1) {
            return null
        }

        return target
    }

    function findComponentByElement(element) {
        if (!element || !window.editor || !window.editor.DomComponents) {
            return null
        }

        var match = null
        var wrapper = window.editor.DomComponents.getWrapper()

        if (!wrapper || typeof wrapper.onAll !== 'function') {
            return null
        }

        wrapper.onAll(function (component) {
            if (match || !component || typeof component.getEl !== 'function') {
                return
            }

            if (component.getEl() === element) {
                match = component
            }
        })

        return match
    }

    function resolveImageTarget() {
        var legacyTarget = normalizeLegacyTarget()

        if (legacyTarget && String(legacyTarget.tagName || '').toLowerCase() !== 'img') {
            legacyTarget = legacyTarget.querySelector ? legacyTarget.querySelector('img') : null
        }

        var exactComponent = findComponentByElement(legacyTarget)

        if (exactComponent) {
            return {
                component: exactComponent,
                element: legacyTarget,
            }
        }

        var selected = window.editor && window.editor.getSelected ? window.editor.getSelected() : null

        if (selected && String(selected.get('tagName') || '').toLowerCase() === 'img') {
            return {
                component: selected,
                element: selected.getEl ? selected.getEl() : legacyTarget,
            }
        }

        if (selected && typeof selected.findType === 'function') {
            var images = selected.findType('image')

            if (legacyTarget) {
                for (var index = 0; index < images.length; index++) {
                    if (images[index].getEl && images[index].getEl() === legacyTarget) {
                        return {
                            component: images[index],
                            element: legacyTarget,
                        }
                    }
                }
            }

            if (images.length === 1) {
                return {
                    component: images[0],
                    element: images[0].getEl ? images[0].getEl() : legacyTarget,
                }
            }
        }

        return {
            component: null,
            element: legacyTarget,
        }
    }

    window.cstudioAiCanHandleSelection = function (nameObj) {
        var type = selectedType(nameObj)

        if (type === '') {
            type = currentType()
        }

        return capabilitiesLoaded && type !== '' && capabilities[type] === true
    }

    window.cstudioAiInstallSpeedTool = function () {
        var type = currentType()
        var container = document.querySelector('.ludiSpeedTools')

        if (!container || type === '' || capabilities[type] !== true) {
            return
        }

        var button = container.querySelector('.cstudio-ai-speed-tool')

        if (!button) {
            button = document.createElement('a')
            button.href = '#'
            button.className = 'cstudio-ai-speed-tool'
            button.title = translate('Generate with AI')
            button.setAttribute('aria-label', translate('Generate with AI'))
            button.innerHTML = '<span aria-hidden="true">🤖</span>'
            button.addEventListener('click', function (event) {
                event.preventDefault()

                var requestedType = button.getAttribute('data-cstudio-ai-type') || currentType()

                if (requestedType !== '') {
                    activeImageTarget = requestedType === 'image' ? resolveImageTarget() : null
                    openModal(requestedType)
                }
            })
            container.appendChild(button)
        }

        button.setAttribute('data-cstudio-ai-type', type)
    }

    function request(action, payload) {
        if (requestRunning) {
            return Promise.reject(new Error(translate('An AI request is already running.')))
        }

        requestRunning = true

        return fetch(config.endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify(
                Object.assign(
                    {
                        action: action,
                        page_id: Number(config.pageId || 0),
                        csrf_token: String(config.csrfToken || ''),
                        language: String(config.locale || 'en'),
                    },
                    payload || {},
                ),
            ),
        })
            .then(function (response) {
                return response
                    .json()
                    .catch(function () {
                        throw new Error(translate('The server returned an invalid response.'))
                    })
                    .then(function (data) {
                        if (!response.ok || data.success !== true) {
                            throw new Error(data.message || translate('The AI request could not be completed.'))
                        }

                        return data
                    })
            })
            .finally(function () {
                requestRunning = false
            })
    }

    function loadCapabilities() {
        request('capabilities', {})
            .then(function (data) {
                capabilities = Object.assign(capabilities, data.capabilities || {})
                capabilitiesLoaded = true

                if (typeof window.installSpeedTools === 'function') {
                    window.baseNameObj = ''
                    window.installSpeedTools()
                }
            })
            .catch(function () {
                capabilitiesLoaded = true
            })
    }

    function closeModal() {
        var modal = document.getElementById('cstudio-ai-modal')

        if (modal) {
            modal.remove()
        }
    }

    function modalShell(title, body) {
        closeModal()

        var modal = document.createElement('div')
        modal.id = 'cstudio-ai-modal'
        modal.className = 'cstudio-ai-modal'
        modal.innerHTML =
            '<div class="cstudio-ai-dialog" role="dialog" aria-modal="true" aria-labelledby="cstudio-ai-title">' +
            '<div class="cstudio-ai-header">' +
            '<h2 id="cstudio-ai-title"><span aria-hidden="true">🤖</span> ' +
            escapeHtml(title) +
            '</h2>' +
            '<button type="button" class="cstudio-ai-close" aria-label="' +
            escapeHtml(translate('Close')) +
            '">×</button>' +
            '</div>' +
            '<div class="cstudio-ai-body">' +
            body +
            '<div class="cstudio-ai-status" aria-live="polite"></div>' +
            '</div>' +
            '</div>'

        modal.querySelector('.cstudio-ai-close').addEventListener('click', closeModal)
        modal.addEventListener('click', function (event) {
            if (event.target === modal && !requestRunning) {
                closeModal()
            }
        })

        document.body.appendChild(modal)

        return modal
    }

    function setStatus(modal, message, isError) {
        var status = modal.querySelector('.cstudio-ai-status')

        status.textContent = message || ''
        status.classList.toggle('cstudio-ai-status--error', isError === true)
    }

    function setBusy(modal, busy) {
        var submit = modal.querySelector('[data-cstudio-ai-submit]')
        var fields = modal.querySelectorAll('input, textarea, select, button')

        Array.prototype.forEach.call(fields, function (field) {
            if (!field.classList.contains('cstudio-ai-close')) {
                field.disabled = busy
            }
        })

        if (submit) {
            submit.classList.toggle('cstudio-ai-loading', busy)
            submit.textContent = busy ? translate('Generating…') : submit.getAttribute('data-label')
        }
    }

    function openModal(type) {
        if (type === 'text') {
            openTextModal()
        } else if (type === 'image') {
            openImageModal()
        } else if (type === 'quiz') {
            openQuizModal()
        }
    }

    function openTextModal() {
        var body =
            '<form id="cstudio-ai-form">' +
            '<label for="cstudio-ai-prompt">' +
            escapeHtml(translate('Instructions')) +
            '</label>' +
            '<textarea id="cstudio-ai-prompt" name="prompt" rows="6" maxlength="4000" required></textarea>' +
            '<label for="cstudio-ai-words">' +
            escapeHtml(translate('Approximate number of words')) +
            '</label>' +
            '<input id="cstudio-ai-words" name="words" type="number" min="25" max="1500" value="200" required>' +
            '<div class="cstudio-ai-actions">' +
            submitButton(translate('Generate text')) +
            '</div>' +
            '</form>'

        var modal = modalShell(translate('Generate text with AI'), body)
        var form = modal.querySelector('#cstudio-ai-form')

        form.addEventListener('submit', function (event) {
            event.preventDefault()
            setBusy(modal, true)
            setStatus(modal, translate('AI is generating text. Please wait.'), false)

            request('text', {
                prompt: form.elements.prompt.value,
                words: Number(form.elements.words.value),
            })
                .then(function (data) {
                    applyText(data.text || '')
                    setStatus(modal, translate('Text generated successfully.'), false)
                    window.setTimeout(closeModal, 350)
                })
                .catch(function (error) {
                    setStatus(modal, error.message, true)
                })
                .finally(function () {
                    setBusy(modal, false)
                })
        })

        form.elements.prompt.focus()
    }

    function applyText(text) {
        var paragraphs = String(text)
            .split(/\n{2,}/)
            .map(function (paragraph) {
                return paragraph.trim()
            })
            .filter(function (paragraph) {
                return paragraph !== ''
            })
            .map(function (paragraph) {
                return '<p>' + escapeHtml(paragraph).replace(/\n/g, '<br>') + '</p>'
            })
            .join('')

        if (paragraphs === '') {
            throw new Error(translate('The AI provider returned empty text.'))
        }

        var content =
            '<tbody><tr><td class="teachdoctextContent">' +
            paragraphs +
            '</td></tr></tbody>'

        if (window.GlobalTagGrappeObj === 'div') {
            content =
                '<table class="teachdoctext" onMouseDown="parent.displayEditButon(this);" style="width:97%;">' +
                content +
                '</table>'
        }

        window.setAbstractObjContent(content)
        persistEditor()
    }

    function openImageModal() {
        var body =
            '<form id="cstudio-ai-form">' +
            '<label for="cstudio-ai-prompt">' +
            escapeHtml(translate('Image topic')) +
            '</label>' +
            '<textarea id="cstudio-ai-prompt" name="prompt" rows="5" maxlength="2000" required></textarea>' +
            '<fieldset class="cstudio-ai-formats">' +
            '<legend>' +
            escapeHtml(translate('Image format')) +
            '</legend>' +
            radioFormat('square', translate('Square'), true) +
            radioFormat('landscape', translate('Landscape'), false) +
            radioFormat('portrait', translate('Portrait'), false) +
            '</fieldset>' +
            '<div class="cstudio-ai-actions">' +
            submitButton(translate('Generate image')) +
            '</div>' +
            '</form>'

        var modal = modalShell(translate('Generate image with AI'), body)
        var form = modal.querySelector('#cstudio-ai-form')

        form.addEventListener('submit', function (event) {
            event.preventDefault()
            setBusy(modal, true)
            setStatus(modal, translate('AI is generating the image. This may take a moment.'), false)

            request('image', {
                prompt: form.elements.prompt.value,
                format: form.elements.format.value,
            })
                .then(function (data) {
                    applyImage(data.url || '')
                    setStatus(modal, translate('Image generated successfully.'), false)
                    window.setTimeout(closeModal, 350)
                })
                .catch(function (error) {
                    setStatus(modal, error.message, true)
                })
                .finally(function () {
                    setBusy(modal, false)
                })
        })

        form.elements.prompt.focus()
    }

    function radioFormat(value, label, checked) {
        return (
            '<label class="cstudio-ai-format">' +
            '<input type="radio" name="format" value="' +
            escapeHtml(value) +
            '"' +
            (checked ? ' checked' : '') +
            '>' +
            '<span class="cstudio-ai-format-shape cstudio-ai-format-shape--' +
            escapeHtml(value) +
            '"></span>' +
            '<span>' +
            escapeHtml(label) +
            '</span>' +
            '</label>'
        )
    }

    function applyImage(url) {
        if (url === '') {
            throw new Error(translate('The AI provider returned an empty image.'))
        }

        var target = activeImageTarget || resolveImageTarget()
        var component = target ? target.component : null
        var element = target ? target.element : null

        if (!component && element) {
            component = findComponentByElement(element)
        }

        if (!component || String(component.get('tagName') || '').toLowerCase() !== 'img') {
            throw new Error(translate('Select an image element before generating.'))
        }

        var attributes = Object.assign({}, component.get('attributes') || {})

        component.set('attributes', attributes)
        component.set('src', url)
        component.addAttributes({
            alt: translate('AI-generated image'),
        })

        if (element && String(element.tagName || '').toLowerCase() === 'img') {
            element.setAttribute('src', url)
            element.setAttribute('alt', translate('AI-generated image'))
        }

        if (component.get('src') !== url) {
            throw new Error(translate('The generated image could not be applied to the selected element.'))
        }

        activeImageTarget = null
        window.setTimeout(persistEditor, 0)
    }

    function getTextBlockSources() {
        var result = []
        var documentFrame = null

        try {
            documentFrame = window.editor.Canvas.getDocument()
        } catch (error) {
            documentFrame = null
        }

        if (!documentFrame) {
            return result
        }

        var nodes = documentFrame.querySelectorAll('table.teachdoctext .teachdoctextContent')

        Array.prototype.forEach.call(nodes, function (node, index) {
            var text = String(node.textContent || '')
                .replace(/\s+/g, ' ')
                .trim()

            if (text !== '') {
                result.push({
                    index: index,
                    text: text,
                    label: text.length > 90 ? text.slice(0, 87) + '…' : text,
                })
            }
        })

        return result
    }

    function openQuizModal() {
        var sources = getTextBlockSources()
        var sourceOptions =
            '<option value="">' + escapeHtml(translate('No text block selected')) + '</option>'

        sources.forEach(function (source, index) {
            sourceOptions +=
                '<option value="' +
                String(index) +
                '">' +
                escapeHtml(source.label) +
                '</option>'
        })

        var body =
            '<form id="cstudio-ai-form">' +
            '<label for="cstudio-ai-topic">' +
            escapeHtml(translate('Quiz topic')) +
            '</label>' +
            '<textarea id="cstudio-ai-topic" name="topic" rows="4" maxlength="2000"></textarea>' +
            '<label for="cstudio-ai-source">' +
            escapeHtml(translate('Use a text block as source')) +
            '</label>' +
            '<select id="cstudio-ai-source" name="source">' +
            sourceOptions +
            '</select>' +
            '<label for="cstudio-ai-questions">' +
            escapeHtml(translate('Number of questions')) +
            '</label>' +
            '<input id="cstudio-ai-questions" name="questions" type="number" min="1" max="10" value="3" required>' +
            '<div class="cstudio-ai-actions">' +
            submitButton(translate('Generate quiz')) +
            '</div>' +
            '</form>'

        var modal = modalShell(translate('Generate quiz with AI'), body)
        var form = modal.querySelector('#cstudio-ai-form')

        form.addEventListener('submit', function (event) {
            event.preventDefault()

            var sourceIndex = form.elements.source.value
            var sourceText = sourceIndex === '' ? '' : (sources[Number(sourceIndex)] || {}).text || ''

            if (form.elements.topic.value.trim() === '' && sourceText === '') {
                setStatus(modal, translate('Enter a topic or select a text block as the source.'), true)

                return
            }

            setBusy(modal, true)
            setStatus(modal, translate('AI is generating quiz questions. Please wait.'), false)

            request('quiz', {
                topic: form.elements.topic.value,
                source_text: sourceText,
                questions: Number(form.elements.questions.value),
            })
                .then(function (data) {
                    applyQuiz(data.questions || [])
                    setStatus(modal, translate('Quiz generated successfully.'), false)
                    window.setTimeout(closeModal, 350)
                })
                .catch(function (error) {
                    setStatus(modal, error.message, true)
                })
                .finally(function () {
                    setBusy(modal, false)
                })
        })

        form.elements.topic.focus()
    }

    function applyQuiz(questions) {
        if (!Array.isArray(questions) || questions.length === 0) {
            throw new Error(translate('The AI provider returned no valid questions.'))
        }

        var selected = window.editor && window.editor.getSelected ? window.editor.getSelected() : null

        if (!selected) {
            throw new Error(translate('Select a quiz element before generating.'))
        }

        var html = questions.map(buildQuizHtml).join('')

        if (selected.get('tagName') === 'table') {
            var replacements = selected.replaceWith(html)

            if (replacements && replacements.length && window.editor.select) {
                window.editor.select(replacements[0])
            }
        } else if (window.GlobalTagGrappeObj === 'div') {
            window.setAbstractObjContent(html)
        } else {
            throw new Error(translate('Select a quiz element before generating.'))
        }

        persistEditor()
    }

    function buildQuizHtml(question) {
        var answers = Array.isArray(question.answers) ? question.answers.slice(0, 6) : []
        var multiple = answers.filter(function (answer) {
            return answer.correct === true
        }).length > 1
        var prefix = multiple ? 'c' : 'm'
        var html =
            '<table class="qcmbarre" onMouseDown="parent.displayEditButon(this);" style="width:100%;">' +
            '<tbody><div class="quizzblockdeco"></div>'

        if (multiple) {
            html += '<div class="multiqcmopts"></div>'
        }

        html +=
            '<tr><td colspan="2" class="quizzTextqcm">' +
            escapeHtml(question.question || '') +
            '</td></tr>'

        answers.forEach(function (answer) {
            var state = answer.correct === true ? '1' : '0'

            html +=
                '<tr class="quizzTextTr">' +
                '<td class="quizzTextTd">' +
                '<img src="img/qcm/' +
                prefix +
                'atgreen' +
                state +
                '.png" class="checkboxqcm">' +
                '</td>' +
                '<td style="text-align:left;">' +
                escapeHtml(answer.text || '') +
                '</td>' +
                '</tr>'
        })

        if (typeof window.cstudioBuildQuizCheckButtonHtml === 'function') {
            html += window.cstudioBuildQuizCheckButtonHtml()
        }

        html += '</tbody></table>'

        return html
    }

    function submitButton(label) {
        return (
            '<button type="submit" class="cstudio-ai-submit" data-cstudio-ai-submit data-label="' +
            escapeHtml(label) +
            '">' +
            escapeHtml(label) +
            '</button>'
        )
    }

    function persistEditor() {
        if (window.editor && window.localStorage && typeof window.idPageHtml !== 'undefined') {
            window.localStorage.setItem('gjs-html-' + window.idPageHtml, window.editor.getHtml())
            window.localStorage.setItem('gjs-css-' + window.idPageHtml, window.editor.getCss())
        }

        if (typeof window.activeEventSave === 'function') {
            window.activeEventSave()
        }

        if (typeof window.saveSourceFrame === 'function') {
            window.saveSourceFrame(false, false, 0)
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadCapabilities)
    } else {
        loadCapabilities()
    }
}())
