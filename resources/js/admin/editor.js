/**
 * Lightweight rich-text editor (no dependencies): a contenteditable region
 * with a small toolbar, mirrored into the original <textarea> so the form
 * keeps working without JavaScript. The HTML is sanitised server-side
 * (App\Services\HtmlSanitizer) with a strict whitelist.
 */
export function initEditor() {
    const fieldset = document.getElementById('editor');
    if (!fieldset) return;

    const toolbar = fieldset.querySelector('menu');
    const editable = fieldset.querySelector('[contenteditable]');
    const textarea = fieldset.querySelector('textarea');

    editable.innerHTML = textarea.value;
    textarea.hidden = true;
    toolbar.hidden = false;
    editable.hidden = false;

    try {
        document.execCommand('defaultParagraphSeparator', false, 'p');
    } catch {
        /* not supported: browsers fall back to <div>, unwrapped by the sanitizer */
    }

    const sync = () => {
        textarea.value = editable.innerHTML.trim() === '<br>' ? '' : editable.innerHTML;
    };

    const updateState = () => {
        toolbar.querySelectorAll('button[data-cmd]').forEach((button) => {
            const cmd = button.dataset.cmd;
            if (['bold', 'italic', 'insertUnorderedList', 'insertOrderedList'].includes(cmd)) {
                button.setAttribute('aria-pressed', String(document.queryCommandState(cmd)));
            }
        });
    };

    toolbar.addEventListener('click', (event) => {
        const button = event.target.closest('button[data-cmd]');
        if (!button) return;

        const { cmd, value } = button.dataset;
        editable.focus();

        if (cmd === 'createLink') {
            const url = window.prompt(button.dataset.prompt, 'https://');
            if (!url || !/^(https?:\/\/|mailto:)/i.test(url)) return;
            document.execCommand('createLink', false, url);
        } else if (cmd === 'formatBlock') {
            const current = document.queryCommandValue('formatBlock').toLowerCase();
            document.execCommand('formatBlock', false, current === value ? 'p' : value);
        } else {
            document.execCommand(cmd, false, value ?? null);
        }

        sync();
        updateState();
    });

    // Paste as plain text to avoid importing foreign styles.
    editable.addEventListener('paste', (event) => {
        event.preventDefault();
        const text = event.clipboardData.getData('text/plain');
        document.execCommand('insertText', false, text);
    });

    // Keyboard shortcuts Ctrl/Cmd + B / I.
    editable.addEventListener('keydown', (event) => {
        if (!(event.ctrlKey || event.metaKey)) return;
        const map = { b: 'bold', i: 'italic' };
        const cmd = map[event.key.toLowerCase()];
        if (cmd) {
            event.preventDefault();
            document.execCommand(cmd);
            sync();
            updateState();
        }
    });

    editable.addEventListener('input', sync);
    editable.addEventListener('keyup', updateState);
    editable.addEventListener('mouseup', updateState);
    textarea.form?.addEventListener('submit', sync);
}
