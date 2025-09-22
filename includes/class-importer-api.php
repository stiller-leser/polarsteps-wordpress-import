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
            Polarsteps_Importer_Settings::log_message('API-Fehler: ' . $response->get_error_message());
            return false;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode(trim($body), true);

        if(is_null($data)) {
            Polarsteps_Importer_Settings::log_message('API-Antwort konnte nicht geparsed werden: ' . print_r(json_last_error_msg(), true));
        }

        if ($debug) {
            Polarsteps_Importer_Settings::log_message('Debug-Modus aktiv.' . print_r($data, true));
            
            $masked_token = substr($remember_token, 0, 3) . str_repeat('*', strlen($remember_token) - 3);
            $args_for_log = $args;
            $args_for_log['headers']['Cookie'] = "remember_token={$masked_token}";
            Polarsteps_Importer_Settings::log_message('API-Anfrage an: ' . $api_url . ' | Args: ' . print_r($args_for_log, true));
            Polarsteps_Importer_Settings::log_message('API-Antwort raw: ' . substr($body, 0, 500) . (strlen($body) > 500 ? '...[truncated]' : ''));
            Polarsteps_Importer_Settings::log_message('Data: ' . implode(", ", $data['all_steps']));

            return $data;
        }

        if (empty($data['all_steps'])) {
            Polarsteps_Importer_Settings::log_message('Keine Steps gefunden oder ungültige Antwort: ' . print_r($data, true));
            return false;
        }

        if ($debug) {
            Polarsteps_Importer_Settings::log_message('Steps gefunden: ' . print_r($data['all_steps'], true));
        }

        return $data['all_steps'];
    }

    public static function step_exists($step_id, $post_type = 'post') {
        $args = [
            'post_type'      => $post_type,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'meta_key'       => '_polarsteps_step_id',
            'meta_value'     => $step_id,
            'fields'         => 'ids',
        ];

        $posts = get_posts($args);

        // Debug-Log für die Abfrage
        Polarsteps_Importer_Settings::log_message(
            sprintf(
                'Prüfe auf Step-ID %s im Post-Type %s: %s Beiträge gefunden.',
                $step_id,
                $post_type,
                count($posts)
            )
        );

        return !empty($posts);
    }

    public static function import_step_photos($media, $post_id) {
        if (empty($media)) {
            return;
        }

        $gallery_ids = [];

        foreach ($media as $item) {
            if (!empty($item['large_thumbnail_path'])) {
                $image_url = $item['large_thumbnail_path'];
                $attachment_id = self::download_and_attach_image($image_url, $post_id);

                if ($attachment_id) {
                    $gallery_ids[] = $attachment_id;
                }
            }
        }

        if (!empty($gallery_ids)) {
            // Füge die Galerie zum Beitragsinhalt hinzu
            $gallery_shortcode = '[gallery ids="' . implode(',', $gallery_ids) . '"]';
            $post_content = get_post_field('post_content', $post_id);
            $updated_content = $post_content . "\n\n" . $gallery_shortcode;
            wp_update_post([
                'ID'           => $post_id,
                'post_content' => $updated_content,
            ]);

            // Setze das erste Bild als Beitragsbild (falls unterstützt)
            if (post_type_supports(get_post_type($post_id), 'thumbnail') && !empty($gallery_ids[0])) {
                set_post_thumbnail($post_id, $gallery_ids[0]);
            }
        }
    }

    private static function download_and_attach_image($image_url, $post_id) {
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';

        $tmp = download_url($image_url);
        if (is_wp_error($tmp)) {
            Polarsteps_Importer_Settings::log_message('Fehler beim Herunterladen des Bildes: ' . $tmp->get_error_message());
            return false;
        }

        $file_array = [
            'name'     => basename($image_url),
            'tmp_name' => $tmp,
        ];

        $attachment_id = media_handle_sideload($file_array, $post_id);
        if (is_wp_error($attachment_id)) {
            Polarsteps_Importer_Settings::log_message('Fehler beim Hochladen des Bildes: ' . $attachment_id->get_error_message());
            @unlink($tmp);
            return false;
        }
        return $attachment_id;
    }
}
