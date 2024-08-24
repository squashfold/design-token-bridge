<?php 
/*
 * Plugin Name:       Design Token Bridge
 * Plugin URI:        https://example.com/plugins/the-basics/
 * Description:       Converts JSON design tokens to CSS variables.
 * Version:           1.0.0
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
        'Design Tokens',            // Page title
        'Design Token Bridge',      // Menu title
        'manage_options',           // Capability required to see this menu item
        'dtb-tokens',               // Menu slug
        'dtb_render_tokens_page',   // Callback function to display the settings page
        'dashicons-admin-generic',  // Icon (optional)
        100                         // Position (optional)
    );
}

add_action('admin_init', 'dtb_settings_init');

function dtb_settings_init() {
    // Register a setting
    register_setting('dtb_settings_group', 'dtb_token_json');

    // Dettings section
    add_settings_section(
        'dtb_settings_section',    // ID
        'Design Token Settings',   // Title
        'dtb_settings_section_cb', // Callback to display the section description (optional)
        'dtb-settings'       // Page (the same as menu slug)
    );

    // Settings field
    add_settings_field(
        'dtb_field_name',         // ID
        'Design Tokens JSON',     // Title
        'dtb_field_cb',           // Callback to display the field
        'dtb-settings',     // Page
        'dtb_settings_section'    // Section ID
    );
}

function dtb_settings_section_cb() {
    echo '<p>Insert JSON containing design tokens exported from your favourite design tool and click </p>';
}

function dtb_field_cb() {
    $setting = get_option('dtb_token_json');
    ?>
    <textarea style="width:100%;max-width:600px" name="dtb_token_json" rows="10" cols="50">
        <?php echo isset($setting) ? esc_textarea($setting) : ''; ?>
    </textarea>
    <?php
}

function json_to_css_variables($json_input) {
    $tokens = json_decode($json_input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return '';
    }

    $css_vars = [];

    function parse_tokens($tokens, $prefix = '', &$css_vars) {
        foreach ($tokens as $key => $value) {
            if (is_array($value) && isset($value['$value'])) {
                // Remove leading and trailing hyphens and build the variable name
                $variable_name = strtolower(trim($prefix . '-' . str_replace(' ', '-', $key), '-'));
                $variable_value = $value['$value'];

                // Format numeric values
                if (isset($value['$type']) && $value['$type'] === 'number') {
                    if (is_numeric($variable_value)) {
                        $variable_value = ($variable_value / 16) . 'rem';
                    }
                }

                // Handle references
                if (strpos($variable_value, '{') === 0 && strpos($variable_value, '}') === (strlen($variable_value) - 1)) {
                    $reference_key = strtolower(str_replace(['{', '}', '.', ' '], ['--', '--', '-', '-'], $variable_value));
                    $reference_key = preg_replace('/-+/', '-', $reference_key); // Remove extra hyphens
                    $reference_key = trim($reference_key, '-'); // Remove hyphens from both ends
                    $variable_value = "var(--{$reference_key})";
                }

                $css_vars[$variable_name] = $variable_value;
            } elseif (is_array($value)) {
                parse_tokens($value, $prefix . '-' . str_replace(' ', '-', $key), $css_vars);
            }
        }
    }

    parse_tokens($tokens, '', $css_vars);

    $css_output = "<style id='dtb-tokens'>:root {\n";
    foreach ($css_vars as $var_name => $var_value) {
        $css_output .= "  --$var_name: $var_value;\n";
    }
    $css_output .= "}</style>";

    return $css_output;
}


function dtb_render_tokens_page() {
    // Get the JSON input from the saved option
    $json_input = get_option('dtb_token_json');
    
    // Generate CSS variables
    $css_output = json_to_css_variables($json_input);

    ?>
    <div class="wrap">
        <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
        <form action="options.php" method="post">
            <?php
            // Security field
            settings_fields('dtb_settings_group');
            
            // Output settings sections and their fields
            do_settings_sections('dtb-settings');
            
            // Save settings button
            submit_button('Import and Save');
            ?>
        </form>
        <h2>Generated CSS Variables</h2>
        <code>
        <?php
        // Output the generated CSS
        echo strip_tags($css_output);
        ?>
        </code>
    </div>
    <?php
}


add_action('wp_head', 'inject_design_tokens_css', 99);

function inject_design_tokens_css() {
    $json_input = get_option('dtb_token_json');
    $css_output = json_to_css_variables($json_input);
    
    // Debug: Test if this function is being executed
    echo "<!-- inject_design_tokens_css is called -->";
    
    echo $css_output;
}
