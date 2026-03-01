<style>
    .button.button-danger{
        color:red;
        border-color:red;
    }
</style>

<div class="wrap">
    <h1><?= \get_admin_page_title(); ?></h1>
    <hr>

    <?php if (isset($data['has_credentials']) && $data['has_credentials']) : ?>
        <h3>Feed Information</h3>
        <table class="form-table">
            <tr>
                <th scope="row">Number of latest posts to save</th>
                <td>
                    <form method="post" action="#" autocomplete="off">
                        <?php \wp_nonce_field('rs_instagram_feed_max_posts_number', 'rs_instagram_feed_settings_nonce'); ?>
                        <select name="rs_instagram_feed_max_posts_number">
                            <?php for($i=1; $i<=10; $i++): ?>
                                <option value="<?= $i ?>" <?= $i === $data['max_posts'] ? 'selected' : '' ?>><?= $i ?></option>
                            <?php endfor; ?>
                        </select>
                        <button class="button" type="submit" name="rs_instagram_feed_max_posts_number_save">Save</button>
                    </form>
                </td>
            </tr>
            <tr>
                <th scope="row">Last sync performed</th>
                <td><?= $data['last_sync']; ?></td>
            </tr>

            <tr>
                <th scope="row">Next scheduled sync</th>
                <td><?= $data['next_sync']?></td>
            </tr>

        </table>

        <?php if(isset($data['has_connection']) && $data['has_connection']): ?>
            <hr>

            <h3>Actions</h3>
            <div class="sync-controls">

                <p>
                    <!-- Manual Sync -->
                    <a href="<?= $actions['manual_sync_url'] ?>" class="button button-secondary">Sync Manually</a>
                </p>

                <p>
                <!-- Start / Pause -->
                <?php if ($data['is_scheduled']) : ?>
                    <a href="<?= $actions['pause_sync_url'] ?>" class="button button-secondary">Pause Auto-Sync</a>
                <?php else : ?>
                    <a href="<?= $actions['start_sync_url'] ?>" class="button button-secondary">Start Auto-Sync</a>
                <?php endif; ?>
                </p>

                <p>
                    <!-- Drop posts -->
                    <a href="<?= $actions['drop_feed_url'] ?>" class="button button-danger">Drop Feed</a>
                </p>

            </div>
        <?php endif; ?>

    <?php else : ?>

        <h3>️⚠️ No connection ⚠️</h3>
        <p>The connection to the Instagram profile is not available.</p>
        <p>Please contact the Network Admin.</p>

    <?php endif; ?>

</div>