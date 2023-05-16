<?php
/**
 * Plugin Name: ChatGPT Rewrite
 * Plugin URI: https://www.nicheassistant.com/wp-rewiter
 * Description: A plugin that allows you to rewrite content using API.
 * Version: 1.0
 * Author: Johan @ Niche Assistant
 * Author URI: https://www.nicheassistant.com
 * License: GPL2
 */

// Enqueue the JavaScript file in the block editor
function chatgpt_rewrite_enqueue_assets()
{
    // Ensure that the script is only enqueued in the block editor.
    if (!is_admin() || get_current_screen()->base !== 'post') {
        return;
    }

    wp_enqueue_script(
        'chatgpt-rewrite-js',
        plugin_dir_url(__FILE__) . 'chatgpt-rewrite.js',
        array('wp-edit-post', 'wp-plugins', 'wp-components', 'wp-compose', 'wp-data', 'wp-element', 'wp-rich-text', 'jquery'),
        filemtime(plugin_dir_path(__FILE__) . 'chatgpt-rewrite.js'),
        true
    );
}

add_action('enqueue_block_editor_assets', 'chatgpt_rewrite_enqueue_assets');

// Add a menu item for the ChatGPT API settings
function chatgpt_rewrite_menu()
{
    add_options_page(
        'ChatGPT API Settings',
        'ChatGPT API',
        'manage_options',
        'chatgpt-api',
        'chatgpt_rewrite_options'
    );
}

add_action('admin_menu', 'chatgpt_rewrite_menu');

// Render the ChatGPT API settings page
function chatgpt_rewrite_options()
{
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this page.');
    }

    // Save API Key
    if (isset($_POST['chatgpt_api_key'])) {
        update_option('chatgpt_api_key', sanitize_text_field($_POST['chatgpt_api_key']));
    }

    // Fetch API Key
    $apiKey = get_option('chatgpt_api_key', '');

    ?>
    <div class="wrap">
        <h1>ChatGPT API Settings</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">API Key</th>
                    <td>
                        <input type="text" name="chatgpt_api_key" value="<?php echo esc_attr($apiKey); ?>"/>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register a REST API endpoint to get the API key
function chatgpt_rewrite_register_routes()
{
    register_rest_route('chatgpt/v1', '/apikey', array(
        'methods' => 'GET',
        'callback' => 'chatgpt_rewrite_get_api_key',
    ));
}

add_action('rest_api_init', 'chatgpt_rewrite_register_routes');

// Callback for the API key REST API endpoint
function chatgpt_rewrite_get_api_key()
{
    $key = get_option('chatgpt_api_key', '');
    $key = str_replace('"', '', $key);
    error_log("Key: ", $key);
    return $key;
}
