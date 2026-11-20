const toggle = document.querySelector('.menu-toggle');
toggle?.addEventListener('click', () => {
    const expanded = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', String(!expanded));
    document.querySelector('#main-nav')?.classList.toggle('is-open', !expanded);
});
document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && toggle?.getAttribute('aria-expanded') === 'true') {
        toggle.click();
        toggle.focus();
    }
});

const country = document.querySelector('#country');
const budget = document.querySelector('#budget[data-budget-ranges]');
if (country && budget) {
    const ranges = JSON.parse(budget.dataset.budgetRanges);
    country.addEventListener('change', () => {
        const currency = country.selectedOptions[0]?.dataset.currency;
        // Reset the selected range so a country change never silently changes its meaning.
        budget.replaceChildren(new Option(currency ? 'Select an option' : 'Select a country first', ''));
        budget.disabled = !currency;
        document.querySelector('#budget-currency').textContent = currency || '';
        if (currency) {
            Object.entries(ranges).forEach(([value, label]) => {
                budget.add(new Option(label.replace('{currency}', currency), value));
            });
        }
    });
}

const siteKey = document.querySelector('meta[name="recaptcha-site-key"]')?.content;
const pending = new WeakSet();
const verified = new WeakSet();
const executeRecaptcha = action => new Promise((resolve, reject) => {
    let poll;
    let deadline;
    const finish = (callback, value) => {
        window.clearTimeout(deadline);
        window.clearInterval(poll);
        callback(value);
    };
    deadline = window.setTimeout(() => finish(reject, new Error('Verification timed out')), 12000);
    const check = () => {
        if (!window.grecaptcha?.ready) {
            return;
        }
        window.clearInterval(poll);
        window.grecaptcha.ready(() => {
            window.grecaptcha.execute(siteKey, { action }).then(
                token => finish(resolve, token),
                error => finish(reject, error),
            );
        });
    };
    poll = window.setInterval(check, 100);
    check();
});

document.addEventListener('submit', async event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement) || event.defaultPrevented) {
        return;
    }
    if (verified.has(form)) {
        verified.delete(form);
        return;
    }
    if (pending.has(form)) {
        event.preventDefault();
        return;
    }
    if (form.dataset.confirm && !window.confirm(form.dataset.confirm)) {
        event.preventDefault();
        return;
    }
    const destination = new URL(form.action, window.location.href);
    if (!siteKey || form.hasAttribute('data-recaptcha-exempt') || form.method.toLowerCase() === 'get' || destination.origin !== window.location.origin) {
        return;
    }
    event.preventDefault();
    pending.add(form);
    const submitter = event.submitter;
    const buttons = [...form.querySelectorAll('button[type="submit"], button:not([type]), input[type="submit"]')];
    const originalDisabled = buttons.map(button => button.disabled);
    buttons.forEach(button => { button.disabled = true; });
    let error = form.querySelector('[data-recaptcha-error]');
    if (!error) {
        error = document.createElement('p');
        error.dataset.recaptchaError = '';
        error.className = 'error-message';
        error.setAttribute('role', 'alert');
        form.append(error);
    }
    error.hidden = true;
    const path = destination.pathname.replace(/\/$/, '');
    const action = /\/contact$/.test(path) ? 'contact' : /\/login$/.test(path) ? 'login' : /\/logout$/.test(path) ? 'logout' : 'admin';
    try {
        const token = await executeRecaptcha(action);
        if (!token) {
            throw new Error('Missing verification token');
        }
        let input = form.querySelector('input[name="g-recaptcha-response"]');
        if (!input) {
            input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'g-recaptcha-response';
            form.append(input);
        }
        input.value = token;
        verified.add(form);
        buttons.forEach((button, index) => { button.disabled = originalDisabled[index]; });
        form.requestSubmit(submitter || undefined);
        verified.delete(form);
    } catch {
        error.textContent = 'Spam verification could not be completed. Please reload the page and try again.';
        error.hidden = false;
    } finally {
        pending.delete(form);
        buttons.forEach((button, index) => { button.disabled = originalDisabled[index]; });
    }
});

