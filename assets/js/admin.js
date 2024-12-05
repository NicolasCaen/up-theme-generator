const DEFAULT_COLORS = [
    { name: 'Primary', slug: 'primary', value: '#0073aa' },
    { name: 'Secondary', slug: 'secondary', value: '#005177' },
    { name: 'Background', slug: 'background', value: '#ffffff' },
    { name: 'Text', slug: 'text', value: '#333333' }
];

jQuery(document).ready(function($) {
    // Fonction pour ajouter un élément de couleur
    function addColorItem(name = '', slug = '', value = '') {
        const template = `
            <div class="color-item">
                <input type="text" name="color_names[]" value="${name}" placeholder="Nom de la couleur">
                <input type="text" name="color_slugs[]" value="${slug}" placeholder="Slug de la couleur">
                <input type="color" name="color_values[]" value="${value}">
                <button type="button" class="remove-color">Supprimer</button>
            </div>
        `;
        $('#color-palette').append(template);
    }

    // Gestionnaire pour le bouton "Charger les valeurs par défaut"
    $('#load-default-colors').on('click', function() {
        // Vider la palette actuelle
        $('#color-palette').empty();
        
        // Ajouter les couleurs par défaut
        DEFAULT_COLORS.forEach(color => {
            addColorItem(color.name, color.slug, color.value);
        });
    });

    // Gestionnaire pour le bouton "Ajouter une couleur"
    $('#add-color').on('click', function() {
        addColorItem();
    });

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
            if (selectedTheme) {
                loadExistingThemeData(selectedTheme);
            }
        }
    });

    // Gestion de la soumission du formulaire
    $('#theme-generator-form').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        formData.append('action', 'generate_theme');
        formData.append('nonce', upThemeGenerator.nonce);
        formData.append('create_backup', $('#create_backup').is(':checked'));

        const operationType = $('#operation_type').val();
        formData.append('operation_type', operationType);
        
        if (operationType === 'update') {
            formData.append('existing_theme', $('#existing_theme').val());
        }

        $.ajax({
            url: upThemeGenerator.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    alert(response.data.message || 'Thème mis à jour avec succès');
                    window.location.href = 'themes.php';
                } else {
                    const errorMessage = response && response.data 
                        ? response.data 
                        : 'Une erreur est survenue lors de la mise à jour du thème.';
                    alert(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                console.error('Erreur AJAX:', error);
                alert('Erreur lors de la communication avec le serveur.');
            }
        });
    });

    // Fonction pour charger les données d'un thème existant
    function loadExistingThemeData(themeSlug) {
        if (!themeSlug) {
            console.error('Slug du thème non fourni');
            return;
        }

        $('.form-section').addClass('loading');
        
        $.ajax({
            url: upThemeGenerator.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_theme_data',
                nonce: upThemeGenerator.nonce,
                theme_slug: themeSlug
            },
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    populateFormWithThemeData(response.data);
                } else {
                    const errorMessage = response && response.data 
                        ? response.data 
                        : 'Erreur lors du chargement des données du thème';
                    alert(errorMessage);
                }
            },
            error: function(xhr, status, error) {
                console.group('Erreur chargement données thème');
                console.error('Status:', status);
                console.error('Erreur:', error);
                console.error('Response:', xhr.responseText);
                console.groupEnd();
                
                alert('Erreur lors du chargement des données du thème. Veuillez réessayer.');
            },
            complete: function() {
                $('.form-section').removeClass('loading');
            }
        });
    }

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

        // Tailles d'espacement
        $('#spacing-sizes').empty();
        if (themeData.spacing && themeData.spacing.spacingSizes && themeData.spacing.spacingSizes.length > 0) {
            themeData.spacing.spacingSizes.forEach(spacing => {
                console.log('Ajout de la taille d\'espacement:', spacing);
                const template = `
                    <div class="spacing-size-item">
                        <div class="spacing-size-name">
                            <input type="text" name="spacing_names[]" value="${spacing.name}" placeholder="Nom (ex: small)">
                        </div>
                        <div class="spacing-size-values">
                            <div class="spacing-size-value">
                                <label>Taille</label>
                                <input type="text" name="spacing_sizes[]" value="${spacing.size}" placeholder="ex: 1rem">
                            </div>
                        </div>
                        <button type="button" class="remove-spacing">Supprimer</button>
                    </div>
                `;
                $('#spacing-sizes').append(template);
            });
        } else {
            // Ajouter une ligne vide si aucune taille d'espacement n'existe
            $('#add-spacing-size').trigger('click');
        }
    }

    // Modifiez l'événement de changement du select de thème
    $('#existing_theme').on('change', function() {
        if ($('#operation_type').val() === 'update') {
            loadExistingThemeData($(this).val());
        }
    });

    // Prévisualisation du screenshot
    $('#theme_screenshot').on('change', function() {
        const file = this.files[0];
        const $preview = $('#screenshot-preview');
        const $img = $preview.find('img');
        
        if (file) {
            // Vérifier le type de fichier
            if (!file.type.match('image/png') && !file.type.match('image/jpeg')) {
                alert('Le fichier doit être au format PNG ou JPEG');
                this.value = '';
                return;
            }
            
            // Vérifier la taille (2Mo max)
            if (file.size > 2 * 1024 * 1024) {
                alert('Le fichier est trop volumineux (maximum 2 Mo)');
                this.value = '';
                return;
            }

            // Afficher la prévisualisation
            const reader = new FileReader();
            reader.onload = function(e) {
                $img.attr('src', e.target.result);
                $preview.show();
                
                // Vérifier les dimensions
                const image = new Image();
                image.src = e.target.result;
                image.onload = function() {
                    if (this.width !== 1200 || this.height !== 900) {
                        alert('Attention : Les dimensions recommandées sont 1200 × 900 pixels. Votre image fait ' + this.width + ' × ' + this.height + ' pixels.');
                    }
                };
            };
            reader.readAsDataURL(file);
        } else {
            $preview.hide();
        }
    });

    // Ajout d'une taille d'espacement
    $('#add-spacing-size').on('click', function() {
        const template = `
            <div class="spacing-size-item">
                <div class="spacing-size-name">
                    <input type="text" name="spacing_names[]" placeholder="Nom (ex: small)">
                </div>
                <div class="spacing-size-values">
                    <div class="spacing-size-value">
                        <label>Taille</label>
                        <input type="text" name="spacing_sizes[]" placeholder="ex: 1rem">
                    </div>
                </div>
                <button type="button" class="remove-spacing">Supprimer</button>
            </div>
        `;
        $('#spacing-sizes').append(template);
    });

    // Suppression d'une taille d'espacement
    $(document).on('click', '.remove-spacing', function() {
        $(this).closest('.spacing-size-item').remove();
    });

    // Auto-génération du slug d'espacement
    $(document).on('input', 'input[name="spacing_names[]"]', function() {
        const $item = $(this).closest('.spacing-size-item');
        const value = $(this).val()
            .toLowerCase()
            .replace(/[^a-z0-9]/g, '-')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    });
});
