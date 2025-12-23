<?php
/**
 * View for the Polarsteps Importer Settings Page.
 *
 * Variables available:
 * @var array $logs Array of log messages.
 * @var array $posts Array of imported post objects.
 * @var bool $manual_run_triggered Whether a manual run was just triggered.
 * @var bool $import_completed Whether an import was successfully completed.
 * @var bool $logs_cleared Whether logs were just cleared.
 * @var int|null $posts_deleted Number of posts deleted, or null.
 * @var int|null $posts_converted Number of posts converted, or null.
 * @var int $paged Current page number.
 * @var int $total_pages Total number of pages.
 * @var int $total_posts Total number of posts.
 * @var int $posts_per_page Number of posts per page.
 * @var string $orderby Current order by.
 * @var string $order Current sort order.
 */

if (!defined('ABSPATH')) {
    exit;
}

// Helper for sortable links
$base_url = admin_url('options-general.php?page=polarsteps-importer');
$get_sort_link = function($column_key, $label) use ($base_url, $orderby, $order) {
    $new_order = ($orderby === $column_key && $order === 'DESC') ? 'ASC' : 'DESC';
    $arrow = ($orderby === $column_key) ? ($order === 'DESC' ? ' &#9660;' : ' &#9650;') : '';
    $url = add_query_arg(['orderby' => $column_key, 'order' => $new_order], $base_url);
    return '<a href="' . esc_url($url) . '">' . esc_html($label) . $arrow . '</a>';
};

