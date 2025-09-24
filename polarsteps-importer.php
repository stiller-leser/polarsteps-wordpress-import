<?php
/*
Plugin Name: Polarsteps Importer
Description: Import your Polarsteps trips as WordPress posts, including images, location data, and original publication dates.
Version: 1.1
Author: Kaj-Sören Mossdorf
Author URI: https://macroco.de
Text Domain: polarsteps-importer
Domain Path: /languages
*/

if (!defined('ABSPATH')) {
    exit;
}

// Autoload der Klassen
spl_autoload_register(function ($class) {
    $prefix = 'Polarsteps_Importer_';
    $base_dir = __DIR__ . '/includes/';
    if (strpos($class, $prefix) === 0) {
        $class_name = str_replace($prefix, '', $class);
        $file = $base_dir . 'class-importer-' . strtolower(str_replace('_', '-', $class_name)) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

// Initialisierung
add_action('plugins_loaded', function() {
    new Polarsteps_Importer_Plugin();
    new Polarsteps_Importer_Settings();
});

// Aktivierung/Deaktivierung
register_activation_hook(__FILE__, ['Polarsteps_Importer_Plugin', 'activate']);
register_deactivation_hook(__FILE__, ['Polarsteps_Importer_Plugin', 'deactivate']);
