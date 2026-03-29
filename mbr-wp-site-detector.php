<?php
/**
 * Plugin Name: MBR WP Site Detector
 * Plugin URI: https://littlewebshack.com/
 * Description: Detects if a website is built with WordPress and identifies the theme and plugins being used
 * Version: 1.6.1
 * Author: Robert Palmer
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: robs-site-detector
 */

if (!defined('ABSPATH')) exit;

/* =========================================================
   Buy Me a Coffee row meta
========================================================= */
add_filter( 'plugin_row_meta', function ( $links, $file, $data ) {
    if ( ! function_exists( 'plugin_basename' ) || $file !== plugin_basename( __FILE__ ) ) return $links;
    $url = 'https://buymeacoffee.com/robertpalmer/';
    $links[] = sprintf(
        '<a href="%s" target="_blank" rel="noopener nofollow" aria-label="%s">☕ %s</a>',
        esc_url( $url ),
        esc_attr( sprintf( __( 'Buy %s a coffee', 'robs-site-detector' ), isset( $data['AuthorName'] ) ? $data['AuthorName'] : __( 'the author', 'robs-site-detector' ) ) ),
        esc_html__( 'Buy me a coffee', 'robs-site-detector' )
    );
    return $links;
}, 10, 3 );

/* =========================================================
   Constants
========================================================= */
define('WP_SITE_DETECTOR_VERSION',        '1.6.1');
define('WP_SITE_DETECTOR_PLUGIN_DIR',     plugin_dir_path(__FILE__));
define('WP_SITE_DETECTOR_PLUGIN_URL',     plugin_dir_url(__FILE__));
define('WP_SITE_DETECTOR_DEFAULT_COLOR',  '#0073aa');

