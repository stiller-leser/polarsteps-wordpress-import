<?php
class Polarsteps_Importer_Process {

    public static function run($args = []) {
        $options = get_option('polarsteps_importer_settings');
        $is_manual_run = !empty($args['manual']);
        $trip_id = $options['polarsteps_trip_id'] ?? '';
        $remember_token = $options['polarsteps_remember_token'] ?? '';
        $post_status = $options['polarsteps_post_status'] ?? 'draft';
        $post_type = $options['polarsteps_post_type'] ?? 'post';
        $category_id = $options['polarsteps_post_category'] ?? 0;
        $use_location_detail_as_category = $options['polarsteps_use_location_detail_as_category'] ?? false;
        $add_leaflet_map = ($options['polarsteps_leaflet_map'] ?? false) && is_plugin_active('leaflet-map/leaflet-map.php');
        $debug_mode = $options['polarsteps_debug_mode'] ?? false;
        $ignore_no_title = $options['polarsteps_ignore_no_title'] ?? false;
        $ignored_step_ids = array_map('trim', explode(',', $options['polarsteps_ignored_step_ids'] ?? ''));
        $image_import_mode = $options['polarsteps_image_import_mode'] ?? 'gallery';
        $steps_per_run = (int) ($options['polarsteps_steps_per_run'] ?? 10);
        $disable_image_import = $options['polarsteps_disable_image_import'] ?? false;

        if (empty($trip_id) || empty($remember_token)) {
            Polarsteps_Importer_Settings::log_message(__('Trip ID or Remember Token is missing.', 'polarsteps-importer'));
            return;
        }

        $remember_token_decrypted = Polarsteps_Importer_Security::decrypt($remember_token);
        $steps = Polarsteps_Importer_API::fetch_steps($trip_id, $remember_token_decrypted, $debug_mode);

        if (!$steps) {
            // Wenn keine Steps gefunden wurden, trotzdem den Cron-Job neu planen, um es später erneut zu versuchen.
            self::finalize_import(0, 0, $is_manual_run);
            return;
        }        

        // Filtere zuerst alle Steps, die tatsächlich importiert werden sollen.
        $steps_to_import = [];
        foreach ($steps as $step) {
            if (empty($step['display_name'])) {
                continue;
            }
            if (isset($step['is_deleted']) && true === $step['is_deleted']) {
                continue;
            }
            if (in_array($step['id'], $ignored_step_ids)) {
                continue;
            }
            // if ($ignore_no_title && empty($step['display_name'])) {
            //      if ($debug_mode) { Polarsteps_Importer_Settings::log_message(sprintf('Skipped Step %s: No title and ignore_no_title is active', $step['id'])); }
            //     continue;
            // }
            if (empty($step['description'])) {
                 if ($debug_mode) { Polarsteps_Importer_Settings::log_message(sprintf('Skipped Step %s ("%s"): Description is empty', $step['id'], $step['display_name'] ?? 'Unknown')); }
                continue;
            }
            if (Polarsteps_Importer_API::step_exists($step['id'], $post_type)) {
                 if ($debug_mode) { Polarsteps_Importer_Settings::log_message(sprintf('Skipped Step %s ("%s"): Already exists', $step['id'], $step['display_name'] ?? 'Unknown')); }
                continue;
            }
            $steps_to_import[] = $step;
        }

        $total_found = count($steps_to_import);
        Polarsteps_Importer_Settings::log_message(
            sprintf(
                /* translators: %d: The number of new posts found to import. */
                _n(
                    'Found %d new post to import.',
                    'Found %d new posts to import.',
                    $total_found,
                    'polarsteps-importer'
                ),
                $total_found
            )
        );

        $steps_to_process = array_slice($steps_to_import, 0, $steps_per_run);
        $total_to_import = count($steps_to_process);

        if ($total_found === 0) {
            // Nichts zu tun, beende den Importvorgang hier.
            self::finalize_import(0, 0, $is_manual_run); // Finalize with 0 imported, 0 remaining
            return;
        }

        $imported_count = 0;
        foreach ($steps_to_process as $index => $step) {
            $log_message = sprintf(
                /* translators: 1: Current post number, 2: Total posts number, 3: Post title. */
                __('Importing post %1$d of %2$d: "%3$s"', 'polarsteps-importer'),
                $index + 1,
                $total_to_import,
                $step['display_name']
            );
            Polarsteps_Importer_Settings::log_message($log_message);

            if (!$debug_mode) {
                // Sanitize content and then use wpautop to correctly create paragraphs (<p>) and line breaks (<br>).
                $post_content = wpautop(wp_kses_post($step['description']));

                // Remove emojis to prevent database issues on systems not using utf8mb4.
                // This regex covers most common emoji ranges.
                $post_content = preg_replace('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}\x{1F700}-\x{1F77F}\x{1F780}-\x{1F7FF}\x{1F800}-\x{1F8FF}\x{1F900}-\x{1F9FF}\x{1FA00}-\x{1FA6F}\x{1FA70}-\x{1FAFF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', '', $post_content);


                // Leaflet Map Shortcode hinzufügen, falls aktiviert und Koordinaten vorhanden
                if ($add_leaflet_map && !empty($step['location']['lat']) && !empty($step['location']['lon'])) {
                    $lat = esc_attr($step['location']['lat']);
                    $lon = esc_attr($step['location']['lon']);
                    $post_content .= "\n\n[leaflet-map lat=\"{$lat}\" lng=\"{$lon}\"][leaflet-marker lat=\"{$lat}\" lng=\"{$lon}\"]";
                }

                $step_date = gmdate('Y-m-d H:i:s', $step['creation_time']);

                $post_data = [
                    'post_title'   => sanitize_text_field($step['display_name']),
                    'post_content' => $post_content,
                    'post_status'  => $post_status,
                    'post_type'    => $post_type,
                    'post_date'    => $step_date,
                    'post_date_gmt'=> get_gmt_from_date($step_date),
                ];

                $post_id = wp_insert_post($post_data);

                if (is_wp_error($post_id) || $post_id === 0) {
                    Polarsteps_Importer_Settings::log_message(sprintf(
                        /* translators: %s: Error message */
                        __('Post could not be created: %s', 'polarsteps-importer'),
                        (is_wp_error($post_id) ? $post_id->get_error_message() : __('Unknown error', 'polarsteps-importer'))
                    ));
                    continue;
                } elseif ($post_id > 0) {
                    Polarsteps_Importer_Settings::log_message(sprintf(
                        /* translators: %d: The ID of the newly created post. */
                        __('Post created with ID %d.', 'polarsteps-importer'),
                        $post_id));
                    $term_ids_to_set = [];
                    $taxonomy_name = null;

                    // Finde die erste hierarchische Taxonomie (z.B. 'category')
                    $taxonomies = get_object_taxonomies($post_type, 'objects');
                    foreach ($taxonomies as $taxonomy) {
                        if ($taxonomy->hierarchical) {
                            $taxonomy_name = $taxonomy->name;
                            break;
                        }
                    }

                    if ($taxonomy_name) {
                        // 1. Kategorie aus Standortdetails hinzufügen
                        if ($use_location_detail_as_category && !empty($step['location']['detail'])) {
                            $category_name = sanitize_text_field($step['location']['detail']);
                            $category_name = Polarsteps_Importer_Translations::translate_country($category_name);
                            $term = get_term_by('name', $category_name, $taxonomy_name);

                            if ($term) {
                                $term_ids_to_set[] = $term->term_id;
                            } else {
                                $new_term = wp_insert_term($category_name, $taxonomy_name);
                                if (!is_wp_error($new_term)) {
                                    $term_ids_to_set[] = $new_term['term_id'];
                                }
                            }
                        }

                        // 2. Manuell ausgewählte Kategorie hinzufügen
                        if ($category_id > 0) {
                            $term_ids_to_set[] = (int)$category_id;
                        }

                        // Setze alle gesammelten Kategorien
                        if (!empty($term_ids_to_set)) {
                            wp_set_object_terms($post_id, array_unique($term_ids_to_set), $taxonomy_name);
                        }
                    }
                } else {
                    Polarsteps_Importer_Settings::log_message(__('An unknown error occurred while creating the post.', 'polarsteps-importer'));
                    continue;
                }

                if ($post_id && !empty($step['media']) && !$disable_image_import) {
                    $import_success = Polarsteps_Importer_API::import_step_media($step['media'], $post_id, $image_import_mode, $step['display_name']);
                    
                    if (!$import_success) {
                         Polarsteps_Importer_Settings::log_message(__('Media import incomplete. Deleting post to allow retry in next run.', 'polarsteps-importer'));
                         wp_delete_post($post_id, true); // Force delete post, attachments are preserved
                         // Decrement imported count effectively by not counting it? 
                         // But imported_count is for *completed* steps.
                         // We should continue to next step.
                         continue;
                    }
                }

                if ($post_id && !empty($step['location'])) {
                    update_post_meta($post_id, '_polarsteps_location', $step['location']);
                }

                if ($post_id) {
                    add_post_meta($post_id, '_polarsteps_step_id', $step['id'], true);
                    $imported_count++;
                }
            } else {
                Polarsteps_Importer_Settings::log_message(__('Debug mode is ON, no post created.', 'polarsteps-importer'));
                // Im Debug-Modus zählen wir den Beitrag als "importiert", ohne ihn zu erstellen.
                $imported_count++;
            }
        }

        $remaining_steps = $total_found - $imported_count;
        self::finalize_import($imported_count, $remaining_steps, $is_manual_run);
    }

