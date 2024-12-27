const DEFAULT_COLORS = [
    { name: 'Primary', slug: 'primary', value: '#0073aa' },
    { name: 'Secondary', slug: 'secondary', value: '#005177' },
    { name: 'Background', slug: 'background', value: '#ffffff' },
    { name: 'Text', slug: 'text', value: '#333333' }
];

const DEFAULT_FONT_SIZES = [
    { fluid: false, name: 's|14', size: '0.875rem', slug: 's' },
    { fluid: false, name: 'm|16', size: '1rem', slug: 'm' },
    { fluid: { max: '1.5rem', min: '1.5rem' }, name: 'l|20', size: '1.25rem', slug: 'l' },
    { fluid: true, name: 'xl|62', size: 'clamp(1rem, 3.23vw, 62px)', slug: 'xl' },
    { fluid: true, name: 'xxl|250', size: 'clamp(2rem, 13.02vw, 250px)', slug: 'xxl' },
    { name: 'h1|62', size: '3.875rem', slug: 'h-one' },
    { name: 'h2|52', size: '3.25rem', slug: 'h-two' },
    { name: 'h3|44', size: '2.75rem', slug: 'h-three' },
    { name: 'h4|36', size: '2.25rem', slug: 'h-four' },
    { name: 'h5|26', size: '1.625rem', slug: 'h-five' }
];

const DEFAULT_SPACING_SIZES = [
    { name: '.5rem|8', size: '.5rem', slug: '1' },
    { name: '1rem|16', size: '1rem', slug: '2' },
    { name: '1.5rem|24', size: '1.5rem', slug: '3' },
    { name: '2rem|32', size: '2rem', slug: '4' },
    { name: '2.5rem|40', size: '2.5rem', slug: '5' },
    { name: '3rem|48', size: '3rem', slug: '6' },
    { name: '4.5rem|72', size: '4.5rem', slug: '7' },
    { name: '7.5rem|120', size: '7.5rem', slug: '8' },
    { name: '10rem|160', size: '10rem', slug: '9' },
    { name: '12.5rem|200', size: '12.5rem', slug: '10' },
    { name: 'header height', size: 'var(--header-height)', slug: 'headerheight' }
];