/* =========================================================
   Main class
========================================================= */
class WP_Site_Detector {

    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) self::$instance = new self();
        return self::$instance;
    }

    private function __construct() {
        add_shortcode('wp_detector', [$this, 'render_detector_form']);
        add_action('wp_enqueue_scripts',    [$this, 'enqueue_scripts']);
        add_action('wp_head',               [$this, 'output_inline_styles']);
        add_action('wp_ajax_detect_wordpress_site',        [$this, 'ajax_detect_site']);
        add_action('wp_ajax_nopriv_detect_wordpress_site', [$this, 'ajax_detect_site']);
        add_action('admin_menu',            [$this, 'register_settings_page']);
        add_action('admin_init',            [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    /* --- helpers --- */
    private function opt($key, $default = '') { return get_option($key, $default); }

    private function darken_hex($hex, $pct) {
        $hex = ltrim($hex, '#');
        if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        $r = max(0, (int)(hexdec(substr($hex,0,2)) * (1 - $pct/100)));
        $g = max(0, (int)(hexdec(substr($hex,2,2)) * (1 - $pct/100)));
        $b = max(0, (int)(hexdec(substr($hex,4,2)) * (1 - $pct/100)));
        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }

    /* =========================================================
       ADMIN – settings menu & registration
    ========================================================= */
    public function register_settings_page() {
        add_options_page(
            __('Site Detector Settings', 'robs-site-detector'),
            __('Site Detector',          'robs-site-detector'),
            'manage_options',
            'mbr-site-detector-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        $opts = [
            'mbr_detector_accent_color'  => [$this, 'sanitize_hex'],
            'mbr_detector_dark_mode'     => 'sanitize_text_field',
            'mbr_detector_glassmorphism' => 'sanitize_text_field',
        ];
        foreach ($opts as $name => $cb) {
            register_setting('mbr_site_detector_group', $name, ['sanitize_callback' => $cb]);
        }
    }

    public function sanitize_hex($v) {
        $v = sanitize_hex_color($v);
        return $v ?: WP_SITE_DETECTOR_DEFAULT_COLOR;
    }

    /* =========================================================
       ADMIN – enqueue colour-picker + inline admin CSS/JS
    ========================================================= */
    public function enqueue_admin_assets($hook) {
        if ('settings_page_mbr-site-detector-settings' !== $hook) return;
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        /* Inline JS – colour picker init + live preview */
        wp_add_inline_script('wp-color-picker', <<<'JS'
jQuery(function($){
    $('.mbr-color-picker').wpColorPicker({
        change: function(){ setTimeout(updatePreview, 50); },
        clear:  function(){ setTimeout(updatePreview, 50); }
    });
    $(document).on('change', '#mbr_detector_dark_mode, #mbr_detector_glassmorphism', updatePreview);

    function updatePreview(){
        var color  = $('.mbr-color-picker').val() || '#0073aa';
        var dark   = $('#mbr_detector_dark_mode').is(':checked');
        var glass  = $('#mbr_detector_glassmorphism').is(':checked');
        var p      = $('#mbr-detector-preview');

        p.find('.mbr-preview-button, .mbr-preview-badge').css('background', color);
        p.find('.mbr-preview-section-title').css('color', color);

        p.toggleClass('preview-dark',  dark).toggleClass('preview-light', !dark);
        p.toggleClass('preview-glass', glass);
        $('#mbr-glass-note').toggle(glass);
    }
    updatePreview();
});
JS
        );

        /* Inline CSS – admin page styles */
        wp_add_inline_style('wp-color-picker', $this->admin_css());
    }

    private function admin_css() { return <<<'CSS'
.mbr-settings-wrap { max-width: 960px; }
.mbr-settings-header { display:flex; align-items:center; gap:14px; margin:24px 0 28px; }
.mbr-settings-header .mbr-logo { background:#0073aa; color:#fff; width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
.mbr-settings-header h1 { margin:0; font-size:22px; }
.mbr-settings-header p  { margin:2px 0 0; color:#666; font-size:13px; }
.mbr-settings-grid { display:grid; grid-template-columns:1fr 360px; gap:24px; align-items:start; }
.mbr-card { background:#fff; border:1px solid #dcdcde; border-radius:10px; padding:24px; box-shadow:0 1px 3px rgba(0,0,0,.06); }
.mbr-card h2 { margin:0 0 18px; font-size:15px; font-weight:600; padding-bottom:12px; border-bottom:1px solid #eee; display:flex; align-items:center; gap:8px; }
.mbr-field-row { display:flex; align-items:flex-start; justify-content:space-between; gap:16px; padding:16px 0; border-bottom:1px solid #f0f0f0; }
.mbr-field-row:last-child { border-bottom:none; padding-bottom:0; }
.mbr-field-label { flex:1; }
.mbr-field-label strong { display:block; font-size:13.5px; margin-bottom:4px; }
.mbr-field-label span   { color:#777; font-size:12.5px; line-height:1.5; }
.mbr-toggle-wrap { display:flex; align-items:center; gap:10px; padding-top:2px; }
.mbr-toggle { position:relative; display:inline-block; width:46px; height:26px; flex-shrink:0; }
.mbr-toggle input { opacity:0; width:0; height:0; }
.mbr-toggle .slider { position:absolute; inset:0; background:#ccc; border-radius:26px; cursor:pointer; transition:background .25s; }
.mbr-toggle .slider::before { content:""; position:absolute; width:20px; height:20px; left:3px; bottom:3px; background:#fff; border-radius:50%; transition:transform .25s; box-shadow:0 1px 3px rgba(0,0,0,.3); }
.mbr-toggle input:checked + .slider { background:#0073aa; }
.mbr-toggle input:checked + .slider::before { transform:translateX(20px); }
.mbr-toggle-label { font-size:13px; color:#444; }
.mbr-color-wrap { display:flex; align-items:center; gap:12px; }
.mbr-save-row { margin-top:22px; }
.mbr-save-row .button-primary { padding:8px 24px; font-size:14px; height:auto; border-radius:6px; }
.mbr-preview-label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.6px; color:#999; margin-bottom:10px; }
#mbr-detector-preview { border-radius:10px; padding:22px; font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif; transition:background .3s,box-shadow .3s; }
#mbr-detector-preview.preview-light { background:#fff; border:1px solid #dcdcde; color:#333; }
#mbr-detector-preview.preview-dark  { background:#1e1e2e; border:1px solid #313244; color:#cdd6f4; }
#mbr-detector-preview.preview-dark .mbr-preview-subtext  { color:#a6adc8; }
#mbr-detector-preview.preview-dark .mbr-preview-input    { background:#313244; border-color:#45475a; color:#cdd6f4; }
#mbr-detector-preview.preview-dark .mbr-preview-result   { background:#181825; border-color:#313244; }
#mbr-detector-preview.preview-dark .mbr-preview-item     { background:#313244; border-color:#45475a; }
#mbr-detector-preview.preview-dark .mbr-preview-item-name  { color:#cdd6f4; }
#mbr-detector-preview.preview-dark .mbr-preview-item-slug  { color:#7f849c; }
#mbr-detector-preview.preview-glass { background:rgba(255,255,255,.12)!important; backdrop-filter:blur(20px) saturate(180%); -webkit-backdrop-filter:blur(20px) saturate(180%); border:1px solid rgba(255,255,255,.25)!important; box-shadow:0 8px 32px rgba(0,0,0,.15); }
#mbr-detector-preview.preview-dark.preview-glass { background:rgba(30,30,46,.55)!important; border-color:rgba(203,214,244,.1)!important; }
.mbr-preview-title   { font-size:16px; font-weight:700; margin-bottom:6px; }
.mbr-preview-subtext { font-size:12px; color:#666; margin-bottom:16px; }
.mbr-preview-row     { display:flex; gap:8px; margin-bottom:14px; }
.mbr-preview-input   { flex:1; padding:8px 10px; border:1.5px solid #ddd; border-radius:5px; font-size:12px; background:#f9f9f9; color:#333; transition:background .3s,border-color .3s,color .3s; }
.mbr-preview-button  { padding:8px 14px; background:#0073aa; color:#fff; border:none; border-radius:5px; font-size:12px; font-weight:600; cursor:default; white-space:nowrap; transition:background .2s; }
.mbr-preview-result  { background:#f9f9f9; border-radius:6px; padding:14px; border:1px solid #e0e0e0; transition:background .3s,border-color .3s; }
.mbr-preview-section-title { font-size:12px; font-weight:700; color:#0073aa; margin-bottom:8px; padding-bottom:5px; border-bottom:1.5px solid #e0e0e0; transition:color .2s; }
.mbr-preview-item    { display:flex; align-items:center; gap:8px; padding:8px 10px; background:#fff; border-radius:4px; border:1px solid #e0e0e0; margin-bottom:5px; transition:background .3s,border-color .3s; }
.mbr-preview-item:last-child { margin-bottom:0; }
.mbr-preview-badge   { width:24px; height:24px; background:#0073aa; border-radius:50%; flex-shrink:0; display:flex; align-items:center; justify-content:center; font-size:11px; color:#fff; transition:background .2s; }
.mbr-preview-item-name { font-size:12px; font-weight:600; color:#333; transition:color .3s; }
.mbr-preview-item-slug { font-size:10px; color:#999; font-family:monospace; transition:color .3s; }
.mbr-preview-glass-note { font-size:11px; color:#999; margin-top:10px; font-style:italic; text-align:center; }
CSS;
    }

    /* =========================================================
       ADMIN – render settings page HTML
    ========================================================= */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) return;

        $accent  = $this->opt('mbr_detector_accent_color',  WP_SITE_DETECTOR_DEFAULT_COLOR);
        $dark    = $this->opt('mbr_detector_dark_mode',    '0');
        $glass   = $this->opt('mbr_detector_glassmorphism','0');
        ?>
        <div class="wrap mbr-settings-wrap">

            <div class="mbr-settings-header">
                <div class="mbr-logo">🔍</div>
                <div>
                    <h1><?php esc_html_e('Site Detector – UI Settings', 'robs-site-detector'); ?></h1>
                    <p><?php esc_html_e('Customise how the detector widget looks on your site.', 'robs-site-detector'); ?></p>
                </div>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('mbr_site_detector_group'); ?>

                <div class="mbr-settings-grid">

                    <!-- Options card -->
                    <div class="mbr-card">
                        <h2>🎨 <?php esc_html_e('Appearance', 'robs-site-detector'); ?></h2>

                        <!-- Accent Colour -->
                        <div class="mbr-field-row">
                            <div class="mbr-field-label">
                                <strong><?php esc_html_e('Accent Colour', 'robs-site-detector'); ?></strong>
                                <span><?php esc_html_e('Applied to buttons, icon badges, borders and section headings.', 'robs-site-detector'); ?></span>
                            </div>
                            <div class="mbr-color-wrap">
                                <input
                                    type="text"
                                    id="mbr_detector_accent_color"
                                    name="mbr_detector_accent_color"
                                    value="<?php echo esc_attr($accent); ?>"
                                    class="mbr-color-picker"
                                    data-default-color="<?php echo esc_attr(WP_SITE_DETECTOR_DEFAULT_COLOR); ?>"
                                >
                            </div>
                        </div>

                        <!-- Dark Mode -->
                        <div class="mbr-field-row">
                            <div class="mbr-field-label">
                                <strong><?php esc_html_e('Dark Mode', 'robs-site-detector'); ?></strong>
                                <span><?php esc_html_e('Switches to a dark Catppuccin Mocha-inspired palette.', 'robs-site-detector'); ?></span>
                            </div>
                            <div class="mbr-toggle-wrap">
                                <label class="mbr-toggle">
                                    <input type="checkbox" id="mbr_detector_dark_mode" name="mbr_detector_dark_mode" value="1" <?php checked('1', $dark); ?>>
                                    <span class="slider"></span>
                                </label>
                                <span class="mbr-toggle-label"><?php esc_html_e('Enable', 'robs-site-detector'); ?></span>
                            </div>
                        </div>

                        <!-- Glassmorphism -->
                        <div class="mbr-field-row">
                            <div class="mbr-field-label">
                                <strong><?php esc_html_e('Glassmorphism', 'robs-site-detector'); ?></strong>
                                <span><?php esc_html_e('Frosted-glass backdrop blur effect on the widget panel.', 'robs-site-detector'); ?></span>
                            </div>
                            <div class="mbr-toggle-wrap">
                                <label class="mbr-toggle">
                                    <input type="checkbox" id="mbr_detector_glassmorphism" name="mbr_detector_glassmorphism" value="1" <?php checked('1', $glass); ?>>
                                    <span class="slider"></span>
                                </label>
                                <span class="mbr-toggle-label"><?php esc_html_e('Enable', 'robs-site-detector'); ?></span>
                            </div>
                        </div>

                        <div class="mbr-save-row">
                            <?php submit_button(__('Save Settings', 'robs-site-detector'), 'primary', 'submit', false); ?>
                        </div>
                    </div>

                    <!-- Preview card -->
                    <div class="mbr-card">
                        <h2>👁️ <?php esc_html_e('Live Preview', 'robs-site-detector'); ?></h2>
                        <p class="mbr-preview-label"><?php esc_html_e('Updates as you change settings', 'robs-site-detector'); ?></p>

                        <div id="mbr-detector-preview" class="preview-light">
                            <div class="mbr-preview-title">WordPress Site Detector</div>
                            <div class="mbr-preview-subtext">Enter a URL to check if it's built with WordPress.</div>
                            <div class="mbr-preview-row">
                                <div class="mbr-preview-input">https://example.com</div>
                                <div class="mbr-preview-button">Detect</div>
                            </div>
                            <div class="mbr-preview-result">
                                <div class="mbr-preview-section-title">Active Theme</div>
                                <div class="mbr-preview-item">
                                    <div class="mbr-preview-badge">🎨</div>
                                    <div>
                                        <div class="mbr-preview-item-name">Twenty Twenty-Four</div>
                                        <div class="mbr-preview-item-slug">twentytwentyfour</div>
                                    </div>
                                </div>
                                <div class="mbr-preview-section-title" style="margin-top:10px;">Detected Plugins</div>
                                <div class="mbr-preview-item">
                                    <div class="mbr-preview-badge">🔌</div>
                                    <div>
                                        <div class="mbr-preview-item-name">Elementor</div>
                                        <div class="mbr-preview-item-slug">elementor</div>
                                    </div>
                                </div>
                            </div>
                            <p class="mbr-preview-glass-note" id="mbr-glass-note" style="display:none;">
                                <?php esc_html_e('Glassmorphism works best over a colourful background image.', 'robs-site-detector'); ?>
                            </p>
                        </div>
                    </div>

                </div><!-- .mbr-settings-grid -->
            </form>
        </div>
        <?php
    }

    /* =========================================================
       FRONTEND – inline CSS vars + JS flags
    ========================================================= */
    public function output_inline_styles() {
        $accent = $this->opt('mbr_detector_accent_color', WP_SITE_DETECTOR_DEFAULT_COLOR);
        $dark   = $this->opt('mbr_detector_dark_mode',   '0');
        $glass  = $this->opt('mbr_detector_glassmorphism','0');

        echo '<style id="mbr-detector-inline-vars">:root{';
        echo '--detector-accent:'       . esc_attr($accent)                          . ';';
        echo '--detector-accent-hover:' . esc_attr($this->darken_hex($accent, 15))   . ';';
        echo '}</style>';

        echo '<script>var mbrDetectorFlags=' . wp_json_encode([
            'darkMode'      => ($dark  === '1'),
            'glassmorphism' => ($glass === '1'),
        ]) . ';</script>';
    }

    /* =========================================================
       FRONTEND – enqueue plugin assets
    ========================================================= */
    public function enqueue_scripts() {
        if (is_admin()) return;
        wp_enqueue_style(
            'robs-robs-site-detector-styles',
            WP_SITE_DETECTOR_PLUGIN_URL . 'assets/css/detector.css',
            [],
            WP_SITE_DETECTOR_VERSION
        );
        wp_enqueue_script(
            'robs-robs-site-detector-script',
            WP_SITE_DETECTOR_PLUGIN_URL . 'assets/js/detector.js',
            ['jquery'],
            WP_SITE_DETECTOR_VERSION,
            true
        );
        wp_localize_script('robs-robs-site-detector-script', 'wpDetectorAjax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('wp_detector_nonce'),
        ]);
    }

    /* =========================================================
       SHORTCODE
    ========================================================= */
    public function render_detector_form($atts) {
        ob_start(); ?>
        <div class="wp-detector-container">
            <div class="wp-detector-form">
                <h3>WordPress Site Detector</h3>
                <p>Enter a website URL to check if it's built with WordPress and discover what theme and plugins it's using.</p>
                <form id="wp-detector-form">
                    <div class="form-group">
                        <input type="url" id="site-url" name="site_url" placeholder="https://example.com" required class="wp-detector-input">
                        <button type="submit" class="wp-detector-button">Detect</button>
                    </div>
                </form>
                <div id="wp-detector-loading" class="wp-detector-loading" style="display:none;">
                    <div class="spinner"></div>
                    <p>Analysing website…</p>
                </div>
                <div id="wp-detector-results" class="wp-detector-results" style="display:none;"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /* =========================================================
       AJAX
    ========================================================= */
    public function ajax_detect_site() {
        check_ajax_referer('wp_detector_nonce', 'nonce');
        $site_url = isset($_POST['site_url']) ? esc_url_raw(wp_unslash($_POST['site_url'])) : '';
        if (empty($site_url)) wp_send_json_error(['message' => 'Please provide a valid URL.']);
        wp_send_json_success($this->detect_wordpress($site_url));
    }

    /* =========================================================
       DETECTION LOGIC  (unchanged)
    ========================================================= */
    private function detect_wordpress($url) {
        $response = wp_remote_get($url, ['timeout' => 15, 'user-agent' => 'WordPress Site Detector', 'sslverify' => false]);
        if (is_wp_error($response)) return ['is_wordpress' => false, 'error' => 'Unable to fetch the website. Please check the URL and try again.'];
        $html    = wp_remote_retrieve_body($response);
        $headers = wp_remote_retrieve_headers($response);
        if (!$this->check_is_wordpress($html, $headers)) return ['is_wordpress' => false, 'message' => 'This website does not appear to be built with WordPress.'];
        return ['is_wordpress' => true, 'theme' => $this->detect_theme($html, $url), 'plugins' => $this->detect_plugins($html, $url), 'wp_version' => $this->detect_wp_version($html), 'url' => $url];
    }

    private function check_is_wordpress($html, $headers) {
        if (stripos($html, '/wp-content/')  !== false) return true;
        if (stripos($html, '/wp-includes/') !== false) return true;
        if (preg_match('/<meta name=["\']generator["\']\s+content=["\']WordPress/i', $html)) return true;
        if (isset($headers['link']) && stripos($headers['link'], 'wp-json') !== false) return true;
        return false;
    }

    private function detect_theme($html, $url) {
        if (preg_match('/\/wp-content\/themes\/([^\/\'"]+)/i', $html, $m)) {
            $slug = $m[1];
            $name = $this->get_theme_name($url, $slug);
            return ['name' => $name ?: ucwords(str_replace(['-','_'], ' ', $slug)), 'slug' => $slug, 'url' => 'https://wordpress.org/themes/'.$slug.'/'];
        }
        return ['name' => 'Unknown (Possibly a custom built theme)', 'slug' => 'unknown', 'url' => null];
    }

    private function get_theme_name($site_url, $slug) {
        $r = wp_remote_get(trailingslashit($site_url).'wp-content/themes/'.$slug.'/style.css', ['timeout' => 10, 'sslverify' => false]);
        if (!is_wp_error($r)) { $css = wp_remote_retrieve_body($r); if (preg_match('/Theme Name:\s*(.+)/i', $css, $m)) return trim($m[1]); }
        return null;
    }

    private function detect_plugins($html, $url) {
        $plugins = [];
        // Match slug and optionally capture ?ver= — note [^?] stops before query string so ver= can be captured
        if (preg_match_all('/\/wp-content\/plugins\/([^\/\'"]+)\/[^\'"\s>?]*(?:\?[^\'"\s>]*\bver=([\d.]+))?/i', $html, $m)) {
            $seen = [];
            for ($i = 0; $i < count($m[1]); $i++) {
                $slug    = $m[1][$i];
                $version = isset($m[2][$i]) && $m[2][$i] !== '' ? $m[2][$i] : null;
                // Keep entry if we haven't seen this slug, or if we now have a version and didn't before
                if (!isset($seen[$slug]) || ($version && !$seen[$slug]['version'])) {
                    $seen[$slug] = [
                        'name'    => ucwords(str_replace(['-','_'], ' ', $slug)),
                        'slug'    => $slug,
                        'version' => $version,
                        'url'     => 'https://wordpress.org/plugins/'.$slug.'/',
                    ];
                }
            }
            $plugins = array_values($seen);
        }
        return $plugins;
    }

    private function detect_wp_version($html) {
        if (preg_match('/<meta name=["\']generator["\']\s+content=["\']WordPress\s+([\d.]+)/i', $html, $m)) return $m[1];
        if (preg_match('/wp-includes\/[^"\']*\?ver=([\d.]+)/i', $html, $m)) return $m[1];
        return 'Unknown';
    }
}

function wp_site_detector_init() { return WP_Site_Detector::get_instance(); }
add_action('plugins_loaded', 'wp_site_detector_init');
