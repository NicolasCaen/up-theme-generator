jQuery(document).ready(function($) {
    // État global
    const state = {
        currentBlock: null,
        currentStyle: null,
        previewTimeout: null
    };

    // Gestionnaire d'onglets
    $('.tab-button').on('click', function() {
        const tab = $(this).data('tab');
        $('.tab-button').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').removeClass('active');
        $(`.tab-content[data-tab="${tab}"]`).addClass('active');
    });

    // Sélection du bloc
    $('#block-type-selector').on('change', function() {
        state.currentBlock = $(this).val();
        if (state.currentBlock) {
            updateBlockPreview();
        }
    });

    // Mise à jour de la prévisualisation lors des changements de style
    $('.style-input').on('change input', debounce(function() {
        if (state.currentBlock) {
            updateBlockPreview();
        }
    }, 300));

    // Fonction de debounce
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Collecte des styles
    function collectStyles() {
        const styles = {};
        
        // Typographie
        const typography = {};
        $('[name^="typography_"]').each(function() {
            const prop = $(this).attr('name').replace('typography_', '');
            const value = $(this).val();
            if (value && value !== 'inherit') {
                typography[prop] = value;
            }
        });
        if (Object.keys(typography).length > 0) {
            styles.typography = typography;
        }

        // Couleurs
        const colors = {};
        $('[name^="color_"]').each(function() {
            const prop = $(this).attr('name').replace('color_', '');
            const value = $(this).val();
            if (value && value !== 'inherit') {
                colors[prop] = value;
            }
        });
        if (Object.keys(colors).length > 0) {
            styles.color = colors;
        }

        // Espacement
        const spacing = {
            padding: {},
            margin: {}
        };
        $('[name^="spacing_padding_"]').each(function() {
            const side = $(this).attr('name').replace('spacing_padding_', '');
            const value = $(this).val();
            if (value) {
                spacing.padding[side] = value;
            }
        });
        $('[name^="spacing_margin_"]').each(function() {
            const side = $(this).attr('name').replace('spacing_margin_', '');
            const value = $(this).val();
            if (value) {
                spacing.margin[side] = value;
            }
        });
        if (Object.keys(spacing.padding).length > 0 || Object.keys(spacing.margin).length > 0) {
            styles.spacing = spacing;
        }

        // Bordure
        const border = {};
        const borderWidth = {};
        const borderRadius = {};
        
        $('[name^="border_width_"]').each(function() {
            const side = $(this).attr('name').replace('border_width_', '');
            const value = $(this).val();
            if (value) {
                borderWidth[side] = value;
            }
        });
        if (Object.keys(borderWidth).length > 0) {
            border.width = borderWidth;
        }

        $('[name^="border_radius_"]').each(function() {
            const corner = $(this).attr('name').replace('border_radius_', '');
            const value = $(this).val();
            if (value) {
                borderRadius[corner] = value;
            }
        });
        if (Object.keys(borderRadius).length > 0) {
            border.radius = borderRadius;
        }

        const borderStyle = $('[name="border_style"]').val();
        if (borderStyle && borderStyle !== 'none') {
            border.style = borderStyle;
        }

        const borderColor = $('[name="border_color"]').val();
        if (borderColor && borderColor !== 'inherit') {
            border.color = borderColor;
        }

        if (Object.keys(border).length > 0) {
            styles.border = border;
        }

        return styles;
    }

    // Mise à jour de la prévisualisation
    function updateBlockPreview() {
        if (!state.currentBlock) return;

        const styles = collectStyles();
        const blockContent = generatePreviewContent(state.currentBlock);
        const styledContent = applyStylesToPreview(blockContent, styles);

        $('#block-preview-container').html(styledContent);
    }

    // Génération du contenu de prévisualisation
    function generatePreviewContent(blockName) {
        // Contenu de prévisualisation par défaut pour différents types de blocs
        const previewContent = {
            'core/paragraph': '<p>Ceci est un paragraphe exemple pour la prévisualisation.</p>',
            'core/heading': '<h2>Titre exemple</h2>',
            'core/button': '<div class="wp-block-button"><a class="wp-block-button__link">Bouton exemple</a></div>',
            'core/group': '<div class="wp-block-group"><div class="wp-block-group__inner-container"><p>Contenu du groupe</p></div></div>',
            // Ajoutez d'autres blocs selon les besoins
        };

        return previewContent[blockName] || `<div>Prévisualisation pour ${blockName}</div>`;
    }

    // Application des styles à la prévisualisation
    function applyStylesToPreview(content, styles) {
        const styleString = generateStyleString(styles);
        return `<div class="block-preview" style="${styleString}">${content}</div>`;
    }

    // Génération de la chaîne de style
    function generateStyleString(styles) {
        const cssProperties = [];

        if (styles.typography) {
            Object.entries(styles.typography).forEach(([prop, value]) => {
                cssProperties.push(`${camelToKebab(prop)}: ${value}`);
            });
        }

        if (styles.color) {
            if (styles.color.text) cssProperties.push(`color: ${styles.color.text}`);
            if (styles.color.background) cssProperties.push(`background-color: ${styles.color.background}`);
        }

        if (styles.spacing) {
            if (styles.spacing.padding) {
                const padding = Object.entries(styles.spacing.padding)
                    .map(([side, value]) => `padding-${side}: ${value}`)
                    .join('; ');
                if (padding) cssProperties.push(padding);
            }
            if (styles.spacing.margin) {
                const margin = Object.entries(styles.spacing.margin)
                    .map(([side, value]) => `margin-${side}: ${value}`)
                    .join('; ');
                if (margin) cssProperties.push(margin);
            }
        }

        if (styles.border) {
            if (styles.border.style) cssProperties.push(`border-style: ${styles.border.style}`);
            if (styles.border.color) cssProperties.push(`border-color: ${styles.border.color}`);
            if (styles.border.width) {
                Object.entries(styles.border.width).forEach(([side, value]) => {
                    cssProperties.push(`border-${side}-width: ${value}`);
                });
            }
            if (styles.border.radius) {
                Object.entries(styles.border.radius).forEach(([corner, value]) => {
                    cssProperties.push(`border-${camelToKebab(corner)}-radius: ${value}`);
                });
            }
        }

        return cssProperties.join('; ');
    }

    // Utilitaire pour convertir camelCase en kebab-case
    function camelToKebab(string) {
        return string.replace(/([a-z0-9]|(?=[A-Z]))([A-Z])/g, '$1-$2').toLowerCase();
    }

    // Sauvegarde du style
    $('#block-style-form').on('submit', function(e) {
        e.preventDefault();
        
        const styleName = $('#style-name').val();
        if (!styleName || !state.currentBlock) return;

        const styleData = {
            name: styleName,
            blockName: state.currentBlock,
            styles: collectStyles()
        };

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'save_block_style',
                nonce: upThemeGenerator.nonce,
                style_data: JSON.stringify(styleData)
            },
            success: function(response) {
                if (response.success) {
                    loadSavedStyles();
                    alert('Style sauvegardé avec succès !');
                } else {
                    alert('Erreur lors de la sauvegarde du style.');
                }
            }
        });
    });

    // Chargement des styles sauvegardés
    function loadSavedStyles() {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            data: {
                action: 'get_block_styles',
                nonce: upThemeGenerator.nonce
            },
            success: function(response) {
                if (response.success) {
                    displaySavedStyles(response.data);
                }
            }
        });
    }

    // Affichage des styles sauvegardés
    function displaySavedStyles(styles) {
        const container = $('#saved-styles-list');
        container.empty();

        Object.values(styles).forEach(style => {
            const styleElement = $(`
                <div class="saved-style-item">
                    <h4>${style.name}</h4>
                    <p>Bloc: ${style.blockName}</p>
                    <div class="style-actions">
                        <button class="button edit-style" data-style='${JSON.stringify(style)}'>Modifier</button>
                        <button class="button delete-style" data-name="${style.name}">Supprimer</button>
                    </div>
                </div>
            `);
            container.append(styleElement);
        });
    }

    // Initialisation
    loadSavedStyles();
}); 