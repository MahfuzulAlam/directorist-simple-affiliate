/**
 * Directorist Simple Affiliate - Registration Form JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        const $form = $('#dsa-registration-form');
        
        if (!$form.length) {
            return;
        }

        // Payment method change handler
        const $paymentMethod = $('#dsa_payment_method');
        const $paypalField = $('.dsa-paypal-field');
        const $bankTransferField = $('.dsa-bank-transfer-field');
        const $paypalEmail = $('#dsa_paypal_email');
        const $bankDetails = $('#dsa_bank_details');

        function togglePaymentFields() {
            const selectedMethod = $paymentMethod.val();
            
            // Hide all payment fields first
            $paypalField.hide();
            $bankTransferField.hide();
            
            // Remove required attributes
            $paypalEmail.removeAttr('required');
            $bankDetails.removeAttr('required');
            
            // Show and set required based on selection
            if (selectedMethod === 'PayPal') {
                $paypalField.show();
                $paypalEmail.attr('required', 'required');
            } else if (selectedMethod === 'Bank Transfer') {
                $bankTransferField.show();
                $bankDetails.attr('required', 'required');
            }
        }

        // Handle payment method change
        $paymentMethod.on('change', togglePaymentFields);
        
        // Initialize on page load
        togglePaymentFields();

        // Form validation
        $form.on('submit', function(e) {
            let isValid = true;
            const $requiredFields = $form.find('[required]');

            // Remove previous error states
            $requiredFields.removeClass('dsa-field-error');

            // Check required fields
            $requiredFields.each(function() {
                const $field = $(this);
                const value = $field.val().trim();

                if ($field.is(':checkbox')) {
                    if (!$field.is(':checked')) {
                        isValid = false;
                        $field.addClass('dsa-field-error');
                    }
                } else {
                    if (!value) {
                        isValid = false;
                        $field.addClass('dsa-field-error');
                    }
                }
            });

            // Email validation
            const $email = $('#dsa_email');
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if ($email.length && !emailPattern.test($email.val())) {
                isValid = false;
                $email.addClass('dsa-field-error');
            }

            // Validate payment method specific fields
            const selectedMethod = $paymentMethod.val();
            if (selectedMethod === 'PayPal') {
                const paypalEmailVal = $paypalEmail.val().trim();
                if (!paypalEmailVal) {
                    isValid = false;
                    $paypalEmail.addClass('dsa-field-error');
                } else if (!emailPattern.test(paypalEmailVal)) {
                    isValid = false;
                    $paypalEmail.addClass('dsa-field-error');
                }
            } else if (selectedMethod === 'Bank Transfer') {
                const bankDetailsVal = $bankDetails.val().trim();
                if (!bankDetailsVal) {
                    isValid = false;
                    $bankDetails.addClass('dsa-field-error');
                }
            }

            // URL validation
            const $website = $('#dsa_website');
            if ($website.length) {
                try {
                    new URL($website.val());
                } catch (e) {
                    isValid = false;
                    $website.addClass('dsa-field-error');
                }
            }

            if (!isValid) {
                e.preventDefault();
                // Scroll to first error
                const $firstError = $form.find('.dsa-field-error').first();
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 500);
                }
                return false;
            }

            // Allow form to submit - don't prevent default if valid
            // Disable submit button to prevent double submission
            const $submitBtn = $form.find('button[type="submit"]');
            $submitBtn.prop('disabled', true).text('Submitting...');
            
            // Form will submit normally if validation passes
        });

        // Real-time validation feedback
        $form.find('input, select, textarea').on('blur', function() {
            const $field = $(this);
            const value = $field.val().trim();

            if ($field.prop('required')) {
                if ($field.is(':checkbox')) {
                    if (!$field.is(':checked')) {
                        $field.addClass('dsa-field-error');
                    } else {
                        $field.removeClass('dsa-field-error');
                    }
                } else {
                    if (!value) {
                        $field.addClass('dsa-field-error');
                    } else {
                        $field.removeClass('dsa-field-error');
                    }
                }
            }
        });
    });
})(jQuery);

