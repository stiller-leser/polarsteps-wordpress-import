<?php
class Polarsteps_Importer_Core {

    public function __construct() {
        // Cron-Job-Hooks
        add_filter('cron_schedules', ['Polarsteps_Importer_Cron', 'add_custom_cron_interval']);
        add_action('polarsteps_importer_cron_hook', ['Polarsteps_Importer_Process', 'run'], 10, 1);
        add_action('polarsteps_importer_manual_hook', ['Polarsteps_Importer_Process', 'run'], 10, 1);

        // Admin-Hooks
        add_action('admin_post_polarsteps_importer_run_now', [$this, 'handle_run_now']);
        add_action('admin_post_polarsteps_importer_delete_posts', [$this, 'handle_delete_posts']);
        add_action('admin_post_polarsteps_importer_convert_gallery', [$this, 'handle_convert_gallery']);
        add_action('admin_post_polarsteps_importer_convert_images', [$this, 'handle_convert_images']);
    }

    /**
     * Behandelt den "Jetzt importieren"-Button.
     */
    public function handle_run_now() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'polarsteps-importer'));
        }
        check_admin_referer('polarsteps_importer_run_now');

        // Plane einen dedizierten, einmaligen Job für den manuellen Import.
        wp_schedule_single_event(time(), 'polarsteps_importer_manual_hook', [['manual' => true]]);
        
        Polarsteps_Importer_Settings::log_message(__('Manual import triggered.', 'polarsteps-importer'));

        wp_redirect(admin_url('options-general.php?page=polarsteps-importer&manual_run_triggered=1'));
        exit;
    }

    /**
     * Handles the deletion of selected posts and optionally their media.
     */
    public function handle_delete_posts() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'polarsteps-importer'));
        }

        $post_ids = [];
        $delete_media = false;

        // Check for Single Delete via GET
        if (isset($_GET['post_id'])) {
            check_admin_referer('polarsteps_importer_delete_post_' . $_GET['post_id']);
            $post_ids[] = intval($_GET['post_id']);
            $delete_media = isset($_GET['delete_media']) && $_GET['delete_media'] === '1';
        } 
        // Check for Bulk Delete via POST
        else {
            check_admin_referer('polarsteps_importer_delete_posts_nonce');
            $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
            $delete_media = isset($_POST['delete_media']) && $_POST['delete_media'] === '1';
        }

        $deleted_count = 0;

        foreach ($post_ids as $post_id) {
            // Security check: ensure this post is actually a Polarsteps import
            // Although the checkbox list is generated from them, a malicious user could spoof IDs.
            $step_id = get_post_meta($post_id, '_polarsteps_step_id', true);
            if (!$step_id) {
                continue; // Skip posts that are not Polarsteps steps
            }

            if ($delete_media) {
                // Get attachments
                $attachments = get_children([
                    'post_parent' => $post_id,
                    'post_type'   => 'attachment',
                ]);
                
                foreach ($attachments as $attachment) {
                    wp_delete_attachment($attachment->ID, true);
                }
            }

            // Force delete the post
            $result = wp_delete_post($post_id, true);
            if ($result) {
                $deleted_count++;
            }
        }

        Polarsteps_Importer_Settings::log_message(sprintf(__('Deleted %d posts via management interface.', 'polarsteps-importer'), $deleted_count));

        wp_redirect(admin_url('options-general.php?page=polarsteps-importer&posts_deleted=' . $deleted_count));
        exit;
    }

    /**
     * Handles the conversion of embedded media to a gallery for selected posts.
     */
    public function handle_convert_gallery() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'polarsteps-importer'));
        }

        $post_ids = [];
        
        // Check for Single Convert via GET
        if (isset($_GET['post_id'])) {
            check_admin_referer('polarsteps_importer_convert_gallery_' . $_GET['post_id']);
            $post_ids[] = intval($_GET['post_id']);
        }
        // Check for Bulk Convert via POST
        else {
            check_admin_referer('polarsteps_importer_delete_posts_nonce'); // We reuse the nonce from the form
             $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
        }

        $converted_count = 0;

        foreach ($post_ids as $post_id) {
            $step_id = get_post_meta($post_id, '_polarsteps_step_id', true);
            if (!$step_id) {
                continue;
            }

            // 1. Get all attachments for this post
            $attachments = get_children([
                'post_parent' => $post_id,
                'post_type'   => 'attachment',
                'fields'      => 'ids',
            ]);

            if (empty($attachments)) {
                continue;
            }

            // 2. Get Post Content
            $post = get_post($post_id);
            $content = $post->post_content;

            // 3. Remove existing <img> and <video> tags
            // Note: This is a broad removal, assuming most images/videos in these posts are the imported ones.
            // If the user manually added other images, they might be removed too if they match.
            // But since these are automated posts, it's safer.
            
            // Remove images
            $content = preg_replace('/<img[^>]+>/i', '', $content);
            
            // Remove videos
            $content = preg_replace('/<video[^>]*>.*?<\/video>/is', '', $content);
            
            // Remove empty paragraphs that might be left behind
            // $content = preg_replace('/<p>\s*<\/p>/i', '', $content); // Optional cleanup

            // 4. Append Gallery Shortcode
            $gallery_shortcode = '[gallery link="file" ids="' . implode(',', $attachments) . '"]';
            $content .= "\n\n" . $gallery_shortcode;

            // 5. Update Post
            wp_update_post([
                'ID'           => $post_id,
                'post_content' => $content,
            ]);

            // 6. Set Featured Image (Thumbnail) if not set
             if (post_type_supports(get_post_type($post_id), 'thumbnail') && !has_post_thumbnail($post_id)) {
                 $first_attachment_id = $attachments[0]; // attachments are order by date ASC by default or menu_order?
                 // Usually get_children returns by post_date DESC? No, menu_order ASC, post_date DESC.
                 // Let's just pick the first one found.
                 $first_attachment_mime = get_post_mime_type($first_attachment_id);
                 if (strpos($first_attachment_mime, 'image/') === 0) {
                    set_post_thumbnail($post_id, $first_attachment_id);
                 }
             }

            $converted_count++;
        }

        Polarsteps_Importer_Settings::log_message(sprintf(__('Converted %d posts to gallery via management interface.', 'polarsteps-importer'), $converted_count));

        wp_redirect(admin_url('options-general.php?page=polarsteps-importer&posts_converted=' . $converted_count));
        exit;
    }

    /**
     * Handles the conversion of galleries back to individual images/videos.
     */
    public function handle_convert_images() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to perform this action.', 'polarsteps-importer'));
        }

        $post_ids = [];
        
        // Check for Single Convert via GET
        if (isset($_GET['post_id'])) {
            check_admin_referer('polarsteps_importer_convert_images_' . $_GET['post_id']);
            $post_ids[] = intval($_GET['post_id']);
        }
        // Check for Bulk Convert via POST
        else {
            check_admin_referer('polarsteps_importer_delete_posts_nonce');
             $post_ids = isset($_POST['post_ids']) ? array_map('intval', $_POST['post_ids']) : [];
        }

        $converted_count = 0;

        foreach ($post_ids as $post_id) {
            $step_id = get_post_meta($post_id, '_polarsteps_step_id', true);
            if (!$step_id) {
                continue;
            }

            $post = get_post($post_id);
            $content = $post->post_content;

            // Find gallery shortcode
            if (preg_match('/\[gallery[^\]]*ids="([^"]+)"[^\]]*\]/', $content, $matches)) {
                $ids = explode(',', $matches[1]);
                $media_html = '';

                foreach ($ids as $id) {
                    $id = (int) $id;
                    $mime = get_post_mime_type($id);
                    $url = wp_get_attachment_url($id);

                    if (!$url) continue;

                    if (strpos($mime, 'image/') === 0) {
                        // Create img tag
                         $img_tag = wp_get_attachment_image($id, 'full', false, ['class' => 'alignnone size-full']);
                         $media_html .= "\n" . $img_tag;
                    } elseif (strpos($mime, 'video/') === 0) {
                        // Create video tag
                        $media_html .= "\n" . sprintf(
                            '<video src="%s" controls class="wp-video-shortcode" width="100%%"></video>',
                            esc_url($url)
                        );
                    }
                }

                // Replace the gallery shortcode with the media HTML
                // Use the full match as search string
                $content = str_replace($matches[0], $media_html, $content);

                wp_update_post([
                    'ID'           => $post_id,
                    'post_content' => $content,
                ]);

                $converted_count++;
            }
        }

        Polarsteps_Importer_Settings::log_message(sprintf(__('Converted %d posts to images via management interface.', 'polarsteps-importer'), $converted_count));

        wp_redirect(admin_url('options-general.php?page=polarsteps-importer&posts_converted_images=' . $converted_count));
        exit;
    }
}