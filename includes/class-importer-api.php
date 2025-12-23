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
            Polarsteps_Importer_Settings::log_message(sprintf(
                /* translators: %s: The error message from the API response. */
                __('API Error: %s', 'polarsteps-importer'),
                $response->get_error_message()
            ));
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode(trim($body), true);

        if (is_null($data)) {
            Polarsteps_Importer_Settings::log_message(sprintf(
                /* translators: %s: The error message from json_last_error_msg(). */
                __('Could not parse API response: %s', 'polarsteps-importer'),
                json_last_error_msg()
            ));
        }

        if ($debug) {
            Polarsteps_Importer_Settings::log_message(__('Debug mode active. Data received:', 'polarsteps-importer') . print_r($data, true));
            
            $masked_token = substr($remember_token, 0, 3) . str_repeat('*', strlen($remember_token) - 3);
            $args_for_log = $args;
            $args_for_log['headers']['Cookie'] = "remember_token={$masked_token}";
            Polarsteps_Importer_Settings::log_message(sprintf(
                /* translators: 1: The API URL, 2: The request arguments. */
                __('API Request to: %1$s | Args: %2$s', 'polarsteps-importer'),
                $api_url, print_r($args_for_log, true)
            ));
            Polarsteps_Importer_Settings::log_message(__('API Response (raw):', 'polarsteps-importer') . substr($body, 0, 500) . (strlen($body) > 500 ? '...[truncated]' : ''));
            Polarsteps_Importer_Settings::log_message(__('Data:', 'polarsteps-importer') . print_r($data, true));
        }
        if (empty($data['all_steps'])) {
            Polarsteps_Importer_Settings::log_message(__('No steps found or invalid response: ', 'polarsteps-importer') . print_r($data, true));
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
            'post_status'    => 'any', // Search for posts in ANY status (published, draft, trash, future, etc.)
            'posts_per_page' => 1,
            'meta_key'       => '_polarsteps_step_id',
            'meta_value'     => $step_id,
            'fields'         => 'ids', // Get the ID to check its status if needed, though we can just check if any exist.
                                       // Wait, to check status we need the object or just trust 'any' covers it all.
                                       // But we want to return FALSE if it's in TRASH.
        ];

        // We need to fetch the status to decide.
        // Let's get the full object or field that includes status? 
        // Actually 'fields' => 'ids' is fine, we can get_post_status($id).
        
        $posts = get_posts($args);
        
        if (!empty($posts)) {
            $post_id = $posts[0];
            $status = get_post_status($post_id);
            
            // If the post is in the trash or auto-draft, we treat it as "not existing" for import purposes,
            // so we can re-import it (creating a new fresh post).
            if ($status === 'trash' || $status === 'auto-draft') {
                return false;
            }
            return true;
        }

        return false;
    }

    public static function import_step_media($media, $post_id, $mode = 'gallery', $step_title = '') {
        $gallery_ids = [];
        $media_html = '';
        $all_media_imported = true;

        if (!empty($media)) {
            $media_counter = 1;
            foreach ($media as $item) {
                $media_url = '';
                $is_video = false;

                if (isset($item['type']) && $item['type'] === 'video' && !empty($item['path'])) {
                    $media_url = $item['path'];
                    $is_video = true;
                } elseif (!empty($item['large_thumbnail_path'])) {
                    $media_url = $item['large_thumbnail_path'];
                }

                if ($media_url) {
                    $new_media_title = $step_title;
                    if (count($media) > 1) {
                        $new_media_title .= ' ' . $media_counter;
                    }

                    // Check if a media item with the same title already exists to prevent duplicates.
                    $attachment_id = self::find_existing_media_by_title($new_media_title);

                    if ($attachment_id) {
                        Polarsteps_Importer_Settings::log_message(sprintf(
                            /* translators: 1: The media title, 2: The attachment ID. */
                            __('Reusing existing media "%1$s" with ID %2$d.', 'polarsteps-importer'),
                            $new_media_title, $attachment_id
                        ));
                        
                        // Optional: Update post parent to the new post if it's orphaned or we want to claim it
                        // This ensures the media is "added" to the post in the backend sense too.
                        wp_update_post([
                            'ID' => $attachment_id,
                            'post_parent' => $post_id
                        ]);
                        
                    } else {
                        // If not found, download and attach it.
                        $attachment_id = self::download_and_attach_media($media_url, $post_id, $new_media_title);
                    }

                    if ($attachment_id) {
                        // Markiere das Medium für einfaches Löschen
                        add_post_meta($attachment_id, '_polarsteps_imported_media', true, true);
                        if ($mode === 'gallery') {
                            $gallery_ids[] = $attachment_id;
                        } else {
                            // 'embed' mode
                            if ($is_video) {
                                $video_url = wp_get_attachment_url($attachment_id);
                                $media_html .= sprintf(
                                    '<video controls class="polarsteps-imported-video"><source src="%s" type="%s">%s</video>',
                                    esc_url($video_url),
                                    esc_attr(get_post_mime_type($attachment_id)),
                                    esc_html__('Your browser does not support the video tag.', 'polarsteps-importer')
                                );
                            } else {
                                $media_html .= wp_get_attachment_image($attachment_id, 'large', false, ['class' => 'polarsteps-imported-image']);
                            }
                        }
                    } else {
                        // Failed to get attachment ID (download failed)
                        $all_media_imported = false;
                    }
                    $media_counter++;
                }
            }
        }

        $content_to_append = '';
        if ($mode === 'gallery' && !empty($gallery_ids)) {
            $gallery_shortcode = '[gallery ids="' . implode(',', $gallery_ids) . '"]';
            $content_to_append = "\n\n" . $gallery_shortcode;

            // Setze das erste Bild als Beitragsbild (falls unterstützt) und falls es ein Bild ist
            if (post_type_supports(get_post_type($post_id), 'thumbnail') && !empty($gallery_ids[0])) {
                 // Check if it's an image before setting as thumbnail
                 $first_attachment_mime = get_post_mime_type($gallery_ids[0]);
                 if (strpos($first_attachment_mime, 'image/') === 0) {
                    set_post_thumbnail($post_id, $gallery_ids[0]);
                 }
            }
        } elseif ($mode === 'embed' && !empty($media_html)) {
            $content_to_append = "\n\n" . $media_html;
        }

        $post = get_post($post_id);
        wp_update_post([
            'ID'           => $post_id,
            'post_content' => $post->post_content . $content_to_append,
        ]);

        return $all_media_imported;
    }

    /**
     * Find an existing media (image or video) in the Media Library by its title.
     *
     * @param string $title The title of the media to find.
     * @return int|false The attachment ID if found, otherwise false.
     */
    private static function find_existing_media_by_title($title) {
        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'posts_per_page' => 1,
            'title'          => $title, // Search by exact post_title
            'fields'         => 'ids',
            'post_mime_type' => ['image', 'video'], // Search for both
            'cache_results'  => false,
        ];

        $query = new WP_Query($args);

        return $query->have_posts() ? $query->posts[0] : false;
    }


    private static function download_and_attach_media($media_url, $post_id, $media_title = '') {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $tmp = false;
        $max_retries = 3;
        $attempt = 0;

        while ($attempt < $max_retries) {
            $tmp = download_url($media_url);
            if (!is_wp_error($tmp)) {
                break; // Success
            }
            
            // Log warning
            Polarsteps_Importer_Settings::log_message(sprintf(
                 __('Attempt %d to download media failed: %s. Retrying...', 'polarsteps-importer'),
                 $attempt + 1,
                 $tmp->get_error_message()
            ));
            
            $attempt++;
            if ($attempt < $max_retries) {
                sleep(1); // Wait 1 second before retrying
            }
        }

        if (is_wp_error($tmp)) {
            Polarsteps_Importer_Settings::log_message(sprintf(
                /* translators: %s: The error message from the download attempt. */
                __('Error downloading media after %d attempts: %s', 'polarsteps-importer'),
                $max_retries,
                $tmp->get_error_message()
            ));
            return false;
        }

        // Sanitize the title to create a friendly filename and get the original extension
        // Remove query parameters from URL for extension detection
        $clean_url = strtok($media_url, '?');
        $file_extension = pathinfo(basename($clean_url), PATHINFO_EXTENSION);
        if (empty($file_extension)) {
             // Fallback: try to guess extension from mime type of temp file if possible, or default to generic?
             // For now, let's hope the URL has one. If not, media_handle_sideload might figure it out or fail.
        }
        
        $sane_filename = sanitize_title($media_title) . '.' . $file_extension;

        $file_array = [
            'name'     => $sane_filename,
            'tmp_name' => $tmp,
        ];

        // Temporarily allow media uploads (images and videos) to avoid permission errors
        add_filter('upload_mimes', [__CLASS__, 'allow_media_uploads']);
        
        $attachment_id = media_handle_sideload($file_array, $post_id, $media_title);
        
        // Remove the filter immediately after
        remove_filter('upload_mimes', [__CLASS__, 'allow_media_uploads']);

        if (is_wp_error($attachment_id)) {
            Polarsteps_Importer_Settings::log_message(sprintf(
                /* translators: %s: The error message from the upload attempt. */
                __('Error uploading media: %s', 'polarsteps-importer'),
                $attachment_id->get_error_message()
            ));
            @unlink($tmp); // wp_delete_file might not work if it wasn't an upload
            return false;
        }
        Polarsteps_Importer_Settings::log_message(
            sprintf(
                /* translators: 1: Media name, 2: Post ID, 3: Attachment ID */
                __('Media "%1$s" successfully attached to post %2$d with ID %3$d.', 'polarsteps-importer'),
                $sane_filename,
                $post_id,
                $attachment_id
            )
        );
        return $attachment_id;
    }
    public static function allow_media_uploads($mimes) {
        $mimes['jpg|jpeg|jpe'] = 'image/jpeg';
        $mimes['gif'] = 'image/gif';
        $mimes['png'] = 'image/png';
        $mimes['bmp'] = 'image/bmp';
        $mimes['tiff|tif'] = 'image/tiff';
        $mimes['ico'] = 'image/x-icon';
        $mimes['webp'] = 'image/webp';
        $mimes['heic'] = 'image/heic';
        $mimes['heif'] = 'image/heif';

        $mimes['mp4|m4v'] = 'video/mp4';
        $mimes['mov|qt'] = 'video/quicktime';
        $mimes['wmv'] = 'video/x-ms-wmv';
        $mimes['avi'] = 'video/x-msvideo';
        $mimes['mpeg|mpg|mpe'] = 'video/mpeg';
        $mimes['mkv'] = 'video/x-matroska';
        $mimes['webm'] = 'video/webm';
        
        return $mimes;
    }
}