// Background assessment gives Google context; submitted forms always receive a fresh token.
if (siteKey) {
    executeRecaptcha('page_view').catch(() => {});
}


document.querySelectorAll('[data-password-toggle]').forEach(button => {
    const input = document.getElementById(button.getAttribute('aria-controls'));
    if (!(input instanceof HTMLInputElement)) {
        return;
    }
    button.hidden = false;
    button.addEventListener('click', () => {
        const showPassword = input.type === 'password';
        input.type = showPassword ? 'text' : 'password';
        button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
        button.setAttribute('aria-pressed', String(showPassword));
    });
});


document.querySelectorAll('[data-repeater]').forEach(repeater => {
    const items = repeater.querySelector('[data-repeater-items]');
    repeater.addEventListener('click', event => {
        const button = event.target.closest('button');
        if (!button) {
            return;
        }
        const item = button.closest('[data-repeater-item]');
        if (button.hasAttribute('data-item-add')) {
            if (items.children.length >= 40) {
                return;
            }
            const index = Number(repeater.dataset.nextIndex);
            repeater.dataset.nextIndex = String(index + 1);
            const template = repeater.querySelector('[data-repeater-template]');
            items.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__INDEX__', String(index)));
            items.lastElementChild.querySelector('input')?.focus();
        } else if (button.hasAttribute('data-item-remove') && item) {
            item.remove();
        } else if (button.hasAttribute('data-item-up') && item?.previousElementSibling) {
            items.insertBefore(item, item.previousElementSibling);
        } else if (button.hasAttribute('data-item-down') && item?.nextElementSibling) {
            items.insertBefore(item.nextElementSibling, item);
        }
    });
});


const invoiceItems = document.querySelector('[data-invoice-items]');
if (invoiceItems) {
    let nextIndex = Math.max(...Array.from(invoiceItems.querySelectorAll('input[name]')).map(input => Number(input.name.match(/items\[(\d+)\]/)?.[1] ?? 0))) + 1;
    document.querySelector('[data-invoice-add]')?.addEventListener('click', () => {
        const row = invoiceItems.querySelector('[data-invoice-row]').cloneNode(true);
        row.querySelectorAll('input').forEach(input => {
            input.name = input.name.replace(/items\[\d+\]/, `items[${nextIndex}]`);
            input.id = input.id.replace(/item-\d+-/, `item-${nextIndex}-`);
            input.value = input.name.includes('[quantity]') ? '1' : (input.type === 'number' ? '0' : '');
        });
        row.querySelectorAll('label').forEach(label => {
            label.htmlFor = label.htmlFor.replace(/item-\d+-/, `item-${nextIndex}-`);
        });
        nextIndex += 1;
        invoiceItems.append(row);
        row.querySelector('input').focus();
    });
    invoiceItems.addEventListener('click', event => {
        if (event.target.closest('[data-invoice-remove]') && invoiceItems.children.length > 1) {
            event.target.closest('[data-invoice-row]').remove();
        }
    });
}
document.querySelector('[data-print-invoice]')?.addEventListener('click', () => window.print());

document.querySelector('[data-invoice-company]')?.addEventListener('change', event => {
    const details = event.target.selectedOptions[0]?.dataset.company;
    if (!details) return;
    const company = JSON.parse(details);
    Object.entries({ seller_name: 'name', seller_address: 'address', seller_email: 'email', seller_tax_identifier: 'tax_identifier' }).forEach(([field, source]) => {
        document.getElementById(field).value = company[source] || '';
    });
});
document.querySelector('[data-invoice-bank]')?.addEventListener('change', event => {
    const details = event.target.selectedOptions[0]?.dataset.bank;
    if (!details) return;
    const bank = JSON.parse(details);
    document.getElementById('payment_terms').value = [bank.name, `Account holder: ${bank.account_holder}`, `Account number: ${bank.account_number}`, bank.branch, bank.ifsc_swift, bank.notes].filter(Boolean).join('\n');
});


const showImageFallback = image => {
    const fallback = image.parentElement.querySelector('.media-image-fallback');
    image.hidden = true;
    if (fallback) fallback.hidden = false;
};
document.querySelectorAll('[data-image-fallback], [data-media-field-image]').forEach(image => {
    image.addEventListener('error', () => showImageFallback(image));
    if (image.complete && image.getAttribute('src') && !image.naturalWidth) showImageFallback(image);
});

