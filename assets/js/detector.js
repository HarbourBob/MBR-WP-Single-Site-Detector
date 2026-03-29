jQuery(document).ready(function($) {
    'use strict';

    /* =========================================================
       Apply saved UI theme flags from PHP (mbrDetectorFlags)
    ========================================================= */
    if (typeof mbrDetectorFlags !== 'undefined') {
        var container = $('.wp-detector-container');
        if (mbrDetectorFlags.darkMode)      container.addClass('detector-dark');
        if (mbrDetectorFlags.glassmorphism) container.addClass('detector-glass');
    }

    /* =========================================================
       Cache DOM references
    ========================================================= */
    var form         = $('#wp-detector-form');
    var resultsDiv   = $('#wp-detector-results');
    var loadingDiv   = $('#wp-detector-loading');
    var urlInput     = $('#site-url');
    var submitButton = form.find('button[type="submit"]');

    /* =========================================================
       Form submission
    ========================================================= */
    form.on('submit', function(e) {
        e.preventDefault();

        var siteUrl = urlInput.val().trim();

        if (!siteUrl) {
            showError('Please enter a valid URL.');
            return;
        }

        if (!isValidUrl(siteUrl)) {
            showError('Please enter a valid URL (e.g., https://example.com).');
            return;
        }

        showLoading();

        $.ajax({
            url:  wpDetectorAjax.ajax_url,
            type: 'POST',
            data: {
                action:   'detect_wordpress_site',
                nonce:    wpDetectorAjax.nonce,
                site_url: siteUrl
            },
            success: function(response) {
                hideLoading();
                if (response.success) {
                    displayResults(response.data);
                } else {
                    showError(response.data.message || 'An error occurred. Please try again.');
                }
            },
            error: function(xhr, status, error) {
                hideLoading();
                showError('Unable to connect to the server. Please try again.');
                console.error('AJAX Error:', error);
            }
        });
    });

    /* =========================================================
       Helpers
    ========================================================= */
    function isValidUrl(string) {
        try {
            var url = new URL(string);
            return url.protocol === 'http:' || url.protocol === 'https:';
        } catch (_) {
            return false;
        }
    }

    function showLoading() {
        submitButton.prop('disabled', true).text('Detecting…');
        resultsDiv.hide();
        loadingDiv.show();
    }

    function hideLoading() {
        submitButton.prop('disabled', false).text('Detect');
        loadingDiv.hide();
    }

    function displayResults(data) {
        var html = '';

        if (data.error) {
            resultsDiv.removeClass('not-wordpress').addClass('error');
            html = '<div class="result-header">' +
                       '<div class="result-icon error">⚠️</div>' +
                       '<div class="result-title"><h4>Error</h4></div>' +
                   '</div>' +
                   '<div class="error-message">' + escapeHtml(data.error) + '</div>';

        } else if (!data.is_wordpress) {
            resultsDiv.removeClass('error').addClass('not-wordpress');
            html = '<div class="result-header">' +
                       '<div class="result-icon warning">ℹ️</div>' +
                       '<div class="result-title">' +
                           '<h4>Not WordPress</h4>' +
                           '<p>' + escapeHtml(data.url) + '</p>' +
                       '</div>' +
                   '</div>' +
                   '<p>' + escapeHtml(data.message) + '</p>';

        } else {
            resultsDiv.removeClass('error not-wordpress');
            html = buildWordPressResults(data);
        }

        resultsDiv.html(html).fadeIn();
    }

    function buildWordPressResults(data) {
        var html = '<div class="result-header">' +
                       '<div class="result-icon success">✓</div>' +
                       '<div class="result-title">' +
                           '<h4>WordPress Detected!</h4>' +
                           '<p>' + escapeHtml(data.url) + '</p>' +
                       '</div>' +
                   '</div>';

        if (data.wp_version && data.wp_version !== 'Unknown') {
            html += '<div class="result-section">' +
                        '<h5>WordPress Version</h5>' +
                        '<span class="wp-version">' + escapeHtml(data.wp_version) + '</span>' +
                    '</div>';
        }

        if (data.theme) {
            html += '<div class="result-section">' +
                        '<h5>Active Theme</h5>' +
                        '<div class="result-item">' +
                            '<div class="result-item-icon">🎨</div>' +
                            '<div class="result-item-content">' +
                                '<div class="result-item-name">' + escapeHtml(data.theme.name) + '</div>' +
                                '<div class="result-item-slug">' + escapeHtml(data.theme.slug) + '</div>' +
                            '</div>' +
                            (data.theme.url ? '<a href="' + escapeHtml(data.theme.url) + '" target="_blank" rel="noopener noreferrer" class="result-item-button">View Theme</a>' : '') +
                        '</div>' +
                    '</div>';
        }

        html += '<div class="result-section"><h5>Detected Plugins</h5>';

        if (data.plugins && data.plugins.length > 0) {
            $.each(data.plugins, function(i, plugin) {
                var versionBadge = plugin.version
                    ? '<span class="result-item-version">v' + escapeHtml(plugin.version) + '</span>'
                    : '';
                html += '<div class="result-item">' +
                            '<div class="result-item-icon">🔌</div>' +
                            '<div class="result-item-content">' +
                                '<div class="result-item-name">' + escapeHtml(plugin.name) + '</div>' +
                                '<div class="result-item-slug">' + escapeHtml(plugin.slug) + versionBadge + '</div>' +
                            '</div>' +
                            (plugin.url ? '<a href="' + escapeHtml(plugin.url) + '" target="_blank" rel="noopener noreferrer" class="result-item-button">View Plugin</a>' : '') +
                        '</div>';
            });
        } else {
            html += '<div class="no-plugins">No plugins detected in the page source. Some plugins may be active but not visible in the frontend.</div>';
        }

        html += '</div>';
        return html;
    }

    function showError(message) {
        resultsDiv.removeClass('not-wordpress').addClass('error');
        resultsDiv.html(
            '<div class="result-header">' +
                '<div class="result-icon error">⚠️</div>' +
                '<div class="result-title"><h4>Error</h4></div>' +
            '</div>' +
            '<div class="error-message">' + escapeHtml(message) + '</div>'
        ).fadeIn();
    }

    function escapeHtml(text) {
        var map = {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'};
        return String(text).replace(/[&<>"']/g, function(m){ return map[m]; });
    }
});
