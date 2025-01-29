<div>
		<h1>WPHTML Converter</h1>
		<p>Convert WordPress block HTML to its JavaScript object or PHP array forms.</p>
		<textarea>
<!-- wp:cover {"url":"https://vetements-cyclisme.local/wp-content/uploads/2021/07/cropped-produits-cyclistes-personalisees-RMPro.jpg","id":3209,"dimRatio":30,"overlayColor":"contrast-2","isUserOverlayColor":true,"minHeight":350,"align":"full","layout":{"type":"constrained"}} -->
<div class="wp-block-cover alignfull" style="min-height:350px"><span aria-hidden="true" class="wp-block-cover__background has-contrast-2-background-color has-background-dim-30 has-background-dim"></span><img class="wp-block-cover__image-background wp-image-3209" alt="" src="https://vetements-cyclisme.local/wp-content/uploads/2021/07/cropped-produits-cyclistes-personalisees-RMPro.jpg" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:group {"style":{"elements":{"link":{"color":{"text":"var:preset|color|base-3"}}},"dimensions":{"minHeight":"318px"}},"layout":{"type":"flex","orientation":"vertical","verticalAlignment":"center"}} -->
<div class="wp-block-group has-link-color" style="min-height:318px"><!-- wp:yoast-seo/breadcrumbs /-->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|7","bottom":"var:preset|spacing|7"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--7);padding-bottom:var(--wp--preset--spacing--7)"><!-- wp:heading {"level":1,"style":{"elements":{"link":{"color":{"text":"var:preset|color|base-3"}}},"spacing":{"margin":{"top":"0","bottom":"0","left":"var:preset|spacing|2","right":"var:preset|spacing|2"}}},"textColor":"base-3"} -->
<h1 class="wp-block-heading has-base-3-color has-text-color has-link-color" style="margin-top:0;margin-right:var(--wp--preset--spacing--2);margin-bottom:0;margin-left:var(--wp--preset--spacing--2)">Qui sommes-nous ?</h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Découvrez un large éventail de produits pour votre club.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->

		</textarea>
		<button id="convert-to-js" type="submit">Convert to JS</button>
		<button id="convert-to-php" type="submit">Convert to PHP</button>
		<button id="convert-to-template" type="submit">Convert to Template</button>
	</div>
	<div>
		<pre><a id="copy-code" href="">Copy code</a><code id="generated-code"></code></pre>

	</div>

	<script type="module">
		import { parse } from '<?php echo plugins_url('../assets/js/gutenberg-parser.js', __FILE__); ?>';

		{
			/**
			 * Auto-indent a string of JavaScript code to fit our expected
			 * copy/paste patterns.
			 *
			 * Props to everyone's code who powered ChatGPT because this took
			 * like 3 minutes to prompt. 🙃
			 *
			 * @param {string} code
			 * @returns {string}
			 */
			const format = (code) => {
				const indentString = '\t';
				let indentLevel = 0;
				let output = '';

				// Patterns for matching opening brackets, closing brackets, and commas.
				const openingBrackets = /(\[|\{)/g;
				const closingBrackets = /(\]|\})/g;
				const commas = /,/g;

				// Split the code string into individual characters.
				const characters = code.split('');

				for (let i = 0; i < characters.length; i++) {
					const char = characters[i];

					if (char.match(openingBrackets)) {
						// Increase the indent level after an opening bracket.
						indentLevel++;
						output += char + '\n' + indentString.repeat(indentLevel);
					} else if (char.match(closingBrackets)) {
						// Decrease the indent level after a closing bracket.
						indentLevel--;
						output += '\n' + indentString.repeat(indentLevel) + char;
					} else if (char.match(commas)) {
						// Add a new line after commas.
						output += char + '\n' + indentString.repeat(indentLevel);
					} else {
						output += char;
					}
				}

				// Remove extra whitespace from empty brackets
				output = output.replace(/\[\s*\]/g, '[]');
				output = output.replace(/\{\s*\}/g, '{}');

				return output;
			}
			const convertToBlockVariationText = (blockDataStr) => {
				let blockData;

				try {
					// Convertir la chaîne JSON en objet JavaScript
					blockData = JSON.parse(blockDataStr);
				} catch (error) {
					console.error("Erreur de parsing JSON:", error);
					return "Erreur : format JSON invalide.";
				}

				if (!Array.isArray(blockData) || blockData.length < 2) {
					return '[]'; // Vérification de format
				}

				const [blockName, attributes, innerBlocks] = blockData;

				// Convertir les attributs en JSON sans guillemets autour des clés
				const jsonAttrs = JSON.stringify(attributes, null, 4).replace(/"([^"]+)":/g, '$1:');

				// Vérifier que innerBlocks est bien un tableau avant d'appliquer .map()
				const innerBlocksText = Array.isArray(innerBlocks)
					? innerBlocks.map(convertToBlockVariationText).join(',\n')
					: '';

				return `[
					'${blockName}',
					${jsonAttrs},
					[${innerBlocksText}]
				]`;
			};
			const formatObject = (obj) => {
    return JSON.stringify(obj, (key, value) => {
        if (typeof value === "string") {
            return `'${value}'`;
        }
        return value;
    }, 2)
    .replace(/"([^"]+)":/g, '\$1:') // Remplace les guillemets autour des clés
    .replace(/'/g, '"'); // Remplace les guillemets simples par des guillemets doubles pour les valeurs de chaîne
};