document.querySelectorAll('input[type="file"][accept="image/*"]').forEach(input => {
    const validateImages = () => {
        const files = [...input.files];
        const message = files.length > 20 ? 'Select up to 20 images at a time.' : files.some(file => file.size > 8 * 1024 * 1024) ? 'Each image must be 8 MB or smaller.' : '';
        input.setCustomValidity(message);
        if (message) input.reportValidity();
    };
    input.addEventListener('change', validateImages);
});

const bulkSelection = [...document.querySelectorAll('[data-media-select]')];
const selectAllMedia = document.querySelector('[data-media-select-all]');
const updateBulkSelection = () => {
    const count = bulkSelection.filter(input => input.checked).length;
    document.querySelector('[data-media-selected-count]').textContent = `${count} selected`;
    document.querySelector('[data-media-delete-selected]').disabled = count === 0;
    selectAllMedia.checked = count > 0 && count === bulkSelection.length;
    selectAllMedia.indeterminate = count > 0 && count < bulkSelection.length;
    selectAllMedia.disabled = bulkSelection.length === 0;
    bulkSelection.forEach(input => input.closest('.media-library-card').classList.toggle('is-selected', input.checked));
};
if (selectAllMedia) {
    selectAllMedia.addEventListener('change', () => {
        bulkSelection.forEach(input => { input.checked = selectAllMedia.checked; });
        updateBulkSelection();
    });
    bulkSelection.forEach(input => input.addEventListener('change', updateBulkSelection));
    updateBulkSelection();
}

