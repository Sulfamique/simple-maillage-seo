<?php
/*
Plugin Name: Simple Maillage SEO
Plugin URI: https://x.com/Sulfamique
Description: Plugin SEO pour un maillage interne automatique et simple.
Version: 1.0
Author: Sulfamique
Author URI: https://x.com/Sulfamique
License: GPL2
Text Domain: simple-maillage-seo
*/

if (!defined('ABSPATH')) {
    exit; // Sécurité : Empêche un accès direct.
}

// Inclure les fonctions supplémentaires
require_once plugin_dir_path(__FILE__) . 'includes/functions.php';

function simple_maillage_seo_load_textdomain() {
    load_plugin_textdomain('simple-maillage-seo', false, dirname(plugin_basename(__FILE__)) . '/languages');
}
add_action('init', 'simple_maillage_seo_load_textdomain');

function simple_maillage_seo_enqueue_admin_assets($hook) {
    if ($hook !== 'toplevel_page_simple-maillage-seo') {
        return;
    }

    // Numéro de version dynamique basé sur la dernière modification du fichier
    $script_version = filemtime(plugin_dir_path(__FILE__) . 'assets/js/scripts.js');
    $style_version = filemtime(plugin_dir_path(__FILE__) . 'assets/css/style.css');

    // CSS
    wp_enqueue_style(
        'simple-maillage-seo-admin-style',
        plugin_dir_url(__FILE__) . 'assets/css/style.css',
        [],
        $style_version // Version basée sur le cache
    );

    // JS
    wp_enqueue_script(
        'simple-maillage-seo-admin-script',
        plugin_dir_url(__FILE__) . 'assets/js/scripts.js',
        ['jquery'], // Dépend de jQuery
        $script_version, // Version basée sur le cache
        true // Charge en footer
    );
}
add_action('admin_enqueue_scripts', 'simple_maillage_seo_enqueue_admin_assets');