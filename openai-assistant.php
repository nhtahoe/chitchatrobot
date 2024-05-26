<?php
/*
Plugin Name: OpenAI Assistant Page Embed
Plugin URI: https://strongnorthtahoe.org/
Description: Adds a chat interface for an OpenAI assistant to your WordPress site via shortcode
Version: 1.0
Author: Nick Harris 	
Author URI: https://strongnorthtahoe.org
License: GPL2
*/

// Include necessary files
include plugin_dir_path(__FILE__) . 'includes/admin-settings.php';
include plugin_dir_path(__FILE__) . 'includes/ajax-handler.php';
include plugin_dir_path(__FILE__) . 'includes/openai-functions.php';

// Enqueue the JavaScript and CSS files only if the shortcode is present
function openai_assistant_enqueue_scripts() {
    wp_enqueue_script('openai-assistant-script', plugin_dir_url(__FILE__) . 'assets/openai-assistant.js', array('jquery'), '1.00', true);
    
    // Localize the script with the variables
    $localize_script = array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'max_height' => get_option('openai_assistant_max_height', 500),
        'assistant_name' => get_option('openai_assistant_name', 'Assistant'),
    );

    // Localize the script only if the necessary settings are set
    if (!empty($localize_script['ajax_url']) && !empty($localize_script['max_height']) && !empty($localize_script['assistant_name'])) {
        wp_localize_script('openai-assistant-script', 'openai_assistant_vars', $localize_script);
    }
    
    wp_enqueue_style('openai-assistant-style', plugin_dir_url(__FILE__) . 'assets/openai-assistant.css', array(), '1.00');
}
add_action('wp_enqueue_scripts', 'openai_assistant_enqueue_scripts');

function openai_assistant_shortcode() {
    $api_key = get_option('openai_assistant_api_key', '');
    $assistant_id = get_option('openai_assistant_id', '');
    $max_height = get_option('openai_assistant_max_height', 500);

    if (empty($api_key) || empty($assistant_id)) {
        return '<div id="openai-assistant-container">
                    <p style="color: red;">The OpenAI API Key and Assistant ID must be set in the plugin settings for the assistant to work.</p>
                </div>';
    }

    return '<div id="openai-assistant-container">
                <div id="chat-box"><div class="placeholder">Ask me anything!</div></div>
                <div id="input-container">
                    <input type="text" id="chat-input" placeholder="Type your message...">
                    <button id="send-button" class="button">Send</button>
                </div>
            </div>';
}
add_shortcode('openai_assistant', 'openai_assistant_shortcode');
?>
