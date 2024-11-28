jQuery(document).ready(function($) {
    // Gestion du changement de type d'opération
    $('#operation_type').on('change', function() {
        const isUpdate = $(this).val() === 'update';
        $('#existing_theme_row').toggle(isUpdate);
        $('.action-text-new').toggle(!isUpdate);
        $('.action-text-update').toggle(isUpdate);
        
        // Désactiver/activer les champs de base selon le mode
        $('#theme_name, #theme_slug').prop('disabled', isUpdate);
        
        if (isUpdate) {
            // Charger les données du thème existant
            const selectedTheme = $('#existing_theme').val();
            loadExistingThemeData(selectedTheme);
        }
    });

    // Fonction pour charger les données d'un thème existant
    function loadExistingThemeData(themeSlug) {
        $.ajax({
            url: upThemeGenerator.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_theme_data',
                nonce: upThemeGenerator.nonce,
                theme_slug: themeSlug
            },
            success: function(response) {
                if (response.success) {
                    populateFormWithThemeData(response.data);
                }
            }
        });
    }

    // Ajout de couleur modifié
    $('#add-color').on('click', function() {
        const template = `
            <div class="color-item">
                <input type="text" name="color_names[]" placeholder="Nom de la couleur">
                <input type="text" name="color_slugs[]" placeholder="Slug de la couleur">
                <input type="color" name="color_values[]">
                <button type="button" class="remove-color">Supprimer</button>
            </div>
        `;
        $('#color-palette').append(template);
    });

    // Auto-génération du slug de couleur
    $(document).on('input', 'input[name="color_names[]"]', function() {
        const $slugInput = $(this).siblings('input[name="color_slugs[]"]');
        if (!$slugInput.val()) {
            const slug = $(this).val()
                .toLowerCase()
                .replace(/[^a-z0-9]/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
            $slugInput.val(slug);
        }
    });

    // Ajout de taille de police
    $('#add-font-size').on('click', function() {
        const template = `
            <div class="font-size-item">
                <input type="text" name="font_names[]" placeholder="Nom (ex: small)">
                <input type="text" name="font_sizes[]" placeholder="Taille (ex: 16px)">
                <button type="button" class="remove-font">Supprimer</button>
            </div>
        `;
        $('#font-sizes').append(template);
    });

    // Suppression d'éléments
    $(document).on('click', '.remove-color, .remove-font', function() {
        $(this).parent().remove();
    });

    // Soumission du formulaire
    $('#theme-generator-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'generate_theme');
        formData.append('nonce', upThemeGenerator.nonce);

        $.ajax({
            url: upThemeGenerator.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            beforeSend: function() {
                // Ajout d'un indicateur de chargement
                $('<div class="notice notice-info"><p>Génération du thème en cours...</p></div>')
                    .insertBefore('#theme-generator-form');
            },
            success: function(response) {
                if (response.success) {
                    // Remplace la notice de chargement par un message de succès
                    $('.notice-info').replaceWith(
                        `<div class="notice notice-success">
                            <p>Thème généré avec succès ! Vous pouvez maintenant l'activer dans la section Apparence > Thèmes.</p>
                        </div>`
                    );
                } else {
                    // Remplace la notice de chargement par un message d'erreur
                    $('.notice-info').replaceWith(
                        `<div class="notice notice-error">
                            <p>Erreur lors de la génération du thème : ${response.data || 'Erreur inconnue'}</p>
                        </div>`
                    );
                }
            },
            error: function(xhr, status, error) {
                // Gestion des erreurs
                $('.notice-info').replaceWith(
                    `<div class="notice notice-error">
                        <p>Erreur lors de la communication avec le serveur : ${error}</p>
                    </div>`
                );
            }
        });
    });

    // Auto-génération du slug
    $('#theme_name').on('input', function() {
        const slug = $(this).val()
            .toLowerCase()
            .replace(/[^a-z0-9]/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
        $('#theme_slug').val(slug);
    });

    // Validation des champs obligatoires
    function validateForm() {
        let isValid = true;
        const requiredFields = ['theme_name', 'theme_slug'];

        requiredFields.forEach(field => {
            const $field = $(`#${field}`);
            if (!$field.val().trim()) {
                isValid = false;
                $field.addClass('error');
            } else {
                $field.removeClass('error');
            }
        });

        return isValid;
    }

    // Prévisualisation des couleurs
    $(document).on('change', 'input[type="color"]', function() {
        const $nameInput = $(this).siblings('input[type="text"]');
        if (!$nameInput.val()) {
            const colorValue = $(this).val();
            $nameInput.val(`Couleur ${colorValue}`);
        }
    });

    // Nettoyage des notifications
    $(document).on('click', '.notice', function() {
        $(this).fadeOut(300, function() {
            $(this).remove();
        });
    });

    function populateFormWithThemeData(themeData) {
        // Remplir les couleurs
        $('#color-palette').empty();
        if (themeData.colors) {
            themeData.colors.forEach(color => {
                const template = `
                    <div class="color-item">
                        <input type="text" name="color_names[]" value="${color.name}" placeholder="Nom de la couleur">
                        <input type="text" name="color_slugs[]" value="${color.slug}" placeholder="Slug de la couleur">
                        <input type="color" name="color_values[]" value="${color.color}">
                        <button type="button" class="remove-color">Supprimer</button>
                    </div>
                `;
                $('#color-palette').append(template);
            });
        }

        // Remplir les autres données du formulaire...
    }
});