$current_url = set_url_scheme('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
?>

<div class="wrap">
    <h1><?php esc_html_e('Polarsteps Importer', 'polarsteps-importer'); ?></h1>

    <?php if ($logs_cleared) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('Logs have been cleared.', 'polarsteps-importer'); ?></p></div>
    <?php endif; ?>

    <?php if ($manual_run_triggered) : ?>
        <div class="notice notice-info is-dismissible"><p><?php esc_html_e('The import has been started in the background. The logs will be updated shortly.', 'polarsteps-importer'); ?></p></div>
    <?php endif; ?>

    <?php if ($import_completed) : ?>
        <div class="notice notice-success is-dismissible"><p><?php esc_html_e('The manual import has been successfully completed.', 'polarsteps-importer'); ?></p></div>
    <?php endif; ?>

    <!-- Formular für Einstellungen -->
    <form method="post" action="options.php">
        <?php
        settings_fields('polarsteps_importer');
        do_settings_sections('polarsteps-importer');
        submit_button(__('Save Settings', 'polarsteps-importer'), 'primary', 'submit', true, ['style' => 'margin-top: 20px;']);
        ?>
    </form>

    <hr style="margin-top: 20px;">

    <!-- Manueller Import -->
    <h3><?php esc_html_e('Manual Import', 'polarsteps-importer'); ?></h3>
    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="polarsteps_importer_run_now">
        <?php wp_nonce_field('polarsteps_importer_run_now'); ?>
        <?php submit_button(__('Import Now', 'polarsteps-importer'), 'secondary', 'submit', false); ?>
    </form>


    <hr>
    <details id="polarsteps-imported-posts-details" open>
        <summary><h3 style="display:inline-block; margin-top:0;"><?php esc_html_e('Imported Posts', 'polarsteps-importer'); ?></h3></summary>
        <div style="margin-top: 15px;">
    <?php
    if (empty($posts) && $paged === 1) {
        echo '<p>' . esc_html__('No imported posts found.', 'polarsteps-importer') . '</p>';
    } else {
        if ($posts_deleted !== null) {
             echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('%d posts deleted.', 'polarsteps-importer'), $posts_deleted) . '</p></div>';
        }
        if ($posts_converted !== null) {
             echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('%d posts converted to gallery.', 'polarsteps-importer'), $posts_converted) . '</p></div>';
        }
        if ($posts_converted_images !== null) {
             echo '<div class="notice notice-success is-dismissible"><p>' . sprintf(esc_html__('%d galleries converted to images.', 'polarsteps-importer'), $posts_converted_images) . '</p></div>';
        }
        ?>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="polarsteps_importer_delete_posts">
            <?php wp_nonce_field('polarsteps_importer_delete_posts_nonce'); ?>
            
            <div class="tablenav top">
                <div class="alignleft actions">
                    <label for="posts_per_page" style="float: left; margin-right: 5px; line-height: 28px;"><?php esc_html_e('Items per page:', 'polarsteps-importer'); ?></label>
                    <select name="posts_per_page" id="posts_per_page" onchange="window.location.href=this.value;">
                        <?php 
                        $limits = [10, 25, 50, 100];
                        foreach ($limits as $limit) {
                            $selected = ($posts_per_page == $limit) ? 'selected' : '';
                            $url = add_query_arg(['posts_per_page' => $limit, 'paged' => 1], $base_url);
                            echo '<option value="' . esc_url($url) . '" ' . $selected . '>' . $limit . '</option>';
                        }
                        // All option
                        $selected_all = ($posts_per_page == -1) ? 'selected' : '';
                        $url_all = add_query_arg(['posts_per_page' => -1, 'paged' => 1], $base_url);
                        echo '<option value="' . esc_url($url_all) . '" ' . $selected_all . '>' . esc_html__('All', 'polarsteps-importer') . '</option>';
                        ?>
                    </select>
                </div>
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php printf(esc_html__('%d items', 'polarsteps-importer'), $total_posts); ?></span>
                    <?php
                    $page_links = paginate_links([
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $total_pages,
                        'current' => $paged
                    ]);

                    if ($page_links) {
                        echo '<span class="pagination-links">' . $page_links . '</span>';
                    }
                    ?>
                </div>
                <br class="clear">
            </div>

            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <td id="cb" class="manage-column column-cb check-column"><input type="checkbox" id="cb-select-all-1"></td>
                        <th><?php echo $get_sort_link('title', __('Title', 'polarsteps-importer')); ?></th>
                        <th><?php echo $get_sort_link('date', __('Date', 'polarsteps-importer')); ?></th>
                        <th><?php esc_html_e('Step ID', 'polarsteps-importer'); ?></th>
                        <th><?php esc_html_e('Status', 'polarsteps-importer'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts as $post): 
                        $step_id = get_post_meta($post->ID, '_polarsteps_step_id', true);
                        
                        // URLs for row actions
                        $delete_url = wp_nonce_url(
                            admin_url('admin-post.php?action=polarsteps_importer_delete_posts&post_id=' . $post->ID . '&delete_media=1'),
                            'polarsteps_importer_delete_post_' . $post->ID
                        );
                        
                        $convert_url = wp_nonce_url(
                            admin_url('admin-post.php?action=polarsteps_importer_convert_gallery&post_id=' . $post->ID),
                            'polarsteps_importer_convert_gallery_' . $post->ID
                        );

                        $convert_images_url = wp_nonce_url(
                            admin_url('admin-post.php?action=polarsteps_importer_convert_images&post_id=' . $post->ID),
                            'polarsteps_importer_convert_images_' . $post->ID
                        );
                    ?>
                    <tr>
                        <th scope="row" class="check-column"><input type="checkbox" name="post_ids[]" value="<?php echo esc_attr($post->ID); ?>"></th>
                        <td class="has-row-actions column-primary">
                            <strong><a href="<?php echo get_edit_post_link($post->ID); ?>"><?php echo esc_html($post->post_title); ?></a></strong>
                            <div class="row-actions">
                                <span class="view"><a href="<?php echo get_permalink($post->ID); ?>" aria-label="<?php esc_attr_e('View', 'polarsteps-importer'); ?>"><?php esc_html_e('View', 'polarsteps-importer'); ?></a> | </span>
                                <span class="delete"><a href="<?php echo esc_url($delete_url); ?>" class="submitdelete" onclick="return confirm('<?php echo esc_js(__('Are you sure you want to delete this post?', 'polarsteps-importer')); ?>');"><?php esc_html_e('Delete', 'polarsteps-importer'); ?></a> | </span>
                                <span class="edit"><a href="<?php echo esc_url($convert_url); ?>" onclick="return confirm('<?php echo esc_js(__('Convert this post to gallery?', 'polarsteps-importer')); ?>');"><?php esc_html_e('Convert to Gallery', 'polarsteps-importer'); ?></a> | </span>
                                <span class="edit"><a href="<?php echo esc_url($convert_images_url); ?>" onclick="return confirm('<?php echo esc_js(__('Convert gallery to images?', 'polarsteps-importer')); ?>');"><?php esc_html_e('Convert to Images', 'polarsteps-importer'); ?></a></span>
                            </div>
                        </td>
                        <td><?php echo get_the_date('', $post->ID); ?></td>
                        <td><?php echo esc_html($step_id); ?></td>
                        <td><?php echo esc_html(get_post_status_object($post->post_status)->label); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
             <div class="tablenav bottom">
                <div class="alignleft actions bulkactions">
                    <?php submit_button(__('Convert to Gallery', 'polarsteps-importer'), 'button', 'convert_gallery', false, [
                        'onclick' => "return confirm('" . esc_js(__('Are you sure you want to convert the selected posts?', 'polarsteps-importer')) . "');",
                        'formaction' => admin_url('admin-post.php?action=polarsteps_importer_convert_gallery')
                    ]); ?>
                    <?php submit_button(__('Convert to Images', 'polarsteps-importer'), 'button', 'convert_images', false, [
                        'onclick' => "return confirm('" . esc_js(__('Are you sure you want to convert the selected posts?', 'polarsteps-importer')) . "');",
                        'formaction' => admin_url('admin-post.php?action=polarsteps_importer_convert_images')
                    ]); ?>
                    <?php submit_button(__('Delete Selected', 'polarsteps-importer'), 'button', 'delete_posts', false, ['onclick' => "return confirm('" . esc_js(__('Are you sure you want to delete the selected posts?', 'polarsteps-importer')) . "');"]); ?>
                    <label style="margin-right: 5px;">
                        <input type="checkbox" name="delete_media" value="1"> 
                        <?php esc_html_e('Also delete attached media', 'polarsteps-importer'); ?>
                    </label>
                </div>
                <div class="tablenav-pages">
                    <span class="displaying-num"><?php printf(esc_html__('%d items', 'polarsteps-importer'), $total_posts); ?></span>
                    <?php
                    if ($page_links) {
                        echo '<span class="pagination-links">' . $page_links . '</span>';
                    }
                    ?>
                </div>
                <br class="clear">
            </div>

            <script>
                jQuery(document).ready(function($) {
                    $('#cb-select-all-1').click(function() {
                        $('input[name="post_ids[]"]').prop('checked', this.checked);
                    });
                });
            </script>
        </form>
        <?php
    }
    ?>
        </div>
    </details>
    <hr>
    <details id="polarsteps-debug-logs-details">
        <summary><h3 style="display:inline-block; margin-top:0;"><?php esc_html_e('Debug Logs', 'polarsteps-importer'); ?></h3></summary>
        <div style="margin-top: 15px;">
    <?php
    if (empty($logs)) {
        echo '<p>' . esc_html__('No logs found. Run an import to generate logs.', 'polarsteps-importer') . '</p>';
    } else {
        // Search Input
        echo '<input type="text" id="log-search-input" placeholder="' . esc_attr__('Search logs...', 'polarsteps-importer') . '" style="width:100%; max-width:600px; margin-bottom:10px; padding:8px;">';

        echo '<div id="log-container" style="max-height: 400px; overflow-y: auto; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; font-family: monospace;">';
        foreach (array_reverse($logs) as $log_line) {
             echo '<div class="log-entry" style="border-bottom: 1px solid #eee; padding: 2px 0;">' . esc_html($log_line) . '</div>';
        }
        echo '</div>';

        // Lösch-Button für Logs
        echo '<p class="submit" style="margin-top: 10px; padding: 0;">';
        echo '<form method="post" action="' . esc_url(admin_url('options-general.php?page=polarsteps-importer')) . '" style="display: inline-block; margin-right: 10px;">';
        wp_nonce_field('polarsteps_importer_clear_logs_nonce', 'polarsteps_importer_clear_logs_nonce');
        submit_button(__('Clear Logs', 'polarsteps-importer'), 'delete', 'clear_logs', false);
        echo '</form>';
        printf('<a href="%s" class="button">%s</a>', esc_url(admin_url('options-general.php?page=polarsteps-importer')), esc_html__('Refresh Logs', 'polarsteps-importer'));
        echo '</p>';

        // JS for log search
        ?>
        <script>
        jQuery(document).ready(function($) {
            // Log Search
            $('#log-search-input').on('keyup', function() {
                var value = $(this).val().toLowerCase();
                $('#log-container .log-entry').filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });
        </script>
        <?php
    }
    ?>
    </div>
    </details>
</div>

<script>
jQuery(document).ready(function($) {
    // Accordion State Persistence
    var detailsElements = $('details');
    detailsElements.each(function() {
        var id = $(this).attr('id');
        if (id) {
            var state = localStorage.getItem(id);
            if (state === 'open') {
                $(this).attr('open', true);
            } else if (state === 'closed') {
                $(this).removeAttr('open');
            }
            
            $(this).on('toggle', function() {
                var isOpen = $(this).prop('open');
                localStorage.setItem(id, isOpen ? 'open' : 'closed');
            });
        }
    });
});
</script>
