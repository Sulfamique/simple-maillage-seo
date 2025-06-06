<?php

// Ajouter une page au menu d'administration
function simple_maillage_seo_add_admin_menu() {
    add_menu_page(
        __('Simple Maillage SEO', 'simple-maillage-seo'),
        __('Simple Maillage SEO', 'simple-maillage-seo'),
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
    $exclusions = get_option('simple_maillage_seo_exclusions', '');

    echo '<div class="wrap">';
    echo '<h1>' . esc_html__('Simple Maillage SEO', 'simple-maillage-seo') . '</h1>';
    echo '<form id="simple-maillage-seo-form" method="post" action="options.php">';
    wp_nonce_field('simple_maillage_seo_save', 'simple_maillage_seo_nonce');

    // Conteneur pour les messages d'erreur
    echo '<div class="error-message" style="display:none; color: red; margin-bottom: 15px;"></div>';

    // Champs WordPress pour la sauvegarde des options
    settings_fields('simple_maillage_seo_settings');
    do_settings_sections('simple-maillage-seo');

    // Tableau dynamique
    echo '<table id="keywords-table" class="wp-list-table widefat fixed striped">';
    echo '<thead><tr><th>' . esc_html__('Mot clé', 'simple-maillage-seo') . '</th><th>' . esc_html__('Lien', 'simple-maillage-seo') . '</th><th>' . esc_html__('Action', 'simple-maillage-seo') . '</th></tr></thead>';
    echo '<tbody>';

    // Ajouter des lignes si des données existent
    if (!empty($data) && is_array($data)) {
        foreach ($data as $item) {
            $keyword = esc_attr($item['keyword'] ?? '');
            $url = esc_url($item['url'] ?? '');
            echo '<tr>';
            echo '<td><input type="text" name="keywords[]" value="' . $keyword . '" placeholder="' . esc_attr__('Entrez un mot clé', 'simple-maillage-seo') . '" /></td>';
            echo '<td><input type="url" name="links[]" value="' . $url . '" placeholder="' . esc_attr__('Entrez un lien', 'simple-maillage-seo') . '" /></td>';
            echo '<td><button type="button" class="button button-secondary remove-row" aria-label="' . esc_attr__('Supprimer', 'simple-maillage-seo') . '"><span class="dashicons dashicons-trash"></span></button></td>';
            echo '</tr>';
        }
    } else {
        // Ajouter une ligne vide par défaut si aucune donnée
        echo '<tr>';
        echo '<td><input type="text" name="keywords[]" placeholder="' . esc_attr__('Entrez un mot clé', 'simple-maillage-seo') . '" /></td>';
        echo '<td><input type="url" name="links[]" placeholder="' . esc_attr__('Entrez un lien', 'simple-maillage-seo') . '" /></td>';
        echo '<td><button type="button" class="button button-secondary remove-row" aria-label="' . esc_attr__('Supprimer', 'simple-maillage-seo') . '"><span class="dashicons dashicons-trash"></span></button></td>';
        echo '</tr>';
    }

    echo '</tbody>';
    echo '</table>';

    echo '<div class="exclusion-list">';
    echo '<h3>' . esc_html__('Exclusions', 'simple-maillage-seo') . '</h3>';
    echo '<p>' . esc_html__('IDs des contenus à exclure, séparés par des virgules', 'simple-maillage-seo') . '</p>';
    echo '<input type="text" name="simple_maillage_seo_exclusions" value="' . esc_attr($exclusions) . '" />';
    echo '</div>';

    // Bouton pour ajouter une ligne
    echo '<button type="button" id="add-row" class="button button-primary">' . esc_html__('Ajouter une ligne', 'simple-maillage-seo') . '</button>';
    submit_button(esc_html__('Enregistrer les paramètres', 'simple-maillage-seo'));
    echo '</form>';
    echo '</div>';
}

// Enregistrer les paramètres
function simple_maillage_seo_register_settings() {
    register_setting('simple_maillage_seo_settings', 'simple_maillage_seo_data', function($input) {
        check_admin_referer('simple_maillage_seo_save', 'simple_maillage_seo_nonce');
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

        // Log uniquement en mode débogage
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(print_r($output, true));
        }

        return $output;
    });

    register_setting('simple_maillage_seo_settings', 'simple_maillage_seo_exclusions', function($input) {
        check_admin_referer('simple_maillage_seo_save', 'simple_maillage_seo_nonce');
        $ids = array_filter(array_map('intval', explode(',', $input)));
        return implode(',', $ids);
    });
}
add_action('admin_init', 'simple_maillage_seo_register_settings');

// Ajouter les liens automatiquement dans le contenu
function simple_maillage_seo_insert_link_dom(DOMDocument $dom, $keyword, $url) {
    $xpath = new DOMXPath($dom);
    $nodes = $xpath->query('//text()');

    foreach ($nodes as $node) {
        if (stripos($node->nodeValue, $keyword) === false) {
            continue;
        }

        $skip = false;
        for ($parent = $node->parentNode; $parent && $parent->nodeType === XML_ELEMENT_NODE; $parent = $parent->parentNode) {
            $tag = strtolower($parent->nodeName);
            if ($tag === 'a' || preg_match('/^h[1-6]$/', $tag)) {
                $skip = true;
                break;
            }
            if ($parent->hasAttribute('class') && stripos($parent->getAttribute('class'), 'faq') !== false) {
                $skip = true;
                break;
            }
        }

        if ($skip) {
            continue;
        }

        $pos = stripos($node->nodeValue, $keyword);
        if ($pos === false) {
            continue;
        }

        $before = substr($node->nodeValue, 0, $pos);
        $match  = substr($node->nodeValue, $pos, strlen($keyword));
        $after  = substr($node->nodeValue, $pos + strlen($keyword));

        $parent = $node->parentNode;
        $afterNode = $dom->createTextNode($after);
        $linkNode  = $dom->createElement('a', $match);
        $linkNode->setAttribute('href', $url);
        $beforeNode = $dom->createTextNode($before);

        $parent->replaceChild($afterNode, $node);
        $parent->insertBefore($linkNode, $afterNode);
        $parent->insertBefore($beforeNode, $linkNode);

        return true;
    }

    return false;
}

function simple_maillage_seo_process_content($content) {
    if (is_admin()) {
        return $content;
    }

    $data = get_option('simple_maillage_seo_data', []);
    if (!is_array($data) || empty($data)) {
        return $content;
    }

    $excluded = get_option('simple_maillage_seo_exclusions', '');
    $excluded_ids = array_filter(array_map('intval', explode(',', $excluded)));
    if (in_array(get_the_ID(), $excluded_ids, true)) {
        return $content;
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument('1.0', 'UTF-8');
    $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();

    foreach ($data as $item) {
        if (!isset($item['keyword'], $item['url']) || empty($item['keyword']) || empty($item['url'])) {
            continue;
        }

        $url = esc_url($item['url']);
        simple_maillage_seo_insert_link_dom($dom, $item['keyword'], $url);
    }

    $body = $dom->getElementsByTagName('body')->item(0);
    $new_content = '';
    foreach ($body->childNodes as $child) {
        $new_content .= $dom->saveHTML($child);
    }

    return $new_content;
}
add_filter('the_content', 'simple_maillage_seo_process_content');
