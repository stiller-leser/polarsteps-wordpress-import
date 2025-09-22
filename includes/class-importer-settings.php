<?php
class Polarsteps_Importer_Settings {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'settings_init']);
        add_filter('pre_update_option_polarsteps_importer_settings', [$this, 'save_settings'], 10, 1);
    }

    public function add_admin_menu() {
        add_menu_page(
            'Polarsteps Importer',
            'Polarsteps Importer',
            'manage_options',
            'polarsteps-importer',
            [$this, 'options_page_with_logs'],
            'dashicons-location-alt',
            80
        );
    }

    public function settings_init() {
        register_setting('polarsteps_importer', 'polarsteps_importer_settings');

        add_settings_section(
            'polarsteps_importer_section',
            'Polarsteps-Einstellungen',
            [$this, 'settings_section_callback'],
            'polarsteps-importer'
        );

        add_settings_field(
            'polarsteps_trip_id',
            'Trip-ID',
            [$this, 'trip_id_render'],
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_remember_token',
            'Remember Token',
            [$this, 'remember_token_render'],
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_post_type',
            'Post-Type',
            [$this, 'post_type_render'], // Neues Feld für Post-Type-Auswahl
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_post_category',
            'Kategorie',
            [$this, 'post_category_render'], // Neues Feld für Kategorie-Auswahl
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_post_status',
            'Beitragsstatus',
            [$this, 'post_status_render'],
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_update_interval',
            'Aktualisierungsintervall (in Stunden)',
            [$this, 'update_interval_render'],
            'polarsteps-importer',
            'polarsteps_importer_section'
        );

        add_settings_field(
            'polarsteps_debug_mode',
            'Debug-Modus aktivieren',
            [$this, 'debug_mode_render'],
            'polarsteps-importer',
            'polarsteps_importer_section'
        );
    }

    // Logs in der Datenbank speichern
    public static function log_message($message) {
        $logs = get_option('polarsteps_importer_logs', []);
        $logs[] = '[Polarsteps Importer] ' . current_time('mysql') . ': ' . $message;
        if (count($logs) > 100) {
            array_shift($logs);
        }
        update_option('polarsteps_importer_logs', $logs);
    }

    // Kombinierte Seite für Einstellungen und Logs
    public function options_page_with_logs() {
        // Speichere Einstellungen, falls Formular gesendet wurde
        if (isset($_POST['clear_logs']) && check_admin_referer('polarsteps_importer_clear_logs')) {
            update_option('polarsteps_importer_logs', []);
            echo '<div class="notice notice-success"><p>Logs wurden gelöscht.</p></div>';
        }

        ?>
        <div class="wrap">
            <h1>Polarsteps Importer</h1>

            <!-- Einstellungen -->
            <form action="options.php" method="post">
                <?php
                settings_fields('polarsteps_importer');
                do_settings_sections('polarsteps-importer');
                submit_button('Einstellungen speichern');
                ?>
            </form>

            <hr>

            <!-- Manueller Import -->
            <h3>Manueller Import</h3>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="polarsteps_importer_run_now">
                <?php wp_nonce_field('polarsteps_importer_run_now'); ?>
                <?php submit_button('Jetzt importieren', 'secondary', 'submit', false); ?>
            </form>

            <hr>

            <!-- Logs-Anzeige -->
            <h3>Debug-Logs</h3>
            <?php $this->display_filtered_logs(); ?>
        </div>
        <?php
    }

    // Gefilterte Logs anzeigen (aus der Datenbank)
    private function display_filtered_logs() {
        $logs = get_option('polarsteps_importer_logs', []);

        if (empty($logs)) {
            echo '<p>Keine Logs gefunden. Führe einen Import durch, um Logs zu generieren.</p>';
            return;
        }

        echo '<div style="max-height: 400px; overflow-y: auto; background: #f9f9f9; padding: 10px; border: 1px solid #ddd; font-family: monospace;">';
        echo '<pre style="margin: 0;">';
        echo esc_html(implode("\n", array_reverse($logs))); // Neueste Logs zuerst
        echo '</pre>';

        echo '</div>';
        // Lösch-Button für Logs
        echo '<p style="margin-top: 10px;">';
        echo '<form method="post">';
        wp_nonce_field('polarsteps_importer_clear_logs');
        submit_button('Logs löschen', 'delete', 'clear_logs', false);
        echo '</form>';
        echo '</p>';
    }

    // Render-Methoden
    public function trip_id_render() {
        $options = get_option('polarsteps_importer_settings');
        echo '<input type="text" name="polarsteps_importer_settings[polarsteps_trip_id]" value="' . esc_attr($options['polarsteps_trip_id'] ?? '') . '">';
    }

    public function remember_token_render() {
        echo '<input type="password" name="polarsteps_importer_remember_token" value="">';
        echo '<p class="description">Trage dein Remember Token ein. Es wird verschlüsselt gespeichert und taucht nicht wieder auf.</p>';
    }

    public function update_interval_render() {
        $options = get_option('polarsteps_importer_settings');
        echo '<input type="number" name="polarsteps_importer_settings[polarsteps_update_interval]" min="1" value="' . esc_attr($options['polarsteps_update_interval'] ?? 1) . '">';
    }

    public function post_status_render() {
        $options = get_option('polarsteps_importer_settings');
        $status = $options['polarsteps_post_status'] ?? 'draft';
        echo '<select name="polarsteps_importer_settings[polarsteps_post_status]">';
        echo '<option value="draft" ' . selected($status, 'draft', false) . '>Entwurf</option>';
        echo '<option value="publish" ' . selected($status, 'publish', false) . '>Veröffentlicht</option>';
        echo '</select>';
    }

    public function post_type_render() {
        $options = get_option('polarsteps_importer_settings');
        $post_type = $options['polarsteps_post_type'] ?? 'post';

        // Hole alle registrierten Post-Types
        $post_types = get_post_types(['public' => true], 'objects');
        ?>
        <select name="polarsteps_importer_settings[polarsteps_post_type]">
            <?php foreach ($post_types as $type => $obj): ?>
                <option value="<?php echo esc_attr($type); ?>" <?php selected($post_type, $type); ?>>
                    <?php echo esc_html($obj->labels->singular_name); ?> (<?php echo esc_html($type); ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            Wähle den Post-Type, in den die Polarsteps-Steps importiert werden sollen.<br>
            Standardmäßig werden Beiträge ("post") verwendet.
        </p>
        <?php
    }

    public function post_category_render() {
        $options = get_option('polarsteps_importer_settings');
        $post_type = $options['polarsteps_post_type'] ?? 'post';
        $category_id = $options['polarsteps_post_category'] ?? 0;

        $taxonomies = get_object_taxonomies($post_type, 'objects');
        $category_taxonomies = [];

        // Suche nach Kategorien-Taxonomien (z. B. "category" oder benutzerdefiniert)
        foreach ($taxonomies as $taxonomy) {
            if ($taxonomy->hierarchical) {
                $category_taxonomies[$taxonomy->name] = $taxonomy->labels->singular_name;
            }
        }

        if (empty($category_taxonomies)) {
            echo '<p>Der ausgewählte Post-Type hat keine Kategorien.</p>';
            echo '<input type="hidden" name="polarsteps_importer_settings[polarsteps_post_category]" value="0">';
            return;
        }

        // Standardmäßig die erste Taxonomie verwenden
        $taxonomy_name = key($category_taxonomies);
        $terms = get_terms([
            'taxonomy' => $taxonomy_name,
            'hide_empty' => false,
        ]);

        ?>
        <select name="polarsteps_importer_settings[polarsteps_post_category]" id="polarsteps_post_category">
            <option value="0">-- Keine Kategorie --</option>
            <?php foreach ($terms as $term): ?>
                <option value="<?php echo esc_attr($term->term_id); ?>" <?php selected($category_id, $term->term_id); ?>>
                    <?php echo esc_html($term->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p class="description">
            Wähle eine Kategorie für die importierten Beiträge.
        </p>

        <script>
        jQuery(document).ready(function($) {
            $('#polarsteps_post_type').change(function() {
                var postType = $(this).val();
                var taxonomies = JSON.parse($(this).find(':selected').attr('data-taxonomies'));
                var categorySelect = $('#polarsteps_post_category');
                var hasCategories = false;

                // Leere das Kategorie-Dropdown
                categorySelect.empty();
                categorySelect.append('<option value="0">-- Keine Kategorie --</option>');

                // Suche nach hierarchischen Taxonomien (Kategorien)
                for (var taxName in taxonomies) {
                    if (taxonomies[taxName].hierarchical) {
                        hasCategories = true;
                        $.ajax({
                            url: ajaxurl,
                            data: {
                                action: 'polarsteps_get_terms',
                                taxonomy: taxName,
                                post_type: postType
                            },
                            success: function(response) {
                                if (response.success && response.data.terms) {
                                    $.each(response.data.terms, function(index, term) {
                                        categorySelect.append(
                                            $('<option></option>').val(term.term_id).text(term.name)
                                        );
                                    });
                                }
                            }
                        });
                        break; // Nur die erste hierarchische Taxonomie verwenden
                    }
                }

                if (!hasCategories) {
                    categorySelect.append('<option value="0">Der ausgewählte Post-Type hat keine Kategorien</option>');
                }
            });
        });
        </script>
        <?php
    }

    public function debug_mode_render() {
        $options = get_option('polarsteps_importer_settings');
        echo '<input type="checkbox" name="polarsteps_importer_settings[polarsteps_debug_mode]" ' . checked($options['polarsteps_debug_mode'] ?? false, true, false) . ' value="1">';
        echo '<p class="description">Aktiviert den Debug-Modus. Es werden keine Beiträge erstellt, aber die API-Antwort wird geloggt.</p>';
    }

    public function settings_section_callback() {
        echo '<p>Trage deine Polarsteps Trip-ID und deinen Remember Token ein, um Steps als WordPress-Beiträge zu importieren.</p>';
    }

    public function save_settings($settings) {
        if (isset($_POST['polarsteps_importer_remember_token']) && !empty($_POST['polarsteps_importer_remember_token'])) {
            $settings['polarsteps_remember_token'] = Polarsteps_Importer_Security::encrypt($_POST['polarsteps_importer_remember_token']);
        } else {
            $options = get_option('polarsteps_importer_settings');
            $settings['polarsteps_remember_token'] = $options['polarsteps_remember_token'] ?? '';   
        }
        return $settings;
    }
}
