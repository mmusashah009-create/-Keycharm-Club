(() => {
    const editor = document.querySelector('[data-variant-editor]');
    if (!editor) return;

    const rows = editor.querySelector('[data-variant-rows]');
    const addButton = editor.querySelector('[data-add-variant]');
    const paletteTemplate = document.querySelector('[data-color-palette-template]');
    const mainImage = document.querySelector('input[name="image"]');
    const productId = document.querySelector('input[name="id"]').value;
    const maximumVariants = 8;

    const wirePalette = (row) => {
        const palette = row.querySelector('[data-color-palette]');
        const colorInput = row.querySelector('input[type="color"]');
        const nameInput = row.querySelector('input[name$="[color_name]"]');
        if (!palette || !colorInput || !nameInput) return;
        if (palette.dataset.ready === 'true') return;

        palette.replaceChildren(paletteTemplate.content.cloneNode(true));
        palette.dataset.ready = 'true';
        const initialName = nameInput.value.trim();
        const matchingInitialChoice = [...palette.querySelectorAll('[data-palette-name]')]
            .find((button) => button.dataset.paletteName.toLowerCase() === initialName.toLowerCase()
                && button.dataset.paletteHex.toUpperCase() === colorInput.value.toUpperCase());
        nameInput.dataset.paletteName = matchingInitialChoice?.dataset.paletteName ?? '';

        palette.querySelectorAll('[data-palette-name]').forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.paletteHex.toUpperCase() === colorInput.value.toUpperCase()));
            button.addEventListener('click', () => {
                const previousPaletteName = nameInput.dataset.paletteName || '';
                colorInput.value = button.dataset.paletteHex;
                button.parentElement.querySelectorAll('[data-palette-name]').forEach((option) => {
                    option.setAttribute('aria-pressed', String(option === button));
                });
                if (nameInput.value.trim() === '' || nameInput.value.trim() === previousPaletteName) {
                    nameInput.value = button.dataset.paletteName;
                }
                nameInput.dataset.paletteName = button.dataset.paletteName;
            });
        });

        colorInput.addEventListener('input', () => {
            const selectedHex = colorInput.value.toUpperCase();
            const matchingButton = [...palette.querySelectorAll('[data-palette-name]')]
                .find((button) => button.dataset.paletteHex.toUpperCase() === selectedHex);
            palette.querySelectorAll('[data-palette-name]').forEach((button) => {
                button.setAttribute('aria-pressed', String(button === matchingButton));
            });
            if (matchingButton && (nameInput.value.trim() === '' || nameInput.value.trim() === nameInput.dataset.paletteName)) {
                nameInput.value = matchingButton.dataset.paletteName;
                nameInput.dataset.paletteName = matchingButton.dataset.paletteName;
            } else if (!matchingButton && nameInput.value.trim() === nameInput.dataset.paletteName) {
                nameInput.value = '';
                nameInput.dataset.paletteName = '';
            }
        });

        nameInput.addEventListener('input', () => {
            if (nameInput.value.trim() !== nameInput.dataset.paletteName) {
                nameInput.dataset.paletteName = '';
            }
        });
    };

    const renumberRows = () => {
        [...rows.querySelectorAll('[data-variant-row]')].forEach((row, index) => {
            row.querySelector('legend').textContent = `Color option ${index + 1}`;
            row.querySelector('input[name$="[id]"]').name = `variants[${index}][id]`;
            row.querySelector('input[name$="[color_name]"]').name = `variants[${index}][color_name]`;
            row.querySelector('input[name$="[color_hex]"]').name = `variants[${index}][color_hex]`;
            row.querySelector('input[type="file"]').name = `variant_images[${index}]`;
            wirePalette(row);
        });
        addButton.disabled = rows.children.length >= maximumVariants;
        mainImage.required = productId === '' && rows.children.length === 0;
    };

    addButton.addEventListener('click', () => {
        if (rows.children.length >= maximumVariants) return;
        const row = document.createElement('fieldset');
        row.className = 'variant-row';
        row.dataset.variantRow = '';
        row.innerHTML = `
            <legend></legend>
            <input type="hidden" name="variants[0][id]" value="0">
            <div class="admin-form-row">
                <label>Color name<input name="variants[0][color_name]" maxlength="50" placeholder="Choose a color below or type a name" required></label>
                <label>Custom color<input name="variants[0][color_hex]" type="color" value="#ED3D95" required><span>Pick any shade not shown below.</span></label>
            </div>
            <div class="color-palette-wrap"><p>Quick color palette</p><div class="color-palette" data-color-palette role="group" aria-label="Quick color choices"></div></div>
            <label>Photo for this color<input name="variant_images[0]" type="file" accept="image/jpeg,image/png,image/webp" capture="environment" required><span>Take a photo or choose one from your device. Required for each new color.</span></label>
            <button class="variant-remove" type="button" data-remove-variant>Remove this color</button>
        `;
        rows.append(row);
        renumberRows();
        row.querySelector('input[name$="[color_name]"]').focus();
    });

    rows.addEventListener('click', (event) => {
        const removeButton = event.target.closest('[data-remove-variant]');
        if (!removeButton) return;
        removeButton.closest('[data-variant-row]').remove();
        renumberRows();
    });

    renumberRows();
})();
