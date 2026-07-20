/**
 * Template Manager editor.
 *
 * Deliberately plain DOM and NOT an Alpine.data() component. This file is
 * loaded after the Alpine CDN bundle, and Alpine dispatches alpine:init the
 * moment it starts, so anything calling Alpine.data() from here would register
 * nothing at all and fail silently. Staying out of Alpine entirely makes the
 * script's position in the document irrelevant.
 *
 * There is no syntax highlighter here on purpose. A real Blade highlighter
 * means shipping a tokeniser, and this product has no build step and takes no
 * CDN dependencies. A monospace textarea with a live line gutter, tab-to-indent
 * and a server-side syntax check is more useful to somebody learning Blade than
 * coloured keywords would be, and it costs nothing to maintain.
 */
(function () {
    'use strict';

    function ready(fn) {
        if (document.readyState !== 'loading') {
            fn();
        } else {
            document.addEventListener('DOMContentLoaded', fn);
        }
    }

    function csrf() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        if (meta) return meta.getAttribute('content');
        var input = document.querySelector('input[name="_token"]');
        return input ? input.value : '';
    }

    ready(function () {
        var editor = document.querySelector('[data-template-editor]');
        if (!editor) return;

        var area = editor.querySelector('.mm-code-area');
        var gutter = editor.querySelector('[data-editor-gutter]');
        var stat = editor.querySelector('[data-editor-stat]');
        var status = editor.querySelector('[data-editor-status]');
        var statusText = editor.querySelector('[data-editor-status-text]');
        if (!area) return;

        /* ----------------------------------------------------------
         * Line gutter. Rebuilt only when the line COUNT changes, so
         * typing inside a line costs nothing.
         * -------------------------------------------------------- */
        var lastCount = -1;

        function renderGutter() {
            var count = area.value.split('\n').length;
            if (count === lastCount) return;
            lastCount = count;

            var buffer = '';
            for (var i = 1; i <= count; i++) {
                buffer += i + '\n';
            }
            if (gutter) gutter.textContent = buffer;
            if (stat) stat.textContent = count + (count === 1 ? ' line' : ' lines');
        }

        function syncScroll() {
            if (gutter) gutter.scrollTop = area.scrollTop;
        }

        area.addEventListener('input', function () {
            renderGutter();
            setStatus('idle', 'Not Checked Since Your Last Edit');
        });
        area.addEventListener('scroll', syncScroll);

        /* ----------------------------------------------------------
         * Tab indents instead of leaving the field. Shift+Tab outdents.
         * Escape restores tab-to-next-field so the editor stays keyboard
         * escapable, which matters for accessibility.
         * -------------------------------------------------------- */
        var trapTab = true;

        area.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                trapTab = false;
                return;
            }
            if (e.key !== 'Tab' || !trapTab) return;

            e.preventDefault();

            var start = area.selectionStart;
            var end = area.selectionEnd;
            var value = area.value;

            if (start === end && !e.shiftKey) {
                area.value = value.slice(0, start) + '    ' + value.slice(end);
                area.selectionStart = area.selectionEnd = start + 4;
                renderGutter();
                return;
            }

            // Block indent / outdent across the selected lines.
            var lineStart = value.lastIndexOf('\n', start - 1) + 1;
            var lineEnd = value.indexOf('\n', end);
            if (lineEnd === -1) lineEnd = value.length;

            var block = value.slice(lineStart, lineEnd);
            var updated = e.shiftKey
                ? block.replace(/^ {1,4}/gm, '')
                : block.replace(/^/gm, '    ');

            area.value = value.slice(0, lineStart) + updated + value.slice(lineEnd);
            area.selectionStart = lineStart;
            area.selectionEnd = lineStart + updated.length;
            renderGutter();
        });

        area.addEventListener('focus', function () {
            trapTab = true;
        });

        /* ----------------------------------------------------------
         * Status pill.
         * -------------------------------------------------------- */
        function setStatus(state, text) {
            if (!status) return;
            status.setAttribute('data-state', state);
            if (statusText) statusText.textContent = text;
        }

        /* ----------------------------------------------------------
         * Server-side syntax check. Runs exactly the validator the save
         * runs, so a green result here means the save will be accepted.
         * -------------------------------------------------------- */
        function check() {
            setStatus('checking', 'Checking…');

            return fetch(editor.getAttribute('data-check-url'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ content: area.value })
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.ok) {
                        setStatus('ok', 'No Syntax Errors');
                    } else {
                        var line = data.error && data.error.line ? ' (line ' + data.error.line + ')' : '';
                        setStatus('error', (data.error ? data.error.message : 'Invalid') + line);
                    }
                    return data;
                })
                .catch(function () {
                    setStatus('idle', 'Could Not Reach The Server');
                });
        }

        var checkBtn = document.querySelector('[data-editor-check]');
        if (checkBtn) checkBtn.addEventListener('click', check);

        /* ----------------------------------------------------------
         * Compiled PHP viewer.
         * -------------------------------------------------------- */
        var compiledBtn = document.querySelector('[data-editor-compiled]');
        var compiledOut = document.querySelector('[data-compiled-output]');

        if (compiledBtn && compiledOut) {
            compiledBtn.addEventListener('click', function () {
                window.dispatchEvent(new CustomEvent('open-modal', { detail: 'compiled-php' }));
                compiledOut.textContent = 'Compiling…';

                fetch(editor.getAttribute('data-compiled-url'), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf(),
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({ content: area.value })
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        compiledOut.textContent = data.ok
                            ? data.compiled
                            : 'This template does not compile:\n\n' + (data.error ? data.error.message : 'Unknown error');
                    })
                    .catch(function () {
                        compiledOut.textContent = 'Could not reach the server.';
                    });
            });
        }

        /* ----------------------------------------------------------
         * Preview. Copies the editor's content into the preview form so
         * the draft is what gets staged, then submits it.
         * -------------------------------------------------------- */
        var previewBtn = document.querySelector('[data-editor-preview]');
        var previewForm = document.getElementById('template-preview-form');
        var previewField = document.querySelector('[data-preview-content]');

        if (previewBtn && previewForm && previewField) {
            previewBtn.addEventListener('click', function () {
                previewField.value = area.value;
                previewForm.submit();
            });
        }

        renderGutter();
    });
})();