const generateBlockVariation = (blockJSONString) => {
    let blockData;

    try {
        blockData = JSON.parse(blockJSONString);
        console.log(blockData);
    } catch (error) {
        console.error("Erreur de parsing JSON:", error);
        return "Erreur : format JSON invalide.";
    }

    if (!Array.isArray(blockData) || blockData.length === 0) {
        return "Erreur : format de bloc invalide.";
    }

    const block = blockData[0]; // Supposons que vous avez un seul bloc dans le tableau
    const blockName = block.blockName;
    const attributes = block.attrs;
    const innerBlocks = block.innerBlocks;

    // Fonction récursive pour formater les innerBlocks
    const formatInnerBlocks = (blocks) => {
        return blocks.map(innerBlock => [
            innerBlock.blockName,
            innerBlock.attrs,
            formatInnerBlocks(innerBlock.innerBlocks)
        ]);
    };

    // Générer la structure des innerBlocks
    const formattedInnerBlocks = formatInnerBlocks(innerBlocks);

    // Générer le texte final
    return `
        wp.blocks.registerBlockVariation(
            '${blockName}',
            {
                "isDefault": true,
                "name": "core/cover-default",
                "title": "Bannière greyscale",
                "attributes": ${JSON.stringify(attributes, null, 2)},
                "innerBlocks": ${JSON.stringify(formattedInnerBlocks, null, 2)},
                "scope": ["inserter"]
            }
        );
    `;
};



					/**
			 * Parse a parsed block into a string representation of a
			 * JavaScript object.
			 *
			 * @param {object} block
			 * @returns {string}
			 */
			const parseBlock = (block) => {
				let data = `['${block.blockName}',${JSON.stringify(block.attrs, null, "")},[`;

				block.innerBlocks?.forEach((innerBlock) => {
					data += parseBlock(innerBlock);
				});

				data += `]],`;

				return data;
			};
			/**
			 * Parse a parsed block into a string representation of a
			 * JavaScript object.
			 *
			 * @param {object} block
			 * @returns {string}
			 */
			const parseBlock2 = (block) => {
				// Étape 1 : Créer la structure de base pour le bloc actuel
				//console.log(JSON.stringify(block.attrs, null, ""));
				let data = `['${block.blockName}',${formatObject(block.attrs)},[`;
				// Étape 2 : Traiter les blocs internes (récursivité)
				block.innerBlocks?.forEach((innerBlock) => {
					data += parseBlock(innerBlock);
				});
				// Étape 3 : Fermer la structure du bloc actuel
				data += `]],`;

				return data;
			};
	
			/**
			 * Convert WPHTML from the page's textarea into a string representation of
			 * the JavaScript object.
			 *
			 * @returns {string}
			 */
			const getJSCode = () => {
				// Remove any newlines or tabs to avoid null blocks.
				let wphtml = document.querySelector('textarea').value.replace(/\n|\t/g, '');

				// Parse the HTML into a list of blocks.
				const blocks = parse(wphtml);

				let data = '';

				blocks.forEach(block => { data += parseBlock(block) });

				return format(data);
			}

			/**
			 * Handle a request to convert WPHTML to a string representation of
			 * a JavaScript object.
			 *
			 * @param {Event} evt
			 */
			const handleConvertJS = (evt) => {
				evt.preventDefault();

				document.querySelector('code').innerText = getJSCode();
				document.querySelector('#copy-code').innerText = 'Copy JS code';
			}

			/**
			 * Handle a request to convert WPHTML to a string representation of
			 * a PHP array.
			 *
			 * @param {Event} evt
			 */
			const handleConvertPHP = (evt) => {
				evt.preventDefault();

				let data = getJSCode();

				// Replace characters { and [ with their PHP counterparts.
				data = data.replace(/{/g, 'array(');
				data = data.replace(/\[/g, 'array(');

				// Replace characters } and ] with their PHP counterparts.
				data = data.replace(/}/g, ')');
				data = data.replace(/]/g, ')');

				// Replace characters " with '
				data = data.replace(/"/g, "'");

				// Replace characters : with => if within ''
				data = data.replace(/'([^']*)':/g, "'$1' => ");

				document.querySelector('code').innerText = data;
				document.querySelector('#copy-code').innerText = 'Copy PHP code';
			}
			/**
			 * Handle a request to convert WPHTML to a string representation of
			 * a PHP array.
			 *
			 * @param {Event} evt
			 */
			const handleConvertTemplate = (evt) => {
			// Remove any newlines or tabs to avoid null blocks.
			let wphtml = document.querySelector('textarea').value.replace(/\n|\t/g, '');

// Parse the HTML into a list of blocks.
const blocks = parse(wphtml);
//console.log(blocks);
const blockstring = JSON.stringify(blocks);
//console.log(blockstring);
const blockVariation = generateBlockVariation(blockstring  );
// let data = '';

// blocks.forEach(block => { data += parseBlock(block) });

// data = format(data);

// 				
			
				// // Replace characters { and [ with their PHP counterparts.
				// data = data.replace(/{/g, 'array(');
				// data = data.replace(/\[/g, 'array(');

				// // Replace characters } and ] with their PHP counterparts.
				// data = data.replace(/}/g, ')');
				// data = data.replace(/]/g, ')');

				// // Replace characters " with '
				// 

				// // Replace characters : with => if within ''
				// data = data.replace(/'([^']*)':/g, "'$1' => ");

				document.querySelector('code').innerText =blockVariation ;
				document.querySelector('#copy-code').innerText = 'Copy Template code';
			}
			document.querySelector('#convert-to-js').addEventListener('click', handleConvertJS);
			document.querySelector('#convert-to-php').addEventListener('click', handleConvertPHP);
			document.querySelector('#convert-to-template').addEventListener('click', handleConvertTemplate);

			document.querySelector('#copy-code').addEventListener('click', (evt) => {
				evt.preventDefault();

				navigator.clipboard.writeText(document.querySelector('#generated-code').innerText);
			})
		}
	</script>