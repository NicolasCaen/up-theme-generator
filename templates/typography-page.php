<?php
if (!defined('ABSPATH')) {
    exit;
}
$nonce = wp_create_nonce('up_theme_generator_nonce');
?>

<div class="wrap">

    <h1>Ajouter de presets de polices</h1>

    <div class="typography-form">
       



        <div class="fonts-selection">
        <div class="form-group__cols form-group__cols--top">
        <div class="form-group__col">
            <label for="preset-name">Nom du preset :</label>
            <input type="text" id="preset-name" required placeholder="ex: Modern & Classic">
        </div>
            <div class="form-group__col">
            <label for="theme-selector">Sélectionner un thème :</label>
  
            <select id="theme-selector" select name="theme" onchange="this.form.submit()">
                <option value="">Choisir un thème</option>
                <?php foreach ($themes as $theme_slug => $theme) : ?>
                    <?php if (strpos($theme_slug, 'backups') === false) : ?>
                        <option value="<?php echo esc_attr($theme_slug); ?>">
                            <?php echo esc_html($theme->get('Name')); ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        </div>
            <h3>Sélection des polices (maximum 3)</h3>
            <div id="font-selectors">
                <div class="font-selector">
                    <label>Police 1 (Primary) :</label>
                    <select class="font-select font-family-select" required>
                        <option value="">Choisir une police</option>
                        <?php foreach ($fonts as $font) : ?>
                            <option value="<?php echo esc_attr($font['name']); ?>">
                                <?php echo esc_html($font['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <button type="button" id="add-font" class="button">Ajouter une police</button>
        </div>


        <div class="form-actions">
            <button type="button" id="save-preset" class="button button-primary">
                Créer le preset
            </button>
        </div>
    </div>
</div>

<style>
.typography-form {
    max-width: 800px;
    margin-top: 20px;
}
.form-group {
    margin-bottom: 20px;
}
.form-group__cols {
   display: flex;
   align-items: center;
   gap:2rem;

}
.form-group__cols--top {
    background: #dddddd;
   margin: -20px -20px 20px -20px;
   padding: 1rem;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
}
.font-selector {
    margin-bottom: 15px;
}
.fonts-selection {
    margin: 20px 0;
    padding: 20px;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 4px;
}
</style>


    <div class="wrap" style='margin-top:7.5rem'>
        <h1>Liste des presets disponibles</h1>



        <?php settings_errors('typography_presets'); ?>


            <?php
            $preset_dir = WP_CONTENT_DIR . '/themes/' . $selected_theme . '/styles/typography/';
            $preset_files = glob($preset_dir . '*.json');
            ?>

            <div class="up-theme-generator__info">
        <h3>Seléctionner un Thème</h3>
        <!-- Theme Selector Form -->
        <form method="get" action="">
            <input type="hidden" name="page" value="up-theme-generator-typography">
            <select name="theme" onchange="this.form.submit()">
                <option value="">Sélectionner un thème</option>
                <?php foreach ($themes as $theme) : ?>
                    <?php $selected = ($theme->get_stylesheet() === $selected_theme) ? 'selected' : ''; ?>
                    <option value="<?php echo esc_attr($theme->get_stylesheet()); ?>" <?php echo $selected; ?>>
                        <?php echo esc_html($theme->get('Name')); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
        <p><strong>Si vous appliquer le preset au thème</strong>, il remplacera les polices dans le thème.json (first / second / third)</p>
    </div>
            <!-- Display Presets if a Theme is Selected -->
            <?php if (!empty($selected_theme)) : ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Nom du Preset</th>
                        <th>First Font</th>
                        <th>Second Font</th>
                        <th>Third Font</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($preset_files)) : ?>
                        <tr><td colspan="5">Aucun preset de typographie trouvé pour ce thème.</td></tr>
                    <?php else : ?>
                        <?php foreach ($preset_files as $file) : ?>
                            <?php
                            $preset_data = json_decode(file_get_contents($file), true);
                            $preset_name = basename($file, '.json');
                            ?>
                            <tr>
                                <td><?php echo esc_html($preset_data['title'] ?? $preset_name); ?></td>
                                <?php foreach (['first', 'second', 'third'] as $font_key) : ?>
                                    <td>
                                        <?php if (isset($preset_data['settings']['typography']['fontFamilies'])) : ?>
                                            <?php foreach ($preset_data['settings']['typography']['fontFamilies'] as $font) : ?>
                                                <?php if ($font['slug'] === $font_key) : ?>
                                                    <strong>Famille:</strong> <?php echo esc_html($font['fontFamily']); ?><br>
                                                    <strong>Nom:</strong> <?php echo esc_html($font['name']); ?><br>
                                                    <?php if (!empty($font['fontFace'])) : ?>
                                                        <strong>Variantes:</strong> <?php echo count($font['fontFace']); ?>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </td>
                                <?php endforeach; ?>
                                <td>
                                    <form method="post">
                                        <input type="hidden" name="preset_file" value="<?php echo esc_attr($preset_name); ?>">
                                        <?php wp_nonce_field('apply_typography_preset', 'typography_preset_nonce'); ?>
                                        <input type="submit" name="apply_preset" class="button button-primary" value="Appliquer le preset au thème">
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="notice notice-warning"><p>Veuillez sélectionner un thème pour voir les presets de typographie disponibles.</p></div>
        <?php endif; ?>
    </div>

