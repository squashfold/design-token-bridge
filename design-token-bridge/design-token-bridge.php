<?php 
/**
 * Plugin Name:       Design Token Bridge
 * Plugin URI:        https://github.com/squashfold/design-token-bridge
 * Description:       Converts JSON design tokens to CSS variables.
 * Version:           1.1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Charlie Prince
 * Author URI:        https://squash-fold.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

add_action('admin_menu', 'dtb_add_settings_page');

function dtb_add_settings_page() {
    add_menu_page(
        'Design Tokens',            
        'Design Token Bridge',      
        'manage_options',           
        'dtb-tokens',               
        'dtb_render_tokens_page',   
        'dashicons-admin-generic',  
        100                         
    );
}

add_action('admin_init', 'dtb_settings_init');

function dtb_settings_init() {
    register_setting('dtb_settings_group', 'dtb_token_json');
    register_setting('dtb_settings_group', 'dtb_convert_px_to_rem');
    register_setting('dtb_settings_group', 'dtb_default_rem_size');

    add_settings_section(
        'dtb_settings_section',    
        'Design Token Settings',   
        'dtb_settings_section_cb', 
        'dtb-settings'       
    );

    add_settings_field(
        'dtb_field_name',         
        'Design Tokens JSON',     
        'dtb_field_cb',           
        'dtb-settings',     
        'dtb_settings_section'    
    );
    
    add_settings_field(
        'dtb_convert_px_to_rem', 
        'Convert PX to REM',     
        'dtb_convert_px_to_rem_cb',
        'dtb-settings',
        'dtb_settings_section'
    );

    add_settings_field(
        'dtb_default_rem_size', 
        'Default REM Base Size', 
        'dtb_default_rem_size_cb',
        'dtb-settings',
        'dtb_settings_section'
    );
}

function dtb_settings_section_cb() {
    echo '<p>Insert JSON containing design tokens exported from your favourite design tool and adjust the settings below.</p>';
}

function dtb_field_cb() {
    $setting = get_option('dtb_token_json');
    ?>
    <textarea style="width:100%;max-width:600px" name="dtb_token_json" rows="10" cols="50">
        <?php echo isset($setting) ? esc_textarea($setting) : ''; ?>
    </textarea>
    <?php
}

function dtb_convert_px_to_rem_cb() {
    $convert = get_option('dtb_convert_px_to_rem', 1); // Default is checked
    ?>
    <input type="checkbox" name="dtb_convert_px_to_rem" value="1" <?php checked(1, $convert, true); ?> />
    <label for="dtb_convert_px_to_rem">Convert PX to REM</label>
    <?php
}

function dtb_default_rem_size_cb() {
    $rem_size = get_option('dtb_default_rem_size', 16); // Default is 16
    ?>
    <input type="number" name="dtb_default_rem_size" value="<?php echo esc_attr($rem_size); ?>" min="1" />
    <label for="dtb_default_rem_size">Base size in pixels (px) for REM conversion.</label>
    <?php
}

function json_to_css_variables($json_input) {
    $tokens = json_decode($json_input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return '';
    }

    $css_vars = [];
    $convert_px_to_rem = get_option('dtb_convert_px_to_rem', 1);
    $default_rem_size = get_option('dtb_default_rem_size', 16);

    function parse_tokens($tokens, $prefix = '', &$css_vars, $convert_px_to_rem, $default_rem_size) {
        foreach ($tokens as $key => $value) {
            if (is_array($value) && isset($value['$value'])) {
                $variable_name = strtolower(trim($prefix . '-' . str_replace(' ', '-', $key), '-'));
                $variable_value = $value['$value'];

                if (isset($value['$type']) && $value['$type'] === 'number') {
                    if (is_numeric($variable_value)) {
                        if ($convert_px_to_rem) {
                            $variable_value = ($variable_value / $default_rem_size) . 'rem';
                        } else {
                            $variable_value = $variable_value . 'px';
                        }
                    }
                }

                if (strpos($variable_value, '{') === 0 && strpos($variable_value, '}') === (strlen($variable_value) - 1)) {
                    $reference_key = strtolower(str_replace(['{', '}', '.', ' '], ['--', '--', '-', '-'], $variable_value));
                    $reference_key = preg_replace('/-+/', '-', $reference_key);
                    $reference_key = trim($reference_key, '-');
                    $variable_value = "var(--{$reference_key})";
                }

                $css_vars[$variable_name] = $variable_value;
            } elseif (is_array($value)) {
                parse_tokens($value, $prefix . '-' . str_replace(' ', '-', $key), $css_vars, $convert_px_to_rem, $default_rem_size);
            }
        }
    }

    parse_tokens($tokens, '', $css_vars, $convert_px_to_rem, $default_rem_size);

    $css_output = "<style id='dtb-tokens'>:root {\n";
    foreach ($css_vars as $var_name => $var_value) {
        $css_output .= "  --$var_name: $var_value;\n";
    }
    $css_output .= "}</style>";

    return $css_output;
}

function dtb_render_tokens_page() {
    $json_input = get_option('dtb_token_json');
    $css_output = json_to_css_variables($json_input);

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('dtb_settings_group');
            do_settings_sections('dtb-settings');
            submit_button('Import and Save');
            ?>
        </form>
        <h2>Generated CSS Variables</h2>
        <code>
        <?php
        echo htmlspecialchars($css_output, ENT_QUOTES, 'UTF-8');
        ?>
        </code>
    </div>
    <?php
}

add_action('wp_head', 'inject_design_tokens_css', 99);

function inject_design_tokens_css() {
    $json_input = get_option('dtb_token_json');
    $css_output = json_to_css_variables($json_input);
    echo "<!-- inject_design_tokens_css is called -->";
    echo htmlspecialchars($css_output, ENT_QUOTES, 'UTF-8');
}
