<?php
/**
 * Plugin Name: WP Rewriter
 * Plugin URI: https://www.nicheassistant.com/wp-rewiter
 * Description: A plugin that allows you to rewrite content using API.
 * Version: 1.0
 * Author: Johan @ Niche Assistant
 * Author URI: https://www.nicheassistant.com
 * License: GPL2
 */

// Enqueue the JavaScript file in the block editor
function wp_rewrite_enqueue_assets()
{
    // Ensure that the script is only enqueued in the block editor.
    if (!is_admin() || get_current_screen()->base !== 'post') {
        return;
    }

    wp_enqueue_script(
        'wp-rewrite-js',
        plugin_dir_url(__FILE__) . 'wp-rewrite.js',
        array('wp-edit-post', 'wp-plugins', 'wp-components', 'wp-compose', 'wp-data', 'wp-element', 'wp-rich-text', 'jquery'),
        filemtime(plugin_dir_path(__FILE__) . 'wp-rewrite.js'),
        true
    );
}

add_action('enqueue_block_editor_assets', 'wp_rewrite_enqueue_assets');

// Add a menu item for the ChatGPT API settings
function wp_rewrite_menu()
{
    add_options_page(
        'WP Rewriter Settings',
        'WP Rewriter',
        'manage_options',
        'wp-api',
        'wp_rewrite_options'
    );
}

add_action('admin_menu', 'wp_rewrite_menu');

// Render the WP Rewrite API settings page
function wp_rewrite_options()
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

    // Fetch the message of the day
    $messageOfTheDay = '';
    $response = wp_remote_get('https://api.nicheassistant.com/message_of_the_day');
    if (!is_wp_error($response) && $response['response']['code'] == 200) {
        $body = json_decode($response['body'], true);
        if (isset($body['message'])) {
            $messageOfTheDay = $body['message'];
        }
    }

    ?>
    <div class="wrap">
        <h1>WP Rewriter Settings</h1>
        <p>WP Rewriter is a small and simple tool to help you paraphrase or rewrite your content.</p>
        <p>More tools and updates at <a href="https://www.nicheassistant.com" target="_blank">Niche Assistant homepage</a>.</p>
        <h2><strong>Latest news</strong></h2>
        <p><?php echo esc_html($messageOfTheDay); ?></p>
        <h2>API keys</h2>
        <p>WP Rewriter uses ChatGPT in order to do the text rewrite, please enter your API key below. If you dont already have one you can signup and
            request one at <a href="https://openai.com/" target="_blank">OpenAI</a>. If you need help, reach out to us
                on Twitter <a href="https://twitter.com/niche_assistant" target="_blank">@niche_assistant</a></p>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">ChatGPT API Key</th>
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
function wp_rewrite_register_routes()
{
    register_rest_route('wp-rewrite/v1', '/apikey', array(
        'methods' => 'GET',
        'callback' => 'wp_rewrite_get_api_key',
    ));
}

add_action('rest_api_init', 'wp_rewrite_register_routes');

// Callback for the API key REST API endpoint
function wp_rewrite_get_api_key()
{
    $key = get_option('chatgpt_api_key', '');
    $key = str_replace('"', '', $key);
    error_log("Key: ", $key);
    return $key;
}