const mediaDialog = document.querySelector('[data-media-dialog]');
if (mediaDialog) {
    const grid = mediaDialog.querySelector('[data-media-library]');
    const errorBox = mediaDialog.querySelector('[data-media-error]');
    const status = mediaDialog.querySelector('[data-media-status]');
    const search = mediaDialog.querySelector('[data-media-search]');
    const useButton = mediaDialog.querySelector('[data-media-use]');
    const previous = mediaDialog.querySelector('[data-media-prev]');
    const next = mediaDialog.querySelector('[data-media-next]');
    const uploadForm = mediaDialog.querySelector('[data-media-upload-form]');
    let targetField;
    let trigger;
    let selected;
    let page = 1;
    let lastPage = 1;
    let request;
    let debounce;

    const showError = message => {
        errorBox.textContent = message;
        errorBox.hidden = !message;
    };
    const updateSelection = () => {
        useButton.disabled = !selected;
        mediaDialog.querySelector('[data-media-selected-label]').textContent = selected ? selected.name : 'No image selected';
        grid.querySelectorAll('[data-media-choice]').forEach(button => {
            const active = Number(button.dataset.mediaChoice) === Number(selected?.id);
            button.setAttribute('aria-pressed', String(active));
        });
    };
    const switchTab = name => {
        mediaDialog.querySelectorAll('[data-media-tab]').forEach(tab => {
            const active = tab.dataset.mediaTab === name;
            tab.setAttribute('aria-selected', String(active));
            tab.tabIndex = active ? 0 : -1;
            document.getElementById(tab.getAttribute('aria-controls')).hidden = !active;
        });
    };
    const responseData = async response => {
        const data = await response.json().catch(() => null);
        if (!response.ok || !data) {
            const messages = Object.values(data?.errors || {}).flat();
            throw new Error(messages.length ? messages.join(' ') : 'Unable to complete this request. Please refresh the page and try again.');
        }
        return data;
    };
    const loadLibrary = async (requestedPage = 1) => {
        request?.abort();
        request = new AbortController();
        const currentRequest = request;
        const url = new URL(mediaDialog.dataset.libraryUrl, window.location.href);
        url.searchParams.set('page', requestedPage);
        url.searchParams.set('search', search.value);
        previous.disabled = next.disabled = true;
        grid.replaceChildren();
        status.textContent = 'Loading images...';
        grid.setAttribute('aria-busy', 'true');
        showError('');
        try {
            const response = await fetch(url, { headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, signal: currentRequest.signal });
            const data = await responseData(response);
            if (currentRequest !== request) return;
            page = data.current_page;
            lastPage = data.last_page;
            data.data.forEach(asset => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = 'media-choice';
                button.dataset.mediaChoice = asset.id;
                button.setAttribute('aria-label', `Select ${asset.name}`);
                const thumbnail = document.createElement('span');
                thumbnail.className = 'media-choice-thumbnail';
                const image = document.createElement('img');
                image.src = asset.url;
                image.alt = asset.alt_text || '';
                image.loading = 'lazy';
                const fallback = document.createElement('span');
                fallback.className = 'media-image-fallback';
                fallback.textContent = `Preview unavailable \u00b7 ${asset.mime_type}`;
                fallback.hidden = true;
                thumbnail.append(image, fallback);
                image.addEventListener('error', () => showImageFallback(image));
                const name = document.createElement('strong');
                name.textContent = asset.name;
                const detail = document.createElement('small');
                detail.textContent = `${asset.width && asset.height ? `${asset.width} \u00d7 ${asset.height} \u00b7 ` : ''}${Math.ceil(asset.size_bytes / 1024)} KB`;
                button.append(thumbnail, name, detail);
                button.addEventListener('click', () => { selected = asset; updateSelection(); });
                grid.append(button);
            });
            status.textContent = data.total ? `${data.total} image${data.total === 1 ? '' : 's'} in your library` : 'No images found. Try another search or upload a new image.';
            mediaDialog.querySelector('[data-media-page]').textContent = `Page ${page} of ${lastPage}`;
            previous.disabled = page <= 1;
            next.disabled = page >= lastPage;
            updateSelection();
        } catch (error) {
            if (error.name !== 'AbortError') {
                showError(error.message);
                status.textContent = 'The media library could not be loaded.';
            }
        } finally {
            if (currentRequest === request) grid.setAttribute('aria-busy', 'false');
        }
    };
    const applyAsset = (field, asset) => {
        const input = field.querySelector('[data-media-value]');
        const image = field.querySelector('[data-media-field-image]');
        input.value = asset?.id || '';
        field.querySelector('[data-media-field-name]').textContent = asset?.name || 'No image selected';
        field.querySelector('[data-media-field-thumbnail]').hidden = !asset;
        field.querySelector('[data-media-clear]').hidden = !asset;
        const choose = field.querySelector('[data-media-open]');
        choose.lastChild.textContent = asset ? 'Change image' : 'Choose image';
        image.parentElement.querySelector('.media-image-fallback').hidden = true;
        image.hidden = false;
        image.alt = asset?.alt_text || '';
        if (asset) image.src = asset.url;
        else image.removeAttribute('src');
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };
    document.addEventListener('click', event => {
        const open = event.target.closest('[data-media-open]');
        const clear = event.target.closest('[data-media-clear]');
        if (clear) applyAsset(clear.closest('[data-media-field]'), null);
        if (!open) return;
        trigger = open;
        targetField = open.closest('[data-media-field]');
        const input = targetField.querySelector('[data-media-value]');
        selected = input.value ? { id: Number(input.value), name: targetField.querySelector('[data-media-field-name]').textContent, url: targetField.querySelector('[data-media-field-image]').src, alt_text: targetField.querySelector('[data-media-field-image]').alt } : null;
        search.value = '';
        switchTab('library');
        updateSelection();
        mediaDialog.showModal();
        document.body.classList.add('media-dialog-open');
        search.focus();
        loadLibrary();
    });
    mediaDialog.querySelectorAll('[data-media-close]').forEach(button => button.addEventListener('click', () => mediaDialog.close()));
    mediaDialog.addEventListener('close', () => { request?.abort(); clearTimeout(debounce); document.body.classList.remove('media-dialog-open'); trigger?.focus(); });
    useButton.addEventListener('click', () => {
        if (!selected || !targetField) return;
        applyAsset(targetField, selected);
        mediaDialog.close();
    });
    mediaDialog.querySelectorAll('[data-media-tab]').forEach(tab => {
        tab.addEventListener('click', () => switchTab(tab.dataset.mediaTab));
        tab.addEventListener('keydown', event => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const name = event.key === 'Home' ? 'library' : event.key === 'End' ? 'upload' : tab.dataset.mediaTab === 'library' ? 'upload' : 'library';
            switchTab(name);
            mediaDialog.querySelector(`[data-media-tab="${name}"]`).focus();
        });
    });
    search.addEventListener('input', () => { clearTimeout(debounce); debounce = setTimeout(() => loadLibrary(), 250); });
    previous.addEventListener('click', () => loadLibrary(page - 1));
    next.addEventListener('click', () => loadLibrary(page + 1));
    uploadForm.addEventListener('submit', async event => {
        event.preventDefault();
        event.stopPropagation();
        const button = mediaDialog.querySelector('[data-media-upload-button]');
        if (button.disabled) return;
        const uploadStatus = mediaDialog.querySelector('[data-media-upload-status]');
        button.disabled = true;
        button.textContent = 'Uploading...';
        uploadStatus.textContent = 'Uploading your images. Please wait.';
        showError('');
        try {
            const payload = new FormData(uploadForm);
            if (siteKey) payload.set('g-recaptcha-response', await executeRecaptcha('admin'));
            const response = await fetch(mediaDialog.dataset.uploadUrl, { method: 'POST', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }, body: payload });
            const data = await responseData(response);
            uploadForm.reset();
            selected = data.data[0];
            uploadStatus.textContent = `${data.data.length} image(s) uploaded.`;
            if (mediaDialog.open) {
                search.value = '';
                switchTab('library');
                await loadLibrary();
                updateSelection();
            }
        } catch (error) {
            showError(error.message);
            uploadStatus.textContent = 'Upload failed. Please review the message above.';
        } finally {
            button.disabled = false;
            button.textContent = 'Upload images';
        }
    });
}

