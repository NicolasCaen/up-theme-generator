jQuery(document).ready(function($) {
    // Mise à jour de l'URL lors du changement de bloc
    $('#block-type-selector').on('change', function() {
        const selectedBlock = $(this).val();
        if (selectedBlock) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('block', selectedBlock);
            window.location.href = currentUrl.toString();
        }
    });

    // Appliquer les styles en temps réel
    $('.style-input').on('change input', function() {
        const styles = {};
        const blockType = $('#block-type-selector').val();
        
        $('.style-input').each(function() {
            const value = $(this).val();
            if (value && value !== 'inherit') {
                const name = $(this).attr('name');
                let property = '';
                
                switch(name) {
                    case 'typography_fontFamily':
                        property = 'font-family';
                        break;
                    case 'typography_fontSize':
                        property = 'font-size';
                        break;
                    case 'typography_fontWeight':
                        property = 'font-weight';
                        break;
                    case 'typography_fontStyle':
                        property = 'font-style';
                        break;
                    case 'typography_lineHeight':
                        property = 'line-height';
                        break;
                    case 'color_text':
                        property = 'color';
                        break;
                    case 'color_background':
                        property = 'background-color';
                        break;
                    case 'spacing_padding':
                        property = 'padding';
                        break;
                    case 'spacing_margin':
                        property = 'margin';
                        break;
                    default:
                        property = name;
                }

                styles[property] = value;
            }
        });

        // Appliquer les styles selon le type de bloc
        const previewContainer = $('#block-preview-container');
        switch(blockType) {
            case 'core/button':
                previewContainer.find('.wp-block-button__link').css(styles);
                break;
            case 'core/quote':
                previewContainer.find('.wp-block-quote p').css(styles);
                break;
            case 'core/heading':
                previewContainer.find('h1, h2, h3, h4, h5, h6').css(styles);
                break;
            case 'core/paragraph':
                previewContainer.find('p').css(styles);
                break;
            case 'core/list':
                previewContainer.find('ul, ol').css(styles);
                break;
            default:
                // Pour les autres blocs, appliquer au premier élément enfant
                previewContainer.children().first().css(styles);
        }

        // Enregistrer la configuration du sélecteur pour la sauvegarde
        const styleConfig = {
            blockType: blockType,
            selector: getBlockSelector(blockType),
            styles: styles
        };
        console.log('Configuration du style:', styleConfig);
    });

    // Obtenir le sélecteur CSS approprié pour le bloc
    function getBlockSelector(blockType) {
        switch(blockType) {
            case 'core/button':
                return '.wp-block-button__link';
            case 'core/quote':
                return '.wp-block-quote p';
            case 'core/heading':
                return '.wp-block-heading';
            case 'core/paragraph':
                return '.wp-block-paragraph';
            case 'core/list':
                return '.wp-block-list';
            default:
                return '.wp-block-' + blockType.replace('core/', '');
        }
    }
});