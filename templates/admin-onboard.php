<div class="wrap">
    <h1><?= __('Plugin Setup: Encryption Key Required', 'reactsmith'); ?></h1>
    <p><?= __('To securely store your Instagram App Secret and Access Tokens, you must define a unique encryption key in your wp-config.php file.', 'reactsmith'); ?></p>

    <div class="notice notice-warning inline">
        <p><strong><?= __('Important:', 'reactsmith'); ?></strong> <?= __('This key is essential for encrypting sensitive data. If you lose this key or change it later, all currently stored secrets will become unreadable and must be re-entered.', 'reactsmith'); ?></p>
    </div>

    <h3><?= __('Instructions:', 'reactsmith'); ?></h3>
    <ol>
        <li><?= __('Open your ', 'reactsmith'); ?> <code>wp-config.php</code> <?= __('file located in your WordPress root directory.', 'reactsmith'); ?></li>
        <li><?= __('Locate the line that says:', 'reactsmith'); ?> <br><code>/* That's all, stop editing! Happy publishing. */</code></li>
        <li><?= __('Paste the following line right <b>ABOVE</b> it:', 'reactsmith'); ?></li>
    </ol>

    <div style="background: #f0f0f0; padding: 15px; border-left: 4px solid #0073aa; font-family: monospace; margin: 20px 0; position: relative;">
        <code>define('RS_SECRET_ENCRYPTION_KEY', '<?= \esc_html($data['suggested_key']); ?>');</code>
    </div>

    <p>
        <button class="button button-secondary" onclick="copyToClipboard('define(\'RS_SECRET_ENCRYPTION_KEY\', \'<?php echo esc_js($data['suggested_key']); ?>\');',this)">
            <?= __('Copy to Clipboard', 'reactsmith'); ?>
        </button>
    </p>

    <p>
        <a href="<?= \esc_url($actions['refresh_url']); ?>" class="button button-primary">
            <?= __('I have added the key, check again', 'reactsmith'); ?>
        </a>
    </p>

    <script>
        function copyToClipboard(text, btn) {
            const el = document.createElement('textarea');
            el.value = text;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            const originalText = btn.innerHTML;
            btn.innerHTML = '<?= esc_js(__('Copied! ✅', 'reactsmith')); ?>';

            setTimeout(function() {
                btn.innerHTML = originalText;
            }, 5000);
        }
    </script>
</div>