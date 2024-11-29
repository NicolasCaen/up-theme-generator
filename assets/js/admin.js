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
        $('.form-section').addClass('loading');
        
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
                } else {
                    alert('Erreur lors du chargement des données du thème : ' + response.data);
                }
            },
            error: function() {
                alert('Erreur de communication avec le serveur');
            },
            complete: function() {
                $('.form-section').removeClass('loading');
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
                <div class="font-size-name">
                    <input type="text" name="font_names[]" placeholder="Nom (ex: small)">
                </div>
                <div class="font-size-values">
                    <div class="font-size-value">
                        <label>Défaut</label>
                        <input type="text" name="font_sizes[]" placeholder="ex: clamp(1rem, 2vw, 1.5rem)">
                    </div>
                    <div class="font-size-fluid">
                        <label>Min</label>
                        <input type="text" name="font_sizes_min[]" placeholder="ex: 1rem">
                    </div>
                    <div class="font-size-fluid">
                        <label>Max</label>
                        <input type="text" name="font_sizes_max[]" placeholder="ex: 1.5rem">
                    </div>
                </div>
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
        console.log('Données reçues pour pré-remplissage:', themeData);

        // Informations de base
        if (themeData.basic) {
            $('#theme_name').val(themeData.basic.name);
            $('#theme_description').val(themeData.basic.description);
            $('#theme_author').val(themeData.basic.author);
            $('#theme_slug').val(themeData.basic.slug);
        }

        // Palette de couleurs
        $('#color-palette').empty();
        if (themeData.colors && themeData.colors.length > 0) {
            themeData.colors.forEach(color => {
                console.log('Ajout de la couleur:', color);
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
        } else {
            // Ajouter une ligne vide si aucune couleur n'existe
            $('#add-color').trigger('click');
        }

        // Tailles de police
        $('#font-sizes').empty();
        if (themeData.typography && themeData.typography.fontSizes && themeData.typography.fontSizes.length > 0) {
            themeData.typography.fontSizes.forEach(fontSize => {
                console.log('Ajout de la taille de police:', fontSize);
                const template = `
                    <div class="font-size-item">
                        <div class="font-size-name">
                            <input type="text" name="font_names[]" value="${fontSize.name}" placeholder="Nom (ex: small)">
                        </div>
                        <div class="font-size-values">
                            <div class="font-size-value">
                                <label>Défaut</label>
                                <input type="text" name="font_sizes[]" value="${fontSize.size}" placeholder="ex: clamp(1rem, 2vw, 1.5rem)">
                            </div>
                            <div class="font-size-fluid">
                                <label>Min</label>
                                <input type="text" name="font_sizes_min[]" value="${fontSize.fluid ? fontSize.fluid.min : ''}" placeholder="ex: 1rem">
                            </div>
                            <div class="font-size-fluid">
                                <label>Max</label>
                                <input type="text" name="font_sizes_max[]" value="${fontSize.fluid ? fontSize.fluid.max : ''}" placeholder="ex: 1.5rem">
                            </div>
                        </div>
                        <button type="button" class="remove-font">Supprimer</button>
                    </div>
                `;
                $('#font-sizes').append(template);
            });
        } else {
            // Ajouter une ligne vide si aucune taille n'existe
            $('#add-font-size').trigger('click');
        }

        // Templates
        if (themeData.templates && themeData.templates.length > 0) {
            $('input[name="templates[]"]').prop('checked', false);
            themeData.templates.forEach(template => {
                $(`input[name="templates[]"][value="${template}"]`).prop('checked', true);
            });
        }

        // Parts
        if (themeData.parts && themeData.parts.length > 0) {
            $('input[name="parts[]"]').prop('checked', false);
            themeData.parts.forEach(part => {
                $(`input[name="parts[]"][value="${part}"]`).prop('checked', true);
            });
        }
    }

    // Modifiez l'événement de changement du select de thème
    $('#existing_theme').on('change', function() {
        if ($('#operation_type').val() === 'update') {
            loadExistingThemeData($(this).val());
        }
    });
});
