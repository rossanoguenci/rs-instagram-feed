# ReactSmith Instagram Feed

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

A robust and secure WordPress plugin to retrieve and display Instagram posts, specifically designed for the ReactSmith theme.

## Features

- **Secure OAuth Integration**: Connect your Instagram Business account easily using the Meta Graph API.
- **Automated Synchronization**: Hourly cron jobs to keep your feed up-to-date automatically.
- **Credential Encryption**: API secrets are encrypted using AES-256-CBC with a key defined in your `wp-config.php`.
- **Theme-Integrated Components**: Seamless integration with the ReactSmith theme's component architecture.
- **Performance Optimized**: Weekly automated cleanup of old posts to maintain a lean database.
- **Onboarding Workflow**: Streamlined setup process for easy configuration.

## Requirements

- **WordPress**: 6.0 or higher.
- **PHP**: 8.0 or higher.
- **Theme**: [ReactSmith Theme](https://github.com/reactsmith/theme) (must be active).
- **Plugins**: [Smart Custom Fields (SCF)](https://wordpress.org/plugins/smart-custom-fields/) or [Advanced Custom Fields (ACF)](https://wordpress.org/plugins/advanced-custom-fields/).

## Installation

1. Upload the `rs-instagram-feed` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. **Critical Step**: You MUST define a secure encryption key in your `wp-config.php` file to encrypt your Meta API credentials:

```php
/** Encryption key for ReactSmith Instagram Feed */
define('RS_SECRET_ENCRYPTION_KEY', 'your-random-high-entropy-string-here');
```

*Note: For maximum security, you can use a base64-encoded 32-byte key prefixed with `base64:`. If a raw string is provided, it will be automatically hashed (SHA-256) to 32 bytes.*

## Configuration

1. Navigate to **Instagram Feed** in the WordPress admin menu.
2. If you haven't set the `RS_SECRET_ENCRYPTION_KEY`, you will be prompted with instructions on how to do so.
3. Go to **Account Connection** and enter your **Meta App ID** and **Meta App Secret**.
4. Click **Connect with Meta** to authorize your Instagram Business account.
5. Once connected, you can configure the maximum number of posts and sync settings in the **Settings** tab.

## Development

To set up the development environment:

1. Clone the repository into your WordPress plugins directory.
2. Install dependencies:
   ```bash
   composer install
   pnpm install
   ```
3. Run tests:
   ```bash
   # Run all tests
   pnpm test

   # Run unit tests only
   pnpm test:unit
   ```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Author

**Rossano Guenci**