const contactClickUrl = document.querySelector('meta[name="contact-click-url"]')?.content;
const contactClickToken = document.querySelector('meta[name="contact-click-token"]')?.content;

if (contactClickUrl && contactClickToken) {
    const recordAction = action => {
        fetch(contactClickUrl, {
            method: 'POST',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': contactClickToken },
            body: new URLSearchParams({ action }),
            keepalive: true,
            referrerPolicy: 'no-referrer',
        }).catch(() => {});
    };

    document.addEventListener('click', event => {
        const link = event.target.closest?.('a[href]');
        if (!link) return;

        const action = link.dataset.contactAction ?? { 'mailto:': 'email', 'tel:': 'phone' }[link.protocol];
        if (action) recordAction(action);
    });

    document.querySelector('[data-enquiry-form]')?.addEventListener('input', () => recordAction('form_start'), { once: true });
}

const enquiryIntent = document.querySelector('#intent');
const enquiryService = document.querySelector('#project_type');
enquiryIntent?.addEventListener('change', () => {
    enquiryService.required = enquiryIntent.value === 'quote';
});

const countrySearch = document.querySelector('#country-search');
if (countrySearch && country) {
    const countryOptions = [...country.options].map(option => option.cloneNode(true));
    countrySearch.hidden = false;
    countrySearch.addEventListener('input', () => {
        const query = countrySearch.value.trim().toLocaleLowerCase();
        const selected = country.value;
        const matches = countryOptions.filter(option => option.value === '' || option.value === selected || option.text.toLocaleLowerCase().includes(query));
        country.replaceChildren(...matches.map(option => option.cloneNode(true)));
        country.value = selected;
    });
}

const locationMap = document.querySelector('[data-location-map]');
locationMap?.addEventListener('toggle', () => {
    if (!locationMap.open) return;
    const frame = locationMap.querySelector('iframe[data-src]');
    if (!frame.hasAttribute('src')) frame.src = frame.dataset.src;
});

