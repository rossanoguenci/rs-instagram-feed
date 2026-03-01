<style>
    .button.button-danger{
        color:red;
        border-color:red;
    }
</style>

<div class="wrap">
    <h1><?= \get_admin_page_title(); ?></h1>
    <hr>
    <!-- Step 1: App Credentials -->
    <h3>App Credentials</h3>
    <form method="post" action="<?= $actions['edit_credentials_url'] ?>" autocomplete="off">
        <?php \wp_nonce_field('rs_instagram_feed_settings_action', 'rs_instagram_feed_settings_nonce'); ?>
        <input type="hidden" id="redirect_uri" name="redirect_uri" value="<?= $actions['this_admin_page_url'] ?>">
        <table class="form-table">
            <tr>
                <th scope="row"><label for="rs_instagram_feed_app_id">App ID</label></th>
                <td>
                    <input name="rs_instagram_feed_app_id" type="text" id="rs_instagram_feed_app_id"
                           value="<?= esc_attr($data['app_id']); ?>"
                           class=""
                           <?= ($data['has_credentials'] && !$data['is_editing_credentials']) ? 'disabled' : ''; ?>
                           autocomplete="off"
                   >
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="rs_instagram_feed_app_secret">App Secret</label></th>
                <td>
                    <input name="rs_instagram_feed_app_secret" type="password" id="rs_instagram_feed_app_secret"
                           value="<?= $data['app_secret'] ?>"
                           class=""
                           <?= ($data['has_credentials'] && !$data['is_editing_credentials']) ? 'disabled' : ''; ?>
                           autocomplete="new-password"
                   >
                </td>
            </tr>
        </table>

        <?php if (!$data['has_credentials'] || $data['is_editing_credentials']) : ?>
            <p class="submit">
                <input type="submit" name="rs_instagram_feed_save_credentials" id="submit" class="button button-primary" value="Save Credentials">
                <?php if($data['is_editing_credentials']) : ?>
                    <a href="<?= $actions['this_admin_page_url'] ?>" class="button button-secondary">Cancel</a>
                <?php endif; ?>
            </p>
        <?php else : ?>
            <p>
                <a href="<?= $actions['edit_credentials_url'] ?>" class="button">Change Credentials</a>
                <a href="<?= $actions['clear_connection_url'] ?>" class="button button-danger">Clear Data</a>
            </p>
        <?php endif; ?>
    </form>

    <hr>

    <!-- Step 2: Connection Status -->
    <?php if ($data['has_credentials'] && !$data['is_editing_credentials']) : ?>
        <h3>Connection Status</h3>
        <?php if ($data['has_connection']) : ?>
            <p style="color: green; font-weight: bold;">
                <span class="dashicons dashicons-yes-alt"></span> Connected
            </p>
            <a href="<?= $actions['revoke_access_url'] ?>" class="button button-danger">Revoke Access</a>
        <?php else : ?>
            <p>
                <?php
                    $btn_text = !$data['is_app_token_set'] ? "Connect FB account" : "Reconnect FB account";
                ?>
                <a href="<?= $actions['meta_login_url'] ?>" class="button button-primary"><?= $btn_text; ?></a>
            </p>
        <?php endif; ?>

    <?php endif; ?>

</div>