    private static function finalize_import($imported_count, $remaining_steps = 0, $is_manual_run = false) {
        if ($imported_count > 0) {
            Polarsteps_Importer_Settings::log_message(
                 sprintf(
                     /* translators: %d: The number of imported posts. */
                     _n(
                         '%d new post was imported.',
                         '%d new posts were imported.',
                         $imported_count,
                         'polarsteps-importer'
                     ),
                     $imported_count
                 )
            );

            if ($remaining_steps > 0) {
                Polarsteps_Importer_Settings::log_message(
                    sprintf(
                        /* translators: %d: The number of remaining steps. */
                        __('Import run finished. %d steps remaining for the next run.', 'polarsteps-importer'),
                        $remaining_steps
                    )
                );
            }
        }

        if ($is_manual_run) {
            // Manueller Lauf ist beendet. Nur Benachrichtigung anzeigen.
            Polarsteps_Importer_Settings::log_message(__('Manual import finished.', 'polarsteps-importer'));
            set_transient('polarsteps_import_completed', true, 60);
        } elseif ($remaining_steps <= 0) {
            // Automatischer Lauf ist beendet, alle Schritte importiert. Job neu planen.
            Polarsteps_Importer_Cron::reschedule_after_import();
            Polarsteps_Importer_Settings::log_message(__('All steps imported. Re-scheduling recurring cron job.', 'polarsteps-importer'));
        }
    }
}