jQuery(document).ready(function($) {
    // Fonction pour initialiser les slugs par défaut
    function initializeDefaultSlugs() {
        // Initialiser les slugs pour les tailles de police
        DEFAULT_FONT_SIZES.forEach((fontSize, index) => {
            const $fontSizeItem = $('#font-sizes .font-size-item').eq(index);
            $fontSizeItem.find('input[name="font_slugs[]"]').val(fontSize.slug);
        });

        // Initialiser les slugs pour les tailles d'espacement
        DEFAULT_SPACING_SIZES.forEach((spacingSize, index) => {
            const $spacingSizeItem = $('#spacing-sizes .spacing-size-item').eq(index);
            $spacingSizeItem.find('input[name="spacing_slugs[]"]').val(spacingSize.slug);
        });
    }

    // Appeler la fonction d'initialisation au chargement de la page
    initializeDefaultSlugs();

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

    // Fonction pour ajouter un élément de taille de police
    function addFontSizeItem(name = '', size = '', min = '', max = '', slug = '') {
        const template = `
            <div class="font-size-item">
                <div class="font-size-name">
                    <input type="text" name="font_names[]" value="${name}" placeholder="Nom (ex: small)">
                </div>
                <div class="font-size-values">
                    <div class="font-size-value">
                        <label>Défaut</label>
                        <input type="text" name="font_sizes[]" value="${size}" placeholder="ex: clamp(1rem, 2vw, 1.5rem)">
                    </div>
                    <div class="font-size-fluid">
                        <label>Min</label>
                        <input type="text" name="font_sizes_min[]" value="${min}" placeholder="ex: 1rem">
                    </div>
                    <div class="font-size-fluid">
                        <label>Max</label>
                        <input type="text" name="font_sizes_max[]" value="${max}" placeholder="ex: 1.5rem">
                    </div>
                    <div class="font-size-slug">
                        <label>Slug</label>
                        <input type="text" name="font_slugs[]" value="${slug}" placeholder="Slug">
                    </div>
                </div>
                <button type="button" class="remove-font">Supprimer</button>
            </div>
        `;
        $('#font-sizes').append(template);
    }

    // Fonction pour ajouter un élément de taille d'espacement
    function addSpacingSizeItem(name = '', size = '', slug = '') {
        const template = `
            <div class="spacing-size-item">
                <div class="spacing-size-name">
                    <input type="text" name="spacing_names[]" value="${name}" placeholder="Nom (ex: small)">
                </div>
                <div class="spacing-size-values">
                    <div class="spacing-size-value">
                        <label>Taille</label>
                        <input type="text" name="spacing_sizes[]" value="${size}" placeholder="ex: 1rem">
                    </div>
                    <div class="spacing-size-slug">
                        <label>Slug</label>
                        <input type="text" name="spacing_slugs[]" value="${slug}" placeholder="Slug">
                    </div>
                </div>
                <button type="button" class="remove-spacing">Supprimer</button>
            </div>
        `;
        $('#spacing-sizes').append(template);
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

    // Gestionnaire pour le bouton "Charger les valeurs par défaut" des tailles de police
    $('#load-default-font-sizes').on('click', function() {
        $('#font-sizes').empty();
        DEFAULT_FONT_SIZES.forEach(fontSize => {
            addFontSizeItem(fontSize.name, fontSize.size, fontSize.min, fontSize.max, fontSize.slug);
        });
    });

    // Gestionnaire pour le bouton "Charger les valeurs par défaut" des espacements
    $('#load-default-spacing-sizes').on('click', function() {
        $('#spacing-sizes').empty();
        DEFAULT_SPACING_SIZES.forEach(spacingSize => {
            addSpacingSizeItem(spacingSize.name, spacingSize.size, spacingSize.slug);
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

        // Inclure les slugs de police et d'espacement
        $('#font-sizes .font-size-item').each(function() {
            const fontSlug = $(this).find('input[name="font_slugs[]"]').val();
            console.log('Font Slug:', fontSlug); // Log pour vérifier les slugs
            formData.append('font_slugs[]', fontSlug);
        });

        $('#spacing-sizes .spacing-size-item').each(function() {
            const spacingSlug = $(this).find('input[name="spacing_slugs[]"]').val();
            console.log('Spacing Slug:', spacingSlug); // Log pour vérifier les slugs
            formData.append('spacing_slugs[]', spacingSlug);
        });

        // Convertir FormData en un objet lisible
        const dataObject = {};
        formData.forEach((value, key) => {
            if (!dataObject[key]) {
                dataObject[key] = value;
            } else {
                if (!Array.isArray(dataObject[key])) {
                    dataObject[key] = [dataObject[key]];
                }
                dataObject[key].push(value);
            }
        });

        // Afficher les données dans une alerte
        alert('Données envoyées :\n' + JSON.stringify(dataObject, null, 2));

        // Envoyer la requête AJAX
        $.ajax({
            url: upThemeGenerator.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response && response.success) {
                    alert(response.data.message || 'Thème généré avec succès');
                    window.location.href = 'themes.php';
                } else {
                    const errorMessage = response && response.data 
                        ? response.data 
                        : 'Une erreur est survenue lors de la génération du thème.';
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
                    <div class="font-size-slug">
                        <label>Slug</label>
                        <input type="text" name="font_slugs[]" placeholder="Slug">
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
                            <div class="font-size-slug">
                                <label>Slug</label>
                                <input type="text" name="font_slugs[]" value="${fontSize.slug}" placeholder="Slug">
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
            themeData.spacing.spacingSizes.forEach(spacingSize => {
                console.log('Ajout de la taille d\'espacement:', spacingSize);
                const template = `
                    <div class="spacing-size-item">
                        <div class="spacing-size-name">
                            <input type="text" name="spacing_names[]" value="${spacingSize.name}" placeholder="Nom (ex: small)">
                        </div>
                        <div class="spacing-size-values">
                            <div class="spacing-size-value">
                                <label>Taille</label>
                                <input type="text" name="spacing_sizes[]" value="${spacingSize.size}" placeholder="ex: 1rem">
                            </div>
                            <div class="spacing-size-slug">
                                <label>Slug</label>
                                <input type="text" name="spacing_slugs[]" value="${spacingSize.slug}" placeholder="Slug">
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
                    <div class="spacing-size-slug">
                        <label>Slug</label>
                        <input type="text" name="spacing_slugs[]" placeholder="Slug">
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
        const $slugInput = $item.find('input[name="spacing_slugs[]"]');
        if (!$slugInput.val()) {
            const slug = $(this).val()
                .toLowerCase()
                .replace(/[^a-z0-9]/g, '-')
                .replace(/-+/g, '-')
                .replace(/^-|-$/g, '');
            $slugInput.val(slug);
        }
    });
});
