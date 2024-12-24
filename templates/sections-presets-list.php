<?php
if (!defined('ABSPATH')) {
    exit;
}

$preset_dir = WP_CONTENT_DIR . '/themes/' . $theme_slug . '/styles/sections/';
$preset_files = glob($preset_dir . '*.json');
?>

<h2>Presets de sections existants</h2>

<?php if (empty($preset_files)): ?>
    <p>Aucun preset trouvé.</p>
<?php else: ?>
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Types de blocs</th>
                <th>Aperçu des styles</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($preset_files as $file): 
                $preset_data = json_decode(file_get_contents($file), true);
                $preset_name = basename($file, '.json');
            ?>
                <tr>
                    <td><?php echo esc_html($preset_data['title'] ?? $preset_name); ?></td>
                    <td><?php echo esc_html(implode(', ', $preset_data['blockTypes'] ?? [])); ?></td>
                    <td>
                        <div class="style-preview">
                            <?php if (isset($preset_data['styles'])): ?>
                                <div class="color-preview">
                                    <span class="color-box" style="background-color: <?php echo esc_attr($preset_data['styles']['color']['background'] ?? ''); ?>"></span>
                                    <span>Fond</span>
                                </div>
                                <div class="color-preview">
                                    <span class="color-box" style="background-color: <?php echo esc_attr($preset_data['styles']['color']['text'] ?? ''); ?>"></span>
                                    <span>Texte</span>
                                </div>
                                <?php if (isset($preset_data['styles']['elements'])): ?>
                                    <div class="color-preview">
                                        <span class="color-box" style="background-color: <?php echo esc_attr($preset_data['styles']['elements']['button']['color']['background'] ?? ''); ?>"></span>
                                        <span>Bouton</span>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <div class="preset-actions">
                            <button class="button edit-preset" 
                                    data-preset="<?php echo esc_attr($preset_name); ?>"
                                    data-preset-data='<?php echo esc_attr(json_encode($preset_data)); ?>'>
                                Modifier
                            </button>
                            <button class="button duplicate-preset" 
                                    data-preset="<?php echo esc_attr($preset_name); ?>"
                                    data-preset-data='<?php echo esc_attr(json_encode($preset_data)); ?>'>
                                Dupliquer
                            </button>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="preset_file" value="<?php echo esc_attr($preset_name); ?>">
                                <?php wp_nonce_field('apply_section_preset', 'section_preset_nonce'); ?>
                                <input type="submit" name="apply_preset" class="button button-primary" value="Appliquer">
                            </form>
                            <button class="button delete-preset" data-preset="<?php echo esc_attr($preset_name); ?>">Supprimer</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?> 