/**
 * Directorist Simple Affiliate - Admin JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Handle approve button click
        $('.dsa-approve-btn').on('click', function(e) {
            e.preventDefault();
            const userId = $(this).data('user-id');
            const $card = $(this).closest('.dsa-affiliate-card');
            const $form = $card.find('.dsa-action-form');
            const $actionInput = $form.find('input[name="dsa_action"]');
            const $commentField = $card.find('.dsa-comment-field');
            const $commentTextarea = $card.find('.dsa-comment-textarea');
            const $actionButtons = $card.find('.dsa-action-buttons');
            const $approveBtn = $card.find('.dsa-approve-btn');
            const $rejectBtn = $card.find('.dsa-reject-btn');
            const $submitBtn = $card.find('.dsa-submit-action');
            const $cancelBtn = $card.find('.dsa-cancel-action');

            // Set action
            $actionInput.val('approve');
            $actionInput.attr('id', 'dsa_action_' + userId);

            // Hide comment field (approve doesn't need comment)
            $commentField.hide();
            $commentTextarea.removeAttr('required');

            // Show/hide buttons
            $approveBtn.hide();
            $rejectBtn.hide();
            $submitBtn.show().text('Confirm Approve');
            $cancelBtn.show();
        });

        // Handle reject button click
        $('.dsa-reject-btn').on('click', function(e) {
            e.preventDefault();
            const userId = $(this).data('user-id');
            const $card = $(this).closest('.dsa-affiliate-card');
            const $form = $card.find('.dsa-action-form');
            const $actionInput = $form.find('input[name="dsa_action"]');
            const $commentField = $card.find('.dsa-comment-field');
            const $commentTextarea = $card.find('.dsa-comment-textarea');
            const $actionButtons = $card.find('.dsa-action-buttons');
            const $approveBtn = $card.find('.dsa-approve-btn');
            const $rejectBtn = $card.find('.dsa-reject-btn');
            const $submitBtn = $card.find('.dsa-submit-action');
            const $cancelBtn = $card.find('.dsa-cancel-action');

            // Set action
            $actionInput.val('reject');
            $actionInput.attr('id', 'dsa_action_' + userId);

            // Show comment field (optional for reject)
            $commentField.show();
            $commentTextarea.focus();

            // Show/hide buttons
            $approveBtn.hide();
            $rejectBtn.hide();
            $submitBtn.show().text('Confirm Reject');
            $cancelBtn.show();
        });

        // Handle cancel button click
        $('.dsa-cancel-action').on('click', function(e) {
            e.preventDefault();
            const $card = $(this).closest('.dsa-affiliate-card');
            const $form = $card.find('.dsa-action-form');
            const $actionInput = $form.find('input[name="dsa_action"]');
            const $commentField = $card.find('.dsa-comment-field');
            const $commentTextarea = $card.find('.dsa-comment-textarea');
            const $approveBtn = $card.find('.dsa-approve-btn');
            const $rejectBtn = $card.find('.dsa-reject-btn');
            const $submitBtn = $card.find('.dsa-submit-action');
            const $cancelBtn = $card.find('.dsa-cancel-action');

            // Reset form
            $actionInput.val('');
            $commentTextarea.val('');
            $commentField.hide();
            $commentTextarea.removeAttr('required');

            // Show/hide buttons
            $approveBtn.show();
            $rejectBtn.show();
            $submitBtn.hide();
            $cancelBtn.hide();
        });

        // Handle form submission
        $('.dsa-action-form').on('submit', function(e) {
            const $form = $(this);
            const action = $form.find('input[name="dsa_action"]').val();
            const $commentTextarea = $form.find('.dsa-comment-textarea');

            // Validate comment for reject (optional but recommended)
            if (action === 'reject') {
                const comment = $commentTextarea.val().trim();
                if (!comment) {
                    if (!confirm('Are you sure you want to reject without adding a comment? The affiliate will not receive a reason for rejection.')) {
                        e.preventDefault();
                        $commentTextarea.focus();
                        return false;
                    }
                }
            }

            // Show loading state
            const $submitBtn = $form.find('.dsa-submit-action');
            const originalText = $submitBtn.text();
            $submitBtn.prop('disabled', true).text('Processing...');
        });
    });
})(jQuery);

