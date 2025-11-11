/**
 * Directorist Simple Affiliate - Frontend Dashboard JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        const $generateBtn = $('.dsa-generate-code-btn');
        const $generateForm = $('.dsa-generate-form');
        const $cancelForm = $('.dsa-cancel-form');
        const $typeSelect = $('#dsa_type');
        const $campaignField = $('.dsa-campaign-field');

        // Toggle generate form
        $generateBtn.on('click', function() {
            $generateForm.slideToggle();
        });

        $cancelForm.on('click', function() {
            $generateForm.slideUp();
            $('#dsa-generate-code-form')[0].reset();
        });

        // Show/hide campaign field based on type
        $typeSelect.on('change', function() {
            if ($(this).val() === 'campaign') {
                $campaignField.slideDown();
            } else {
                $campaignField.slideUp();
            }
        });

        // Handle form submission
        $('#dsa-generate-code-form').on('submit', function(e) {
            e.preventDefault();

            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const originalText = $submitBtn.text();

            $submitBtn.prop('disabled', true).text('Generating...');

            $.ajax({
                url: dsaFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dsa_generate_affiliate_code',
                    nonce: dsaFrontend.nonce,
                    code: $('#dsa_code').val(),
                    type: $('#dsa_type').val(),
                    campaign_name: $('#dsa_campaign_name').val(),
                    description: $('#dsa_description').val(),
                    expires_at: $('#dsa_expires_at').val(),
                },
                success: function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert(response.data.message);
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });

        // Copy code to clipboard
        $(document).on('click', '.dsa-copy-btn, .dsa-copy-code-btn', function() {
            const code = $(this).data('code');
            copyToClipboard(code);
            $(this).text('Copied!').css('background', '#00a32a');
            setTimeout(() => {
                $(this).text('Copy').css('background', '');
            }, 2000);
        });

        // Copy URL to clipboard
        $(document).on('click', '.dsa-copy-url-btn', function() {
            const url = $(this).data('url');
            copyToClipboard(url);
            $(this).text('Copied!').css('background', '#00a32a');
            setTimeout(() => {
                $(this).text('Copy URL').css('background', '');
            }, 2000);
        });

        // Delete code
        $(document).on('click', '.dsa-delete-code-btn', function() {
            if (!confirm('Are you sure you want to delete this affiliate code?')) {
                return;
            }

            const $btn = $(this);
            const codeId = $btn.data('code-id');
            const $row = $btn.closest('tr');

            $btn.prop('disabled', true).text('Deleting...');

            $.ajax({
                url: dsaFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dsa_delete_affiliate_code',
                    nonce: dsaFrontend.nonce,
                    code_id: codeId,
                },
                success: function(response) {
                    if (response.success) {
                        $row.fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data.message);
                        $btn.prop('disabled', false).text('Delete');
                    }
                },
                error: function() {
                    alert('An error occurred. Please try again.');
                    $btn.prop('disabled', false).text('Delete');
                }
            });
        });

        // Copy to clipboard helper function
        function copyToClipboard(text) {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            try {
                document.execCommand('copy');
            } catch (err) {
                console.error('Failed to copy:', err);
            }
            document.body.removeChild(textarea);
        }

        // Settings form - Payment method toggle
        const $settingsPaymentMethod = $('#dsa_settings_payment_method');
        const $settingsPaypalField = $('.dsa-settings-form .dsa-paypal-field');
        const $settingsBankField = $('.dsa-settings-form .dsa-bank-transfer-field');
        const $settingsPaypalEmail = $('#dsa_settings_paypal_email');
        const $settingsBankDetails = $('#dsa_settings_bank_details');

        function toggleSettingsPaymentFields() {
            const selectedMethod = $settingsPaymentMethod.val();
            
            $settingsPaypalField.hide();
            $settingsBankField.hide();
            $settingsPaypalEmail.removeAttr('required');
            $settingsBankDetails.removeAttr('required');
            
            if (selectedMethod === 'PayPal') {
                $settingsPaypalField.show();
                $settingsPaypalEmail.attr('required', 'required');
            } else if (selectedMethod === 'Bank Transfer') {
                $settingsBankField.show();
                $settingsBankDetails.attr('required', 'required');
            }
        }

        if ($settingsPaymentMethod.length) {
            $settingsPaymentMethod.on('change', toggleSettingsPaymentFields);
        }

        // Settings form submission
        $('#dsa-affiliate-settings-form').on('submit', function(e) {
            e.preventDefault();

            const $form = $(this);
            const $submitBtn = $form.find('button[type="submit"]');
            const $message = $form.find('.dsa-form-message');
            const originalText = $submitBtn.text();

            $submitBtn.prop('disabled', true).text('Saving...');
            $message.hide().removeClass('dsa-message-success dsa-message-error');

            // Validate payment method specific fields
            const paymentMethod = $settingsPaymentMethod.val();
            let isValid = true;

            if (paymentMethod === 'PayPal') {
                const paypalEmail = $settingsPaypalEmail.val().trim();
                if (!paypalEmail) {
                    isValid = false;
                    $settingsPaypalEmail.addClass('dsa-field-error');
                } else {
                    $settingsPaypalEmail.removeClass('dsa-field-error');
                }
            } else if (paymentMethod === 'Bank Transfer') {
                const bankDetails = $settingsBankDetails.val().trim();
                if (!bankDetails) {
                    isValid = false;
                    $settingsBankDetails.addClass('dsa-field-error');
                } else {
                    $settingsBankDetails.removeClass('dsa-field-error');
                }
            }

            if (!isValid) {
                $submitBtn.prop('disabled', false).text(originalText);
                $message.text('Please fill in all required fields.').addClass('dsa-message-error').show();
                return;
            }

            $.ajax({
                url: dsaFrontend.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'dsa_update_affiliate_settings',
                    nonce: dsaFrontend.nonce,
                    payment_method: $settingsPaymentMethod.val(),
                    website: $('#dsa_settings_website').val(),
                    phone: $('#dsa_settings_phone').val(),
                    paypal_email: $settingsPaypalEmail.val(),
                    bank_details: $settingsBankDetails.val(),
                },
                success: function(response) {
                    if (response.success) {
                        $message.text(response.data.message).addClass('dsa-message-success').show();
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        $message.text(response.data.message).addClass('dsa-message-error').show();
                        $submitBtn.prop('disabled', false).text(originalText);
                    }
                },
                error: function() {
                    $message.text('An error occurred. Please try again.').addClass('dsa-message-error').show();
                    $submitBtn.prop('disabled', false).text(originalText);
                }
            });
        });
    });
})(jQuery);


