<?php
class Polarsteps_Importer_API {

    public static function fetch_steps($trip_id, $remember_token, $debug = false) {
        $api_url = "https://api.polarsteps.com/trips/{$trip_id}";

        $args = [
            'headers' => [
                'User-Agent'  => 'PolarstepsClient/1.0',
                'Accept'      => 'application/json',
                'Content-Type'=> 'application/json',
                'Cookie'      => "remember_token={$remember_token}",
            ],
        ];

        $response = wp_remote_get($api_url, $args);

        if (is_wp_error($response)) {
            Polarsteps_Importer_Settings::log_message(sprintf(__('API Error: %s', 'polarsteps-importer'), $response->get_error_message()));
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode(trim($body), true);

        if (is_null($data)) {
            Polarsteps_Importer_Settings::log_message(sprintf(__('Could not parse API response: %s', 'polarsteps-importer'), json_last_error_msg()));
        }

        if ($debug) {
            Polarsteps_Importer_Settings::log_message(__('Debug mode active. Data received:', 'polarsteps-importer') . print_r($data, true));
            
            $masked_token = substr($remember_token, 0, 3) . str_repeat('*', strlen($remember_token) - 3);
            $args_for_log = $args;
            $args_for_log['headers']['Cookie'] = "remember_token={$masked_token}";
            Polarsteps_Importer_Settings::log_message(sprintf(__('API Request to: %1$s | Args: %2$s', 'polarsteps-importer'), $api_url, print_r($args_for_log, true)));
            Polarsteps_Importer_Settings::log_message(__('API Response (raw):', 'polarsteps-importer') . substr($body, 0, 500) . (strlen($body) > 500 ? '...[truncated]' : ''));
            Polarsteps_Importer_Settings::log_message(__('Data:', 'polarsteps-importer') . print_r($data, true));
        }
        if (empty($data['all_steps'])) {
            Polarsteps_Importer_Settings::log_message('Keine Steps gefunden oder ungültige Antwort: ' . print_r($data, true));
            return false;
        }

        if ($debug) {
            Polarsteps_Importer_Settings::log_message(__('Steps found:', 'polarsteps-importer') . print_r($data['all_steps'], true));
        }

        return $data['all_steps'];
    }

    public static function step_exists($step_id, $post_type = 'post') {
        $args = [
            'post_type'      => $post_type,
            'post_status'    => ['publish', 'pending', 'draft', 'future', 'private'],
            'posts_per_page' => 1,
            'meta_key'       => '_polarsteps_step_id',
            'meta_value'     => $step_id,
            'fields'         => 'ids',
            'suppress_filters' => true, // Wichtig für die Performance bei vielen Posts
            'cache_results'  => false,  // Deaktiviert den Cache für diese Abfrage
        ];

        $posts = get_posts($args);
        return !empty($posts);
    }

    public static function import_step_photos($media, $post_id, $mode = 'gallery', $step_title = '') {
        $gallery_ids = [];
        $image_html = '';

        if (!empty($media)) {
            $image_counter = 1;
            foreach ($media as $item) {
                if (!empty($item['large_thumbnail_path'])) {
                    $image_url = $item['large_thumbnail_path'];
                    $new_image_title = $step_title;
                    if (count($media) > 1) {
                        $new_image_title .= ' ' . $image_counter;
                    }

                    // Check if an image with the same title already exists to prevent duplicates.
                    $attachment_id = self::find_existing_image_by_title($new_image_title);

                    if ($attachment_id) {
                        Polarsteps_Importer_Settings::log_message(sprintf(__('Reusing existing image "%1$s" with ID %2$d.', 'polarsteps-importer'), $new_image_title, $attachment_id));
                    } else {
                        // If not found, download and attach it.
                        $attachment_id = self::download_and_attach_image($image_url, $post_id, $new_image_title);
                    }

                    if ($attachment_id) {
                        // Markiere das Bild für einfaches Löschen
                        add_post_meta($attachment_id, '_polarsteps_imported_image', true, true);
                        if ($mode === 'gallery') {
                            $gallery_ids[] = $attachment_id;
                        } else {
                            // 'embed' mode
                            $image_html .= wp_get_attachment_image($attachment_id, 'large', false, ['class' => 'polarsteps-imported-image']);
                        }
                    }
                    $image_counter++;
                }
            }
        }

        $content_to_append = '';
        if ($mode === 'gallery' && !empty($gallery_ids)) {
            $gallery_shortcode = '[gallery ids="' . implode(',', $gallery_ids) . '"]';
            $content_to_append = "\n\n" . $gallery_shortcode;

            // Setze das erste Bild als Beitragsbild (falls unterstützt)
            if (post_type_supports(get_post_type($post_id), 'thumbnail') && !empty($gallery_ids[0])) {
                set_post_thumbnail($post_id, $gallery_ids[0]);
            }
        } elseif ($mode === 'embed' && !empty($image_html)) {
            $content_to_append = "\n\n" . $image_html;
        }

        $post = get_post($post_id);
        wp_update_post([
            'ID'           => $post_id,
            'post_content' => $post->post_content . $content_to_append,
        ]);
    }

    /**
     * Find an existing image in the Media Library by its title.
     *
     * @param string $title The title of the image to find.
     * @return int|false The attachment ID if found, otherwise false.
     */
    private static function find_existing_image_by_title($title) {
        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'title'          => $title, // Search by exact post_title
            'fields'         => 'ids',
            'post_mime_type' => 'image',
            'suppress_filters' => true,
            'cache_results'  => false,
        ];

        $query = new WP_Query($args);

        return $query->have_posts() ? $query->posts[0] : false;
    }


    private static function download_and_attach_image($image_url, $post_id, $image_title = '') {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $tmp = download_url($image_url);
        if (is_wp_error($tmp)) {
            Polarsteps_Importer_Settings::log_message(sprintf(__('Error downloading image: %s', 'polarsteps-importer'), $tmp->get_error_message()));
            return false;
        }

        // Sanitize the title to create a friendly filename and get the original extension
        $file_extension = pathinfo(basename($image_url), PATHINFO_EXTENSION);
        $sane_filename = sanitize_title($image_title) . '.' . $file_extension;

        $file_array = [
            'name'     => $sane_filename,
            'tmp_name' => $tmp,
        ];

        $attachment_id = media_handle_sideload($file_array, $post_id, $image_title);
        if (is_wp_error($attachment_id)) {
            Polarsteps_Importer_Settings::log_message(sprintf(__('Error uploading image: %s', 'polarsteps-importer'), $attachment_id->get_error_message()));
            @unlink($tmp);
            return false;
        }
        Polarsteps_Importer_Settings::log_message(
            sprintf(
                /* translators: 1: Image name, 2: Post ID, 3: Attachment ID */
                __('Image "%1$s" successfully attached to post %2$d with ID %3$d.', 'polarsteps-importer'),
                $sane_filename,
                $post_id,
                $attachment_id
            )
        );
        return $attachment_id;
    }
}
