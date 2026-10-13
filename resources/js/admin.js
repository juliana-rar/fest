/**
 * Admin panel entry point.
 */
import { initTheme } from './modules/theme.js';
import { initConfirm } from './admin/confirm.js';
import { initEditor } from './admin/editor.js';
import { initRepeater } from './admin/repeater.js';
import { initImagePreview, initSlugPreview, initFlash } from './admin/uploads.js';
import { initPicker } from './admin/picker.js';

initTheme();
initConfirm();
initEditor();
initRepeater();
initImagePreview();
initSlugPreview();
initFlash();
initPicker();