const heroCarousel = document.querySelector('[data-hero-carousel]');
if (heroCarousel) {
    const slides = [...heroCarousel.querySelectorAll('[data-hero-slide]')];
    const selectors = [...heroCarousel.querySelectorAll('[data-hero-select]')];
    const controls = heroCarousel.querySelector('[data-hero-controls]');
    const status = heroCarousel.querySelector('[data-hero-status]');
    let current = 0;
    let touchStart = null;

    const showSlide = index => {
        const next = (index + slides.length) % slides.length;
        if (next === current) return;

        current = next;
        slides.forEach((slide, position) => {
            const active = current === position;
            slide.setAttribute('aria-hidden', String(!active));
            slide.inert = !active;
            selectors[position].setAttribute('aria-pressed', String(active));
        });
        status.textContent = selectors[current].textContent.trim();
    };

    selectors.forEach((button, index) => button.addEventListener('click', () => showSlide(index)));
    heroCarousel.querySelector('[data-hero-previous]').addEventListener('click', () => showSlide(current - 1));
    heroCarousel.querySelector('[data-hero-next]').addEventListener('click', () => showSlide(current + 1));
    controls.addEventListener('keydown', event => {
        if (event.key !== 'ArrowLeft' && event.key !== 'ArrowRight') return;
        event.preventDefault();
        const fromSelector = selectors.includes(event.target);
        showSlide(current + (event.key === 'ArrowRight' ? 1 : -1));
        if (fromSelector) selectors[current].focus();
    });

    const stage = heroCarousel.querySelector('.home-hero-stage');
    stage.addEventListener('pointerdown', event => {
        if (event.pointerType === 'touch') touchStart = { x: event.clientX, y: event.clientY };
    });
    stage.addEventListener('pointercancel', () => { touchStart = null; });
    stage.addEventListener('pointerup', event => {
        if (event.pointerType !== 'touch' || !touchStart) return;
        const horizontal = event.clientX - touchStart.x;
        const vertical = event.clientY - touchStart.y;
        touchStart = null;
        if (Math.abs(horizontal) > 55 && Math.abs(horizontal) > Math.abs(vertical) * 1.5) {
            showSlide(current + (horizontal < 0 ? 1 : -1));
        }
    });

    controls.hidden = false;
}

const testimonialSlider = document.querySelector('[data-testimonial-slider]');
const testimonialControls = testimonialSlider?.querySelector('[data-testimonial-controls]');
if (testimonialControls) {
    const track = testimonialSlider.querySelector('[data-testimonial-track]');
    const slides = [...track.children];
    const counter = testimonialControls.querySelector('[data-testimonial-index]');
    const slideOffset = slide => slide.offsetLeft - slides[0].offsetLeft;
    const positions = () => {
        const lastOffset = track.scrollWidth - track.clientWidth;
        return slides.filter(slide => slideOffset(slide) <= lastOffset + 1);
    };

    const closestPosition = available => available.reduce((closest, slide) =>
        Math.abs(track.scrollLeft - slideOffset(slide)) < Math.abs(track.scrollLeft - slideOffset(closest))
            ? slide
            : closest,
    );

    const updateCounter = () => {
        const available = positions();
        const first = closestPosition(available);
        const start = slides.indexOf(first) + 1;
        const edge = slideOffset(first) + track.clientWidth + 1;
        const end = slides.filter(slide => slideOffset(slide) + slide.offsetWidth <= edge).length;
        const label = start === end ? `${start} / ${slides.length}` : `${start}–${end} / ${slides.length}`;
        if (counter.textContent.trim() !== label) counter.textContent = label;
        testimonialControls.hidden = available.length < 2;
    };

    const move = direction => {
        const available = positions();
        const current = available.indexOf(closestPosition(available));
        const target = (current + direction + available.length) % available.length;
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        track.scrollTo({ left: slideOffset(available[target]), behavior: reduceMotion ? 'instant' : 'smooth' });
    };

    testimonialControls.querySelector('[data-testimonial-prev]').addEventListener('click', () => move(-1));
    testimonialControls.querySelector('[data-testimonial-next]').addEventListener('click', () => move(1));
    track.addEventListener('scroll', updateCounter, { passive: true });
    track.addEventListener('keydown', event => {
        if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
            event.preventDefault();
            move(event.key === 'ArrowRight' ? 1 : -1);
        }
    });
    testimonialSlider.setAttribute('data-testimonial-enhanced', '');
    new ResizeObserver(updateCounter).observe(track);
    updateCounter();
}
