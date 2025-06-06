console.log('scripts.js est exécuté correctement.');

jQuery(document).ready(function($) {
    // Ajouter une nouvelle ligne
    $('#add-row').on('click', function() {
        console.log('Bouton "Ajouter une ligne" cliqué');
        const newRow = `
            <tr>
                <td><input type="text" name="keywords[]" placeholder="Entrez un mot clé" /></td>
                <td><input type="url" name="links[]" placeholder="Entrez un lien" /></td>
                <td><button type="button" class="button button-secondary remove-row" aria-label="Supprimer"><span class="dashicons dashicons-trash"></span></button></td>
            </tr>`;
        $('#keywords-table tbody').append(newRow);
    });

    // Supprimer une ligne
    $(document).on('click', '.remove-row', function() {
        console.log('Bouton "Supprimer" cliqué');
        $(this).closest('tr').remove();
    });

    // Validation du formulaire
    $('#simple-maillage-seo-form').on('submit', function(e) {
        let isValid = true;

        // Réinitialiser le message d'erreur
        $('.error-message').hide().text('');

        // Vérifie que tous les champs sont remplis
        $('#keywords-table tbody tr').each(function() {
            const keyword = $(this).find('input[name="keywords[]"]').val();
            const link = $(this).find('input[name="links[]"]').val();

            if (!keyword || !link) {
                console.log('Erreur : champ vide détecté');
                // Ajouter un message d'erreur dans le conteneur
                $('.error-message').text('Tous les champs doivent être remplis avant d\'enregistrer.').show();
                isValid = false;
                return false; // Arrête la boucle
            }
        });

        // Empêche la soumission si un champ est vide
        if (!isValid) {
            e.preventDefault();
        }
    });
});
