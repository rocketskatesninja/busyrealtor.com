// The six-step tenant setup wizard. Registered as an Alpine component rather than a
// global function so the markup only names it, and its starting values arrive as JSON
// on the wrapper element instead of being interpolated into a script block.

import Alpine from 'alpinejs';

function config() {
    const el = document.getElementById('setup-wizard');

    try {
        return JSON.parse(el?.dataset.wizard || '{}');
    } catch (e) {
        return {};
    }
}

document.addEventListener('alpine:init', () => {
    Alpine.data('setupWizard', () => {
        const cfg = config();

        return {
            step: 1,
            saving: false,
            errorMsg: '',
            completedSteps: [],
            data: cfg.data || {},

            async saveStep() {
                this.saving = true;
                this.errorMsg = '';

                try {
                    let body;
                    const headers = { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Accept': 'application/json' };

                    if (this.step === 3 || this.step === 5) {
                        // Use FormData for file uploads
                        body = new FormData();
                        body.append('step', this.step);

                        if (this.step === 3) {
                            ['hero_title','hero_subtitle','hero_background_type','hero_preset','hero_gradient_start','hero_gradient_end'].forEach(k => body.append(k, this.data[k] || ''));
                            const heroFile = this.$refs.heroImageInput?.files?.[0];
                            if (heroFile) body.append('hero_image', heroFile);
                        }

                        if (this.step === 5) {
                            if (!this.data.prop_title) {
                                // Skip — no property to add
                                this.step++;
                                this.saving = false;
                                window.scrollTo({top: 0, behavior: 'smooth'});
                                return;
                            }
                            body.append('title', this.data.prop_title);
                            body.append('property_type', this.data.prop_type || 'house');
                            body.append('price', this.data.prop_price);
                            body.append('address', this.data.prop_address);
                            body.append('city', this.data.prop_city);
                            body.append('state', this.data.prop_state);
                            body.append('zip', this.data.prop_zip);
                            body.append('bedrooms', this.data.prop_bedrooms);
                            body.append('bathrooms', this.data.prop_bathrooms);
                            body.append('sqft', this.data.prop_sqft);
                            const propFile = this.$refs.propImageInput?.files?.[0];
                            if (propFile) body.append('images[]', propFile);
                        }
                    } else {
                        headers['Content-Type'] = 'application/json';
                        let payload = { step: this.step };

                        if (this.step === 1) {
                            Object.assign(payload, {
                                favicon_preset: this.data.favicon_preset,
                                primary_color: this.data.primary_color,
                                header_display_mode: this.data.header_display_mode,
                            });
                        } else if (this.step === 2) {
                            Object.assign(payload, {
                                owner_name: this.data.owner_name,
                                contact_email: this.data.contact_email,
                                contact_phone: this.data.contact_phone,
                                contact_address: this.data.contact_address,
                                license_number: this.data.license_number,
                                brokerage_name: this.data.brokerage_name,
                            });
                        } else if (this.step === 4) {
                            Object.assign(payload, {
                                ai_preferred: this.data.ai_preferred,
                                ai_enabled: this.data.ai_enabled ? 1 : 0,
                                ai_anthropic_key: this.data.ai_anthropic_key,
                                ai_anthropic_model: this.data.ai_anthropic_model,
                                ai_openai_key: this.data.ai_openai_key,
                                ai_openai_model: this.data.ai_openai_model,
                                ga_measurement_id: this.data.ga_measurement_id,
                                ga_enabled: this.data.ga_enabled,
                                fb_access_token: this.data.fb_access_token,
                                fb_page_id: this.data.fb_page_id,
                                fb_enabled: this.data.fb_enabled,
                                tw_api_key: this.data.tw_api_key,
                                tw_api_secret: this.data.tw_api_secret,
                                tw_access_token: this.data.tw_access_token,
                                tw_access_token_secret: this.data.tw_access_token_secret,
                                tw_enabled: this.data.tw_enabled,
                            });
                        }
                        body = JSON.stringify(payload);
                    }

                    const resp = await fetch(cfg.saveUrl, { method: 'POST', headers, body });

                    if (!resp.ok) {
                        const err = await resp.json().catch(() => null);
                        throw new Error(err?.message || Object.values(err?.errors || {}).flat().join(' ') || 'Save failed');
                    }

                    if (!this.completedSteps.includes(this.step)) {
                        this.completedSteps.push(this.step);
                    }
                    this.step++;
                    window.scrollTo({top: 0, behavior: 'smooth'});
                } catch (e) {
                    this.errorMsg = e.message;
                } finally {
                    this.saving = false;
                }
            },

            async launch() {
                this.saving = true;
                this.errorMsg = '';
                try {
                    const resp = await fetch(cfg.saveUrl, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                        },
                        body: JSON.stringify({ step: 'complete' }),
                    });
                    const result = await resp.json();
                    if (result.redirect) {
                        window.location.href = result.redirect;
                    }
                } catch (e) {
                    this.errorMsg = e.message;
                    this.saving = false;
                }
            },
        };
    });
});
