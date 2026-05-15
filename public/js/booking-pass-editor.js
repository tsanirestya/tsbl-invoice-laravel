/**
 * booking-pass-editor.js
 * Visual drag-and-drop layout editor for Booking Pass templates.
 */
(function () {
    'use strict';

    // ── DOM refs ──────────────────────────────────────────────────────────────
    const canvas = document.getElementById('bp-canvas');
    if (!canvas) return;

    const dropZone       = document.getElementById('bp-drop-zone');
    const stylePanel     = document.getElementById('bp-style-panel');
    const zoomWrap       = document.getElementById('bp-canvas-zoom-wrap');
    const customVarsList = document.getElementById('custom-vars-list');

    const CANVAS_W = 794;
    const CANVAS_H = 1123;

    const SAVE_URL      = canvas.dataset.saveUrl;
    const UPLOAD_BG_URL = canvas.dataset.uploadBgUrl;
    const PREVIEW_URL   = canvas.dataset.previewUrl;
    const CSRF          = canvas.dataset.csrf;

    // ── State ─────────────────────────────────────────────────────────────────
    let state = {
        fields:          [],
        customFields:    [],
        history:         [],
        redoStack:       [],
        selectedKey:     null,
        zoom:            1,
        gridOn:          false,
        draggingSidebar: null,
        draggingBox:     null,
        resizing:        null,
    };

    // Canvas preview values (editor only, not PDF)
    const PREVIEW_VALUES = {
        reservation_no:   'RES-20260513-0001',
        guest_name:       'John Doe',
        guest_country:    'Indonesia',
        visit_date:       '13 May 2026',
        partner_name:     'Hotel ABC',
        product_name:     'Trans Studio Theme Park',
        payment_method:   'DEPOSIT',
        payment_channel:  'Transfer',
        total_amount:     'Rp 500.000',
        status:           'CONFIRMED',
        notes:            'VIP Guest',
        created_at:       '13/05/2026 10:00',
        items_table:      '[Tabel Produk]',
        items_list:       '• Trans Studio x2\n• Water World x2',
        qr_code:          'RES-20260513-0001',
        logo:             '[LOGO]',
    };

    // ── Load initial mapping ──────────────────────────────────────────────────
    function init() {
        const raw = document.getElementById('bp-initial-mapping');
        if (!raw) return;
        try {
            const data       = JSON.parse(raw.textContent);
            const allFields  = [...(data.fields || []), ...(data.custom_fields || [])];
            state.fields     = allFields;
            state.customFields = (data.custom_fields || []).map(f => ({ key: f.key, label: f.label }));

            renderCustomVarsSidebar();
            allFields.forEach(f => { if (f.visible !== false) createBox(f); });
            refreshSidebarState();
        } catch (e) {
            console.warn('BP Editor: could not parse initial mapping', e);
        }
        pushHistory();
    }

    // ── History ───────────────────────────────────────────────────────────────
    function pushHistory() {
        state.history.push(JSON.stringify(state.fields));
        state.redoStack = [];
        document.getElementById('btn-undo').disabled = state.history.length <= 1;
        document.getElementById('btn-redo').disabled = true;
    }

    function undo() {
        if (state.history.length <= 1) return;
        state.redoStack.push(state.history.pop());
        restoreSnapshot(state.history[state.history.length - 1]);
        document.getElementById('btn-undo').disabled = state.history.length <= 1;
        document.getElementById('btn-redo').disabled = false;
    }

    function redo() {
        if (!state.redoStack.length) return;
        const snap = state.redoStack.pop();
        state.history.push(snap);
        restoreSnapshot(snap);
        document.getElementById('btn-undo').disabled = false;
        document.getElementById('btn-redo').disabled = !state.redoStack.length;
    }

    function restoreSnapshot(snap) {
        state.fields = JSON.parse(snap);
        canvas.querySelectorAll('.bp-field-box').forEach(el => el.remove());
        state.fields.forEach(f => { if (f.visible !== false) createBox(f); });
        refreshSidebarState();
        deselectAll();
    }

    // ── Preview value helper ──────────────────────────────────────────────────
    function getPreviewValue(fieldDef) {
        const key        = fieldDef.key;
        const outputType = fieldDef.output_type || 'text';
        const rawText    = PREVIEW_VALUES[key] ?? (key.startsWith('booking_pass_data.') ? '[' + fieldDef.label + ']' : fieldDef.label);

        if (outputType === 'qr') {
            return rawText;
        }
        if (outputType === 'barcode') {
            return '▐▌ BARCODE: ' + rawText;
        }
        return rawText;
    }

    // ── Create a box on canvas ────────────────────────────────────────────────
    function createBox(fieldDef) {
        const existing = canvas.querySelector(`.bp-field-box[data-key="${fieldDef.key}"]`);
        if (existing) { updateBoxStyle(existing, fieldDef); return; }

        const box = document.createElement('div');
        box.className    = 'bp-field-box';
        box.dataset.key  = fieldDef.key;
        applyBoxStyles(box, fieldDef);

        const showLabel = fieldDef.show_label !== false;
        if (showLabel) {
            const labelEl = document.createElement('span');
            labelEl.className = 'bp-field-label';
            labelEl.textContent = fieldDef.label;
            applyLabelStyles(labelEl, fieldDef);
            box.appendChild(labelEl);
        }

        const valueEl = document.createElement('span');
        valueEl.className   = 'bp-field-value';
        valueEl.textContent = getPreviewValue(fieldDef);
        box.appendChild(valueEl);

        const removeBtn = document.createElement('button');
        removeBtn.className = 'bp-remove-btn';
        removeBtn.innerHTML = '×';
        removeBtn.title     = 'Hapus dari canvas';
        removeBtn.addEventListener('click', (e) => { e.stopPropagation(); removeBox(fieldDef.key); });
        box.appendChild(removeBtn);

        const resizeHandle = document.createElement('div');
        resizeHandle.className = 'bp-resize-handle';
        box.appendChild(resizeHandle);

        box.addEventListener('mousedown', onBoxMousedown);
        resizeHandle.addEventListener('mousedown', onResizeMousedown);
        box.addEventListener('click', (e) => { e.stopPropagation(); selectBox(fieldDef.key); });

        canvas.appendChild(box);
    }

    function applyBoxStyles(box, fieldDef) {
        box.style.left       = (fieldDef.x_pct || 0) + '%';
        box.style.top        = (fieldDef.y_pct || 0) + '%';
        box.style.width      = (fieldDef.width_pct || 25) + '%';
        box.style.fontSize   = (fieldDef.font_size || 12) + 'px';
        box.style.fontWeight = fieldDef.font_weight || 'normal';
        box.style.color      = fieldDef.color || '#000000';
        box.style.textAlign  = fieldDef.align || 'left';

        // Visual cue for qr/barcode output type
        const outputType = fieldDef.output_type || 'text';
        if (outputType === 'qr') {
            box.style.border = '2px dashed #7c3aed';
        } else if (outputType === 'barcode') {
            box.style.border = '2px dashed #d97706';
        } else {
            box.style.border = '';
        }
    }

    function applyLabelStyles(labelEl, fieldDef) {
        if (fieldDef.label_font_size) {
            labelEl.style.fontSize = fieldDef.label_font_size + 'px';
        }
        if (fieldDef.label_color) {
            labelEl.style.color = fieldDef.label_color;
        }
    }

    function updateBoxStyle(box, fieldDef) {
        applyBoxStyles(box, fieldDef);

        const labelEl   = box.querySelector('.bp-field-label');
        const valueEl   = box.querySelector('.bp-field-value');
        const showLabel = fieldDef.show_label !== false;

        if (showLabel && !labelEl) {
            const lbl = document.createElement('span');
            lbl.className   = 'bp-field-label';
            lbl.textContent = fieldDef.label;
            applyLabelStyles(lbl, fieldDef);
            box.insertBefore(lbl, box.firstChild);
        } else if (!showLabel && labelEl) {
            labelEl.remove();
        } else if (labelEl) {
            labelEl.textContent = fieldDef.label;
            applyLabelStyles(labelEl, fieldDef);
        }

        if (valueEl) {
            valueEl.textContent = getPreviewValue(fieldDef);
        }
    }

    function removeBox(key) {
        const box = canvas.querySelector(`.bp-field-box[data-key="${key}"]`);
        if (box) box.remove();
        state.fields = state.fields.filter(f => f.key !== key);
        if (state.selectedKey === key) deselectAll();
        refreshSidebarState();
        pushHistory();
    }

    // ── Field state helpers ───────────────────────────────────────────────────
    function getField(key) { return state.fields.find(f => f.key === key); }

    function upsertField(key, props) {
        const idx = state.fields.findIndex(f => f.key === key);
        if (idx >= 0) {
            state.fields[idx] = { ...state.fields[idx], ...props };
        } else {
            state.fields.push(props);
        }
    }

    // ── Sidebar state ─────────────────────────────────────────────────────────
    function refreshSidebarState() {
        const onCanvas = new Set(state.fields.map(f => f.key));
        document.querySelectorAll('.bp-var-item').forEach(el => {
            el.classList.toggle('on-canvas', onCanvas.has(el.dataset.key));
            el.draggable = !onCanvas.has(el.dataset.key);
        });
    }

    function renderCustomVarsSidebar() {
        customVarsList.innerHTML = '';
        state.customFields.forEach(cf => {
            const div = document.createElement('div');
            div.className = 'bp-var-item';
            div.draggable = true;
            div.dataset.key   = cf.key;
            div.dataset.label = cf.label;
            div.innerHTML = `
                <i class="bi bi-tag var-drag-icon"></i>
                <div class="var-label">${cf.label}<div class="var-key">${cf.key}</div></div>
                <i class="bi bi-grip-vertical text-muted" style="font-size:14px;"></i>
            `;
            div.addEventListener('dragstart', onSidebarDragStart);
            customVarsList.appendChild(div);
        });
        refreshSidebarState();
    }

    // ── Drag from sidebar ─────────────────────────────────────────────────────
    function onSidebarDragStart(e) {
        const item = e.currentTarget;
        if (!item.draggable || item.classList.contains('on-canvas')) {
            e.preventDefault();
            return;
        }
        state.draggingSidebar = { key: item.dataset.key, label: item.dataset.label };
        e.dataTransfer.effectAllowed = 'copy';
        e.dataTransfer.setData('text/plain', item.dataset.key);
    }

    // ── Drop on canvas ────────────────────────────────────────────────────────
    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
        canvas.classList.add('drag-over');
    });

    dropZone.addEventListener('dragleave', () => canvas.classList.remove('drag-over'));

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        canvas.classList.remove('drag-over');
        if (!state.draggingSidebar) return;

        const rect = canvas.getBoundingClientRect();
        const xPx  = (e.clientX - rect.left) / state.zoom;
        const yPx  = (e.clientY - rect.top)  / state.zoom;
        const xPct = clamp((xPx / CANVAS_W) * 100, 0, 90);
        const yPct = clamp((yPx / CANVAS_H) * 100, 0, 95);

        const fieldDef = {
            key:              state.draggingSidebar.key,
            label:            state.draggingSidebar.label,
            x_pct:            round2(xPct),
            y_pct:            round2(yPct),
            width_pct:        30,
            font_size:        12,
            font_weight:      'normal',
            color:            '#000000',
            align:            'left',
            show_label:       true,
            label_font_size:  9,
            label_color:      '#64748b',
            output_type:      'text',
            visible:          true,
        };

        upsertField(fieldDef.key, fieldDef);
        createBox(fieldDef);
        refreshSidebarState();
        selectBox(fieldDef.key);
        pushHistory();

        state.draggingSidebar = null;
    });

    // ── Box reposition ────────────────────────────────────────────────────────
    function onBoxMousedown(e) {
        if (e.target.classList.contains('bp-remove-btn'))   return;
        if (e.target.classList.contains('bp-resize-handle')) return;
        e.preventDefault();

        const box = e.currentTarget;
        state.draggingBox = {
            key:      box.dataset.key,
            startX:   e.clientX,
            startY:   e.clientY,
            origLeft: parseFloat(box.style.left),
            origTop:  parseFloat(box.style.top),
        };
        document.addEventListener('mousemove', onBoxMousemove);
        document.addEventListener('mouseup',   onBoxMouseup, { once: true });
    }

    function onBoxMousemove(e) {
        if (!state.draggingBox) return;
        const db    = state.draggingBox;
        const dxPct = ((e.clientX - db.startX) / state.zoom / CANVAS_W) * 100;
        const dyPct = ((e.clientY - db.startY) / state.zoom / CANVAS_H) * 100;
        const newLeft = clamp(db.origLeft + dxPct, 0, 98);
        const newTop  = clamp(db.origTop  + dyPct, 0, 98);

        const box = canvas.querySelector(`.bp-field-box[data-key="${db.key}"]`);
        if (box) { box.style.left = newLeft + '%'; box.style.top = newTop + '%'; }

        if (state.selectedKey === db.key) {
            document.getElementById('sp-x').value = round2(newLeft);
            document.getElementById('sp-y').value = round2(newTop);
        }
    }

    function onBoxMouseup() {
        if (!state.draggingBox) return;
        const db  = state.draggingBox;
        const box = canvas.querySelector(`.bp-field-box[data-key="${db.key}"]`);
        if (box) {
            upsertField(db.key, { x_pct: round2(parseFloat(box.style.left)), y_pct: round2(parseFloat(box.style.top)) });
        }
        document.removeEventListener('mousemove', onBoxMousemove);
        state.draggingBox = null;
        pushHistory();
    }

    // ── Resize ────────────────────────────────────────────────────────────────
    function onResizeMousedown(e) {
        e.preventDefault();
        e.stopPropagation();
        const box = e.currentTarget.parentElement;
        state.resizing = { key: box.dataset.key, startX: e.clientX, origWidth: parseFloat(box.style.width) };
        document.addEventListener('mousemove', onResizeMousemove);
        document.addEventListener('mouseup',   onResizeMouseup, { once: true });
    }

    function onResizeMousemove(e) {
        if (!state.resizing) return;
        const r    = state.resizing;
        const dxPct = ((e.clientX - r.startX) / state.zoom / CANVAS_W) * 100;
        const newW  = clamp(r.origWidth + dxPct, 5, 100);
        const box   = canvas.querySelector(`.bp-field-box[data-key="${r.key}"]`);
        if (box) box.style.width = newW + '%';
        if (state.selectedKey === r.key) document.getElementById('sp-width').value = round2(newW);
    }

    function onResizeMouseup() {
        if (!state.resizing) return;
        const r   = state.resizing;
        const box = canvas.querySelector(`.bp-field-box[data-key="${r.key}"]`);
        if (box) upsertField(r.key, { width_pct: round2(parseFloat(box.style.width)) });
        state.resizing = null;
        pushHistory();
    }

    // ── Selection & Style Panel ───────────────────────────────────────────────
    function selectBox(key) {
        deselectAll();
        state.selectedKey = key;

        const box = canvas.querySelector(`.bp-field-box[data-key="${key}"]`);
        if (box) box.classList.add('selected');

        const field = getField(key);
        if (!field) return;

        document.getElementById('style-field-name').textContent     = field.label;
        document.getElementById('sp-font-size').value               = field.font_size        || 12;
        document.getElementById('sp-font-weight').value             = field.font_weight       || 'normal';
        document.getElementById('sp-color').value                   = field.color            || '#000000';
        document.getElementById('sp-width').value                   = field.width_pct        || 30;
        document.getElementById('sp-x').value                       = round2(field.x_pct     || 0);
        document.getElementById('sp-y').value                       = round2(field.y_pct     || 0);
        document.getElementById('sp-show-label').checked            = field.show_label !== false;
        document.getElementById('sp-label-font-size').value         = field.label_font_size  || 9;
        document.getElementById('sp-label-color').value             = field.label_color      || '#64748b';
        document.getElementById('sp-output-type').value             = field.output_type      || 'text';

        setActiveAlignBtn(field.align || 'left');
        stylePanel.classList.add('visible');
    }

    function deselectAll() {
        canvas.querySelectorAll('.bp-field-box.selected').forEach(b => b.classList.remove('selected'));
        state.selectedKey = null;
        stylePanel.classList.remove('visible');
    }

    document.getElementById('bp-drop-zone').addEventListener('click', deselectAll);

    // ── Style panel → live update ─────────────────────────────────────────────
    function stylePanelChanged() {
        if (!state.selectedKey) return;
        const key = state.selectedKey;

        const fontSize       = parseInt(document.getElementById('sp-font-size').value)      || 12;
        const fontWeight     = document.getElementById('sp-font-weight').value;
        const color          = document.getElementById('sp-color').value;
        const width          = parseFloat(document.getElementById('sp-width').value)         || 30;
        const xPct           = parseFloat(document.getElementById('sp-x').value)             || 0;
        const yPct           = parseFloat(document.getElementById('sp-y').value)             || 0;
        const showLabel      = document.getElementById('sp-show-label').checked;
        const labelFontSize  = parseInt(document.getElementById('sp-label-font-size').value) || 9;
        const labelColor     = document.getElementById('sp-label-color').value;
        const outputType     = document.getElementById('sp-output-type').value;
        const align          = document.querySelector('.align-btn.active')?.dataset.align    || 'left';

        upsertField(key, {
            font_size:       fontSize,
            font_weight:     fontWeight,
            color,
            width_pct:       round2(width),
            x_pct:           round2(xPct),
            y_pct:           round2(yPct),
            show_label:      showLabel,
            label_font_size: labelFontSize,
            label_color:     labelColor,
            output_type:     outputType,
            align,
        });

        const field = getField(key);
        const box   = canvas.querySelector(`.bp-field-box[data-key="${key}"]`);
        if (box && field) updateBoxStyle(box, field);
    }

    [
        'sp-font-size', 'sp-font-weight', 'sp-color',
        'sp-width', 'sp-x', 'sp-y',
        'sp-show-label', 'sp-label-font-size', 'sp-label-color', 'sp-output-type',
    ].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', stylePanelChanged);
    });

    document.querySelectorAll('.align-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            setActiveAlignBtn(btn.dataset.align);
            stylePanelChanged();
        });
    });

    function setActiveAlignBtn(align) {
        document.querySelectorAll('.align-btn').forEach(b => b.classList.toggle('active', b.dataset.align === align));
    }

    // ── Zoom ──────────────────────────────────────────────────────────────────
    document.getElementById('select-zoom').addEventListener('change', (e) => {
        state.zoom = parseFloat(e.target.value);
        zoomWrap.style.transform = `scale(${state.zoom})`;
    });

    // ── Grid ──────────────────────────────────────────────────────────────────
    document.getElementById('btn-grid').addEventListener('click', () => {
        state.gridOn = !state.gridOn;
        canvas.style.backgroundImage = state.gridOn
            ? 'repeating-linear-gradient(0deg,transparent,transparent 39px,#ddd 39px,#ddd 40px),repeating-linear-gradient(90deg,transparent,transparent 39px,#ddd 39px,#ddd 40px)'
            : '';
        document.getElementById('btn-grid').classList.toggle('active', state.gridOn);
    });

    // ── Undo / Redo ───────────────────────────────────────────────────────────
    document.getElementById('btn-undo').addEventListener('click', undo);
    document.getElementById('btn-redo').addEventListener('click', redo);

    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && !e.shiftKey && e.key === 'z') { e.preventDefault(); undo(); }
        if ((e.ctrlKey || e.metaKey) && (e.key === 'y' || (e.shiftKey && e.key === 'z'))) { e.preventDefault(); redo(); }
    });

    // ── Save ──────────────────────────────────────────────────────────────────
    document.getElementById('btn-save').addEventListener('click', saveMapping);

    function buildFieldMapping() {
        const coreFields   = state.fields.filter(f => !f.key.startsWith('booking_pass_data.'));
        const customFields = state.fields.filter(f =>  f.key.startsWith('booking_pass_data.'));
        return { canvas: { width_px: CANVAS_W, height_px: CANVAS_H }, fields: coreFields, custom_fields: customFields };
    }

    async function saveMapping() {
        const btn = document.getElementById('btn-save');
        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan…';

        try {
            const res  = await fetch(SAVE_URL, {
                method:  'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
                body:    JSON.stringify({ field_mapping: buildFieldMapping() }),
            });
            const data = await res.json();
            showToast(data.success ? 'Layout tersimpan!' : 'Gagal menyimpan.', data.success ? 'success' : 'danger');
        } catch (err) {
            showToast('Error: ' + err.message, 'danger');
        } finally {
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-floppy me-1"></i> Simpan Layout';
        }
    }

    // ── Preview PDF ───────────────────────────────────────────────────────────
    document.getElementById('btn-preview').addEventListener('click', async () => {
        const btn          = document.getElementById('btn-preview');
        const reservSelect = document.getElementById('select-preview-reservation');
        const reservId     = reservSelect ? reservSelect.value : '';
        const label        = reservId ? reservSelect.options[reservSelect.selectedIndex].text : 'Dummy';

        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

        await saveMapping();

        try {
            const url  = reservId ? (PREVIEW_URL + '?reservation_id=' + encodeURIComponent(reservId)) : PREVIEW_URL;
            const res  = await fetch(url, { headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF } });
            const data = await res.json();
            if (data.url) {
                window.open(data.url + '?t=' + Date.now(), '_blank');
                showToast('Preview: ' + label, 'success');
            } else {
                showToast('Gagal generate preview.', 'danger');
            }
        } catch (err) {
            showToast('Error preview: ' + err.message, 'danger');
        } finally {
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-eye me-1"></i> Preview PDF';
        }
    });

    // ── Background upload ─────────────────────────────────────────────────────
    document.getElementById('bg-file-input').addEventListener('change', async (e) => {
        const file = e.target.files[0];
        if (!file) return;

        const label = document.getElementById('bg-upload-label');
        label.textContent = 'Mengupload…';

        const fd = new FormData();
        fd.append('template_file', file);
        fd.append('_token', CSRF);

        try {
            const res  = await fetch(UPLOAD_BG_URL, { method: 'POST', body: fd });
            const data = await res.json();
            if (data.success) {
                let img    = document.getElementById('bp-bg-img');
                const noMsg = document.getElementById('bp-no-bg-msg');
                if (noMsg) noMsg.remove();
                if (!img) {
                    img = document.createElement('img');
                    img.id = 'bp-bg-img';
                    img.alt = 'background';
                    img.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;object-fit:fill;pointer-events:none;z-index:0;';
                    canvas.insertBefore(img, canvas.firstChild);
                }
                img.src = data.url + '?t=' + Date.now();
                label.textContent = 'Ganti background';
                showToast('Background berhasil diupload.', 'success');
            } else {
                showToast('Upload gagal.', 'danger');
                label.textContent = 'Upload background (JPG/PNG)';
            }
        } catch (err) {
            showToast('Error upload: ' + err.message, 'danger');
            label.textContent = 'Upload background (JPG/PNG)';
        }
    });

    // ── Custom Field Modal ────────────────────────────────────────────────────
    document.getElementById('btn-add-custom-field').addEventListener('click', () => {
        document.getElementById('cf-label').value = '';
        document.getElementById('cf-key').value   = '';
        document.getElementById('cf-key-preview').textContent = '...';
        new bootstrap.Modal(document.getElementById('customFieldModal')).show();
    });

    document.getElementById('cf-label').addEventListener('input', (e) => {
        const slug = slugify(e.target.value);
        document.getElementById('cf-key').value = slug;
        document.getElementById('cf-key-preview').textContent = slug || '...';
    });

    document.getElementById('cf-key').addEventListener('input', (e) => {
        document.getElementById('cf-key-preview').textContent = e.target.value || '...';
    });

    document.getElementById('btn-confirm-custom-field').addEventListener('click', () => {
        const label = document.getElementById('cf-label').value.trim();
        const key   = 'booking_pass_data.' + (document.getElementById('cf-key').value.trim() || slugify(label));

        if (!label)                                          { alert('Label harus diisi.'); return; }
        if (state.customFields.some(f => f.key === key))    { alert('Key sudah ada.'); return; }
        if (state.fields.some(f => f.key === key))          { alert('Field ini sudah ada di canvas.'); return; }

        state.customFields.push({ key, label });
        renderCustomVarsSidebar();

        const newItem = customVarsList.querySelector(`.bp-var-item[data-key="${key}"]`);
        if (newItem) newItem.addEventListener('dragstart', onSidebarDragStart);

        bootstrap.Modal.getInstance(document.getElementById('customFieldModal')).hide();
    });

    // ── Sidebar drag init ─────────────────────────────────────────────────────
    document.querySelectorAll('#bp-sidebar-body .bp-var-item').forEach(el => {
        el.addEventListener('dragstart', onSidebarDragStart);
        el.addEventListener('dragend',   () => { state.draggingSidebar = null; });
    });

    // ── Toast ─────────────────────────────────────────────────────────────────
    function showToast(msg, type = 'success') {
        const container = document.getElementById('bp-toast');
        const id        = 'toast-' + Date.now();
        container.insertAdjacentHTML('beforeend', `
            <div id="${id}" class="toast align-items-center text-bg-${type} border-0 show" role="alert">
                <div class="d-flex">
                    <div class="toast-body">${msg}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            </div>
        `);
        setTimeout(() => { const el = document.getElementById(id); if (el) el.remove(); }, 3000);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────
    function clamp(val, min, max) { return Math.max(min, Math.min(max, val)); }
    function round2(n)            { return Math.round(n * 100) / 100; }
    function slugify(str)         {
        return str.toLowerCase().trim().replace(/[^a-z0-9]+/g, '_').replace(/^_|_$/g, '');
    }

    // ── Boot ──────────────────────────────────────────────────────────────────
    init();

})();
