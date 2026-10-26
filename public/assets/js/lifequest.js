(function () {
    'use strict';

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    function showToast(message, variant) {
        if (!message) {
            return;
        }

        if (typeof bootstrap === 'undefined' || !bootstrap.Toast) {
            window.alert(message);
            return;
        }

        var container = document.getElementById('lq-toast-container');
        if (!container) {
            return;
        }

        var toastElement = document.createElement('div');
        toastElement.className = 'toast align-items-center border-0 text-bg-' + (variant || 'success');
        toastElement.setAttribute('role', 'alert');
        toastElement.setAttribute('aria-live', 'assertive');
        toastElement.setAttribute('aria-atomic', 'true');

        var wrapper = document.createElement('div');
        wrapper.className = 'd-flex';

        var body = document.createElement('div');
        body.className = 'toast-body';
        body.textContent = message;

        var close = document.createElement('button');
        close.type = 'button';
        close.className = 'btn-close btn-close-white me-2 m-auto';
        close.setAttribute('data-bs-dismiss', 'toast');
        close.setAttribute('aria-label', 'Tutup');

        wrapper.appendChild(body);
        wrapper.appendChild(close);
        toastElement.appendChild(wrapper);
        container.appendChild(toastElement);

        toastElement.addEventListener('hidden.bs.toast', function () {
            toastElement.remove();
        });

        new bootstrap.Toast(toastElement, { autohide: true, delay: 4500 }).show();
    }

    function updateProgress(progress) {
        if (!progress) {
            return;
        }

        var percent = Number(progress.percent || 0);
        var completed = Number(progress.completed || 0);
        var total = Number(progress.total || 0);

        var label = document.getElementById('lq-progress-label');
        if (label) {
            label.textContent = String(percent);
        }

        var completedElement = document.getElementById('lq-progress-completed');
        if (completedElement) {
            completedElement.textContent = String(completed);
        }

        var totalElement = document.getElementById('lq-progress-total');
        if (totalElement) {
            totalElement.textContent = String(total);
        }

        var fill = document.getElementById('lq-progress-fill');
        if (fill) {
            fill.style.width = percent + '%';
            var bar = fill.closest('.lq-progress');
            if (bar) {
                bar.setAttribute('aria-valuenow', String(percent));
            }
        }

        var caption = document.getElementById('lq-progress-caption');
        if (caption) {
            caption.textContent = completed + ' dari ' + total + ' aktivitas selesai';
        }
    }

    function setSubmitting(form, submitting) {
        var button = form.querySelector('.js-complete-btn');
        if (!button) {
            return;
        }

        var spinner = button.querySelector('.spinner-border');
        var label = button.querySelector('.js-btn-label');
        button.disabled = submitting;

        if (spinner) {
            spinner.classList.toggle('d-none', !submitting);
        }

        if (label) {
            label.textContent = submitting ? 'Menyimpan...' : 'Selesaikan';
        }
    }

    ready(function () {
        try {
            var storedToast = sessionStorage.getItem('lq_toast');
            if (storedToast) {
                var toastPayload = JSON.parse(storedToast);
                sessionStorage.removeItem('lq_toast');
                window.setTimeout(function () {
                    showToast(toastPayload.message, toastPayload.variant || 'success');
                }, 150);
            }
        } catch (error) {
            sessionStorage.removeItem('lq_toast');
        }

        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var message = form.getAttribute('data-confirm');
                if (message && !window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });

        document.querySelectorAll('.js-complete-form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.fetch) {
                    return;
                }

                event.preventDefault();

                if (form.dataset.submitting === '1') {
                    return;
                }

                form.dataset.submitting = '1';
                setSubmitting(form, true);

                var item = form.closest('.lq-check-item');
                if (item) {
                    item.classList.add('is-pending');
                }

                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) {
                        return response.json().catch(function () {
                            return null;
                        }).then(function (payload) {
                            if (!response.ok || !payload || !payload.success) {
                                var message = payload && payload.message ? payload.message : 'Aktivitas gagal diselesaikan. Silakan coba lagi.';
                                throw new Error(message);
                            }

                            return payload;
                        });
                    })
                    .then(function (payload) {
                        if (payload.data && payload.data.progress) {
                            updateProgress(payload.data.progress);
                        }

                        try {
                            sessionStorage.setItem('lq_toast', JSON.stringify({
                                message: payload.data && payload.data.message ? payload.data.message : 'Aktivitas berhasil diselesaikan.',
                                variant: 'success'
                            }));
                        } catch (error) {
                            // Session storage may be unavailable; reload still updates the checklist.
                        }

                        window.setTimeout(function () {
                            window.location.reload();
                        }, 350);
                    })
                    .catch(function (error) {
                        form.dataset.submitting = '';
                        setSubmitting(form, false);

                        if (item) {
                            item.classList.remove('is-pending');
                        }

                        showToast(error.message || 'Aktivitas gagal diselesaikan. Silakan coba lagi.', 'danger');
                    });
            });
        });

        var recurrenceSelect = document.getElementById('recurrence_type');
        var weekdayField = document.getElementById('recurrence-weekday-field');
        var daysField = document.getElementById('recurrence-days-field');

        if (recurrenceSelect && weekdayField && daysField) {
            var toggleRecurrence = function () {
                var value = recurrenceSelect.value;
                weekdayField.classList.toggle('d-none', value !== 'WEEKLY');
                daysField.classList.toggle('d-none', value !== 'CUSTOM_DAYS');
            };

            recurrenceSelect.addEventListener('change', toggleRecurrence);
            toggleRecurrence();
        }

        var repeatType = document.getElementById('repeat_type');
        var cooldownField = document.getElementById('cooldown-field');
        var cooldownInput = document.getElementById('cooldown_seconds');
        var maxDailyInput = document.getElementById('maximum_daily_completions');

        if (repeatType) {
            var toggleRepeatFields = function () {
                var isRepeatable = repeatType.value === 'REPEATABLE';

                if (cooldownField) {
                    cooldownField.classList.toggle('d-none', !isRepeatable);
                }

                if (cooldownInput && !isRepeatable) {
                    cooldownInput.value = '0';
                }

                if (maxDailyInput && repeatType.value === 'ONCE_DAILY') {
                    maxDailyInput.value = '1';
                }
            };

            repeatType.addEventListener('change', toggleRepeatFields);
            toggleRepeatFields();
        }
    });
})();
