/**
 * Directorist Simple Affiliate - Visitor Tracking JavaScript
 */
(function($) {
    'use strict';

    $(document).ready(function() {
        // Check if we have a referral cookie
        const refCode = getCookie(dsaTracking.cookieName);
        
        if (refCode) {
            // Record visit asynchronously
            recordVisit(refCode);
        }
    });

    /**
     * Record visit via AJAX
     */
    function recordVisit(refCode) {
        const visitData = {
            action: 'dsa_record_visit',
            nonce: dsaTracking.nonce,
            ref_code: refCode,
            referrer: document.referrer || '',
            landing: window.location.href,
            user_agent: navigator.userAgent || ''
        };

        $.ajax({
            url: dsaTracking.ajaxUrl,
            type: 'POST',
            data: visitData,
            timeout: 5000, // 5 second timeout
            success: function(response) {
                // Visit recorded successfully (or duplicate ignored)
                if (response.success && !response.data.duplicate) {
                    // Optional: Track conversion events here
                    // trackConversion();
                }
            },
            error: function() {
                // Silently fail - don't interrupt user experience
                console.log('DSA: Visit tracking failed (non-critical)');
            }
        });
    }

    /**
     * Get cookie value
     */
    function getCookie(name) {
        const value = "; " + document.cookie;
        const parts = value.split("; " + name + "=");
        if (parts.length === 2) {
            return parts.pop().split(";").shift();
        }
        return null;
    }

    /**
     * Track conversion (can be called when a purchase/action occurs)
     */
    function trackConversion() {
        const refCode = getCookie(dsaTracking.cookieName);
        if (!refCode) {
            return;
        }

        $.ajax({
            url: dsaTracking.ajaxUrl,
            type: 'POST',
            data: {
                action: 'dsa_record_conversion',
                nonce: dsaTracking.nonce,
                ref_code: refCode
            },
            timeout: 5000
        });
    }

    // Expose conversion tracking globally for use in other scripts
    window.dsaTrackConversion = trackConversion;
})(jQuery);

