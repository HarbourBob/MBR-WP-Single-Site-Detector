# MBR WP Site Detector

Contributors: Robert Palmer
Tags: wp, themes, detector, plugins
Stable tag: 1.5.8
Tested up to: 6.8
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A plugin that allows visitors to check if any website is built with WordPress and discover what theme and plugins it's using.

## Plugin URI 

https://littlewebshack.com/

## Features

- **WordPress Detection**: Identifies if a website is running WordPress
- **Theme Detection**: Discovers the active WordPress theme
- **Plugin Detection**: Lists visible plugins (those with frontend assets)
- **WordPress Version**: Detects the WordPress version being used
- **Buttons**: Link to themes and plugins
- **User-Friendly Interface**: Clean, responsive design
- **AJAX-Powered**: Fast detection without page reloads
- **Security**: Proper nonce verification and data sanitization

## Installation

1. Download the plugin files
2. Upload the entire plugin folder to `/wp-content/plugins/`
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Add the shortcode `[wp_detector]` to any page or post

## Usage

### Using the Shortcode

Add the shortcode to any page or post where you want the detector to appear:

```
[wp_detector]
```

### For Visitors

1. Enter a website URL (e.g., https://example.com)
2. Click the "Detect" button
3. View the results showing:
   - Whether the site is WordPress
   - WordPress version
   - Active theme
   - Detected plugins

## File Structure

```
wordpress-detector/
├── wordpress-detector.php    # Main plugin file
├── assets/
│   ├── css/
│   │   └── detector.css     # Styling
│   └── js/
│       └── detector.js      # AJAX functionality
└── README.md                 # Documentation
```

## Technical Details

### Detection Methods

**WordPress Detection:**
- Checks for `/wp-content/` paths
- Looks for `/wp-includes/` references
- Identifies WordPress meta generator tags
- Checks for wp-json API endpoints

**Theme Detection:**
- Parses theme paths in stylesheets
- Attempts to read theme name from style.css

**Plugin Detection:**
- Identifies plugin paths in HTML source
- Only detects plugins that load frontend assets

### Limitations

- Cannot detect all plugins (only those with frontend footprints)
- Some WordPress sites hide identifying information for security
- Blocked by CORS and firewall restrictions
- Security plugins may obscure WordPress signatures

## Security

The plugin includes:
- WordPress nonce verification
- URL sanitization with `esc_url_raw()`
- Input validation
- XSS protection in JavaScript
- Secure AJAX handling

## Compatibility

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Works with all modern browsers
- Mobile responsive
   
## Support

For support, please visit: https://robertp419.sg-host.com/wordpress/

## Author

Robert Palmer

## Changelog

### Version 1.5.8
- Minor bug fixes

### Version 1.5.7
- Initial release
- WordPress detection
- Theme identification
- Plugin discovery
- WordPress version detection
- Responsive design
- AJAX-powered interface
