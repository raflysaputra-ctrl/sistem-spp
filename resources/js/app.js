import './bootstrap';
import Chart from 'chart.js/auto';

const chartElement = document.querySelector('[data-finance-chart]');
const chartDataElement = document.querySelector('#finance-chart-data');

if (chartElement && chartDataElement) {
    const chartData = JSON.parse(chartDataElement.textContent);
    const rupiah = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    });
    const data = {
        labels: chartData.labels,
        datasets: [{
            label: 'Penerimaan',
            data: chartData.values,
            fill: false,
            borderColor: '#087443',
            backgroundColor: '#087443',
            pointBackgroundColor: '#ffffff',
            pointBorderColor: '#087443',
            pointBorderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            tension: 0.1,
        }],
    };
    const config = {
        type: 'line',
        data,
        options: {
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (context) => `Penerimaan: ${rupiah.format(context.parsed.y)}`,
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: (value) => rupiah.format(value),
                    },
                },
            },
        },
    };

    new Chart(chartElement, config);
}

const confirmationDialog = document.querySelector('#confirmation-dialog');
const confirmationForm = document.querySelector('#confirmation-form');
const confirmationTitle = document.querySelector('#confirmation-title');
const confirmationMessage = document.querySelector('#confirmation-message');
const confirmationDetails = document.querySelector('#confirmation-details');
const confirmationInputField = document.querySelector('#confirmation-input-field');
const confirmationInputLabel = document.querySelector('#confirmation-input-label');
const confirmationInput = document.querySelector('#confirmation-input');
const confirmationInputError = document.querySelector('#confirmation-input-error');
const confirmationCancel = document.querySelector('#confirmation-cancel');
const confirmationSubmit = document.querySelector('#confirmation-submit');
let formToConfirm;

if (confirmationDialog instanceof HTMLDialogElement) {
    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirmed === 'true') {
                delete form.dataset.confirmed;

                return;
            }

            event.preventDefault();
            formToConfirm = form;
            confirmationTitle.textContent = form.dataset.confirmTitle ?? 'Konfirmasi tindakan';
            confirmationMessage.textContent = form.dataset.confirmMessage ?? 'Pastikan tindakan ini sudah benar.';
            confirmationSubmit.textContent = form.dataset.confirmSubmit ?? 'Lanjutkan';
            confirmationDetails.replaceChildren();
            confirmationDetails.hidden = true;

            const detailsTemplate = form.querySelector('template[data-confirm-details]');

            if (detailsTemplate instanceof HTMLTemplateElement && detailsTemplate.content.childElementCount > 0) {
                confirmationDetails.append(detailsTemplate.content.cloneNode(true));
                confirmationDetails.hidden = false;
            }

            const needsInput = Boolean(form.dataset.confirmInputName);
            confirmationInputField.hidden = !needsInput;
            confirmationInput.value = '';
            confirmationInput.required = needsInput;
            confirmationInput.type = form.dataset.confirmInputType ?? 'text';
            confirmationInput.autocomplete = form.dataset.confirmInputAutocomplete ?? 'off';
            confirmationInput.setAttribute('aria-invalid', 'false');
            confirmationInputError.hidden = true;
            confirmationSubmit.disabled = false;

            if (needsInput) {
                confirmationInputLabel.textContent = form.dataset.confirmInputLabel ?? 'Ketik konfirmasi untuk melanjutkan';
            }

            confirmationDialog.showModal();

            if (needsInput) {
                confirmationInput.focus();
            }
        });
    });

    confirmationInput.addEventListener('input', () => {
        if (!formToConfirm?.dataset.confirmInputName) {
            return;
        }

        if (formToConfirm.dataset.confirmInputValue === undefined) {
            confirmationInput.setAttribute('aria-invalid', 'false');
            confirmationInputError.hidden = true;

            return;
        }

        const isValid = confirmationInput.value === formToConfirm.dataset.confirmInputValue;
        confirmationInput.setAttribute('aria-invalid', String(!isValid));
        confirmationInputError.textContent = 'NIS tidak sesuai. Ketik NIS yang ditampilkan untuk melanjutkan.';
        confirmationInputError.hidden = isValid || confirmationInput.value === '';
    });

    confirmationSubmit.addEventListener('click', () => {
        if (!formToConfirm) {
            return;
        }

        const inputName = formToConfirm.dataset.confirmInputName;

        if (inputName && confirmationInput.value === '') {
            confirmationInput.setAttribute('aria-invalid', 'true');
            confirmationInputError.textContent = 'Input konfirmasi wajib diisi.';
            confirmationInputError.hidden = false;
            confirmationInput.focus();

            return;
        }

        if (inputName && formToConfirm.dataset.confirmInputValue !== undefined && confirmationInput.value !== formToConfirm.dataset.confirmInputValue) {
            confirmationInput.setAttribute('aria-invalid', 'true');
            confirmationInputError.textContent = 'NIS tidak sesuai. Ketik NIS yang ditampilkan untuk melanjutkan.';
            confirmationInputError.hidden = false;
            confirmationInput.focus();

            return;
        }

        if (inputName) {
            let hiddenInput = Array.from(formToConfirm.elements).find(
                (element) => element instanceof HTMLInputElement && element.type === 'hidden' && element.name === inputName,
            );

            if (!hiddenInput) {
                hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = inputName;
                formToConfirm.append(hiddenInput);
            }

            hiddenInput.value = confirmationInput.value;
        }

        const form = formToConfirm;
        confirmationDialog.close();
        form.dataset.confirmed = 'true';
        form.requestSubmit();
    });

    confirmationCancel.addEventListener('click', () => {
        confirmationDialog.close();
    });

    confirmationForm.addEventListener('submit', (event) => {
        event.preventDefault();
        confirmationSubmit.click();
    });

    confirmationDialog.addEventListener('close', () => {
        confirmationInput.value = '';
        confirmationInput.type = 'text';
        confirmationInput.autocomplete = 'off';
        confirmationInput.setAttribute('aria-invalid', 'false');
        confirmationInputError.hidden = true;
        formToConfirm = undefined;
    });
}
