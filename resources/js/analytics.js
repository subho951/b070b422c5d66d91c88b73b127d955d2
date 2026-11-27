const preferences = document.querySelector('[data-analytics-preferences]');

let sendAnalyticsEvent = () => {};

if (preferences) {
    const { measurementId, debug, url, token } = preferences.dataset;
    const status = preferences.querySelector('[data-analytics-status]');
    const buttons = [...preferences.querySelectorAll('[data-analytics-choice]')];
    let consent = preferences.dataset.consent;
    let leadSource = preferences.dataset.leadSource;
    let started = false;

    const showConsent = () => {
        status.textContent = {
            granted: 'Analytics allowed for this session.',
            denied: 'Analytics declined for this session.',
            unset: 'Analytics is off until you allow it.',
        }[consent];
        buttons.forEach(button => {
            button.disabled = button.dataset.analyticsChoice === consent;
        });
    };

    const startAnalytics = source => {
        if (started || consent !== 'granted' || !measurementId) return;

        // Leave a tag supplied by the hosting environment in control of its own configuration.
        if (window.gtag || document.querySelector('script[src*="googletagmanager.com/gtag/js"], script[src*="googletagmanager.com/gtm.js"]')) return;

        started = true;
        window['ga-disable-' + measurementId] = false;
        window.dataLayer = window.dataLayer || [];
        window.gtag = function () { window.dataLayer.push(arguments); };
        const tag = window.gtag;
        tag('consent', 'default', {
            analytics_storage: 'granted',
            ad_storage: 'denied',
            ad_user_data: 'denied',
            ad_personalization: 'denied',
        });
        tag('js', new Date());
        const pageLocation = new URL(location.href);
        pageLocation.search = '';
        pageLocation.hash = '';
        if (source === 'gbp') {
            pageLocation.search = 'utm_source=google&utm_medium=organic&utm_campaign=gbp';
        }
        tag('config', measurementId, {
            send_page_view: false,
            allow_google_signals: false,
            allow_ad_personalization_signals: false,
            cookie_domain: 'none',
            cookie_expires: 0,
            cookie_prefix: 'howvanta',
            page_location: pageLocation.href,
            page_referrer: '',
            ...(debug === 'true' ? { debug_mode: true } : {}),
        });

        sendAnalyticsEvent = event => {
            if (consent !== 'granted') return;
            tag('event', event, {
                send_to: measurementId,
                ...(leadSource ? { lead_source: leadSource } : {}),
            });
        };
        sendAnalyticsEvent('page_view');

        const conversion = document.querySelector('[data-analytics-conversion]')?.dataset.analyticsConversion;
        if (conversion) {
            sendAnalyticsEvent('contact_form_submit');
            sendAnalyticsEvent('generate_lead');
            if (conversion === 'consultation') sendAnalyticsEvent('consultation_request');
        }

        const script = document.createElement('script');
        script.async = true;
        script.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(measurementId);
        script.referrerPolicy = 'no-referrer';
        document.head.append(script);
    };

    buttons.forEach(button => button.addEventListener('click', async () => {
        const allowed = button.dataset.analyticsChoice === 'granted';
        buttons.forEach(choice => { choice.disabled = true; });

        try {
            const campaign = {};
            let referrer = null;
            if (allowed) {
                const query = new URLSearchParams(location.search);
                for (const key of ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'gbraid', 'wbraid']) {
                    const value = query.get(key);
                    if (value && value.length <= 255) campaign[key] = value;
                }
                if (document.referrer) referrer = new URL(document.referrer).origin;
            }
            const response = await fetch(url, {
                method: 'POST',
                headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                body: JSON.stringify({ allowed, campaign, referrer }),
                referrerPolicy: 'no-referrer',
            });
            if (!response.ok) throw new Error('Consent could not be saved');

            const { landing_source: landingSource, lead_source: updatedSource } = await response.json();
            leadSource = updatedSource;
            consent = allowed ? 'granted' : 'denied';
            if (allowed && started) {
                window['ga-disable-' + measurementId] = false;
                window.gtag('consent', 'update', { analytics_storage: 'granted' });
            } else if (allowed) {
                startAnalytics(landingSource);
            } else if (started || measurementId) {
                window['ga-disable-' + measurementId] = true;
                for (const cookie of document.cookie.split(';')) {
                    const name = cookie.split('=')[0].trim();
                    if (/^howvanta_ga(_[A-Z0-9]+)?$/i.test(name)) {
                        document.cookie = name + '=; Max-Age=0; path=/';
                    }
                }
            }
            showConsent();
        } catch {
            showConsent();
            status.textContent = 'Analytics preferences could not be saved. Please try again.';
        }
    }));

    showConsent();
    preferences.hidden = false;
    startAnalytics(preferences.dataset.landingSource);
}

export function trackContactAnalytics(action) {
    const event = { whatsapp: 'whatsapp_click', phone: 'phone_click', email: 'email_click' }[action];
    if (event) sendAnalyticsEvent(event);
}
