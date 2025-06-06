<?php

// Ajouter une page au menu d'administration
function simple_maillage_seo_add_admin_menu() {
    add_menu_page(
        'Simple Maillage SEO',
        'Simple Maillage SEO',
        'manage_options',
        'simple-maillage-seo',
        'simple_maillage_seo_dashboard',
        'dashicons-admin-links',
        20
    );
}
add_action('admin_menu', 'simple_maillage_seo_add_admin_menu');

// Afficher le tableau de bord
function simple_maillage_seo_dashboard() {
    // Récupération des données actuelles
    $data = get_option('simple_maillage_seo_data', []); // Récupère les données sauvegardées

    echo '<div class="wrap">';
    echo '<h1>Simple Maillage SEO</h1>';
    echo '<form id="simple-maillage-seo-form" method="post" action="options.php">';

    // Conteneur pour les messages d'erreur
    echo '<div class="error-message" style="display:none; color: red; margin-bottom: 15px;"></div>';

    // Champs WordPress pour la sauvegarde des options
    settings_fields('simple_maillage_seo_settings');
    do_settings_sections('simple-maillage-seo');

    // Tableau dynamique
    echo '<table id="keywords-table" class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>Mot clé</th><th>Lien</th><th>Action</th></tr></thead>';
    echo '<tbody>';

    // Ajouter des lignes si des données existent
    if (!empty($data) && is_array($data)) {
        foreach ($data as $item) {
            $keyword = esc_attr($item['keyword'] ?? '');
            $url = esc_url($item['url'] ?? '');
            echo '<tr>';
            echo '<td><input type="text" name="keywords[]" value="' . $keyword . '" placeholder="Entrez un mot clé" /></td>';
            echo '<td><input type="url" name="links[]" value="' . $url . '" placeholder="Entrez un lien" /></td>';
            echo '<td><button type="button" class="button button-secondary remove-row">Supprimer</button></td>';
            echo '</tr>';
        }
    } else {
        // Ajouter une ligne vide par défaut si aucune donnée
        echo '<tr>';
        echo '<td><input type="text" name="keywords[]" placeholder="Entrez un mot clé" /></td>';
        echo '<td><input type="url" name="links[]" placeholder="Entrez un lien" /></td>';
        echo '<td><button type="button" class="button button-secondary remove-row">Supprimer</button></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    // Bouton pour ajouter une ligne
    echo '<button type="button" id="add-row" class="button button-primary">Ajouter une ligne</button>';
    submit_button('Enregistrer les paramètres');
    echo '</form>';
    echo '</div>';
}

// Enregistrer les paramètres
function simple_maillage_seo_register_settings() {
    register_setting('simple_maillage_seo_settings', 'simple_maillage_seo_data', function($input) {
        $output = [];

        if (isset($input['keywords'], $input['links']) && is_array($input['keywords']) && is_array($input['links'])) {
            foreach ($input['keywords'] as $index => $keyword) {
                $keyword = sanitize_text_field($keyword);
                $url = esc_url_raw($input['links'][$index]);

                // Ajouter seulement si le mot clé et le lien sont valides
                if (!empty($keyword) && !empty($url)) {
                    $output[] = ['keyword' => $keyword, 'url' => $url];
                }
            }
        }

        // Test temporaire : Loguer les données sauvegardées
        error_log(print_r($output, true));

        return $output;
    });
}
add_action('admin_init', 'simple_maillage_seo_register_settings');

// Ajouter les liens automatiquement dans le contenu
function simple_maillage_seo_process_content($content) {
    // Ne pas modifier le contenu dans l'administration
    if (is_admin()) {
        return $content;
    }

    // Récupération des données enregistrées
    $data = get_option('simple_maillage_seo_data', []);
    if (!is_array($data)) {
        return $content;
    }

    foreach ($data as $item) {
        // Vérifier que le mot-clé et l'URL sont valides
        if (!isset($item['keyword'], $item['url']) || empty($item['keyword']) || empty($item['url'])) {
            continue;
        }

        $keyword = preg_quote($item['keyword'], '/'); // Échappe les caractères spéciaux dans le mot-clé
        $url = esc_url($item['url']); // Nettoie l'URL

        // Utiliser preg_replace_callback pour ne remplacer que le premier mot-clé trouvé
        $content = preg_replace_callback(
            "/(?<!<a[^>]*>)\b($keyword)\b(?!<\/a>)/i", // Rechercher le mot-clé sans être déjà dans un lien
            function ($matches) use ($url) {
                return '<a href="' . $url . '">' . $matches[1] . '</a>'; // Ajouter le lien autour du mot-clé
            },
            $content,
            1 // Limiter à une occurrence
        );
    }

    return $content;
}
add_filter('the_content', 'simple_maillage_seo_process_content');
