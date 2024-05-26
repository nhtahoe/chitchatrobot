<?php
// Handle the OpenAI API request
function openai_assistant_handle_request() {
    $api_key = get_option('openai_assistant_api_key', ''); // Retrieve OpenAI API key from settings
    $assistant_id = get_option('openai_assistant_id', ''); // Retrieve Assistant ID from settings

    if (empty($api_key) || empty($assistant_id)) {
        wp_send_json_error(['message' => 'OpenAI API Key and Assistant ID must be set in the plugin settings for the assistant to work.']);
        return;
    }

    $message = sanitize_text_field($_POST['message']);
    $session_id = sanitize_text_field($_POST['session_id']);
    $logging_enabled = get_option('openai_assistant_logging', 'off') === 'on';

    $logs = [];

    if ($logging_enabled) {
        error_log('Received message: ' . $message);
    }

    // Retrieve or create thread
    $thread_id = get_transient($session_id . '_thread_id');
    if (!$thread_id) {
        $thread_id = create_thread($api_key, $logs);
        set_transient($session_id . '_thread_id', $thread_id, 2 * HOUR_IN_SECONDS);
    }

    // Create a new message in the thread
    $message_id = create_message($api_key, $thread_id, $message, $logs);

    // Create a run to process the message
    $run_id = create_run($api_key, $assistant_id, $thread_id, $logs);

    // Poll for the run result
    $response = poll_run($api_key, $thread_id, $run_id, $logs);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'There was an error with the request.', 'error' => $response->get_error_message(), 'logs' => $logging_enabled ? $logs : []]);
    } else {
        $body = json_decode(wp_remote_retrieve_body($response), true);

        // Log the response for debugging
        if ($logging_enabled) {
            $logs[] = 'OpenAI API response: ' . print_r($body, true);
        }

        // Extract the reply from the run steps
        $assistant_reply = extract_reply_from_run($api_key, $thread_id, $run_id, $logs);

        // Clean the response
        $cleaned_reply = clean_response($assistant_reply);

        if ($cleaned_reply) {
            wp_send_json_success(['reply' => $cleaned_reply, 'thread_id' => $thread_id, 'logs' => $logging_enabled ? $logs : []]);
        } else {
            wp_send_json_success(['reply' => null, 'debug' => $body, 'logs' => $logging_enabled ? $logs : []]);
        }
    }
}
add_action('wp_ajax_openai_assistant_request', 'openai_assistant_handle_request');
add_action('wp_ajax_nopriv_openai_assistant_request', 'openai_assistant_handle_request');

function openai_assistant_get_messages() {
    $api_key = get_option('openai_assistant_api_key');
    $assistant_id = get_option('openai_assistant_id');
    $thread_id = sanitize_text_field($_POST['thread_id']);

    if (empty($thread_id)) {
        wp_send_json_error(array('message' => 'Invalid thread ID.'));
        return;
    }

    $url = "https://api.openai.com/v1/threads/{$thread_id}/messages";
    $response = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ),
    ));

    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Error fetching messages.'));
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['data'])) {
        wp_send_json_success($data['data']);
    } else {
        wp_send_json_error(array('message' => 'Error fetching messages.'));
    }
}
add_action('wp_ajax_openai_assistant_get_messages', 'openai_assistant_get_messages');
add_action('wp_ajax_nopriv_openai_assistant_get_messages', 'openai_assistant_get_messages');

function openai_assistant_create_thread() {
    $api_key = get_option('openai_assistant_api_key', '');
    $assistant_id = get_option('openai_assistant_id', '');

    if (empty($api_key) || empty($assistant_id)) {
        wp_send_json_error(['message' => 'OpenAI API Key and Assistant ID must be set in the plugin settings for the assistant to work.']);
        return;
    }

    $logs = [];

    $thread_id = create_thread($api_key, $logs);

    if ($thread_id) {
        wp_send_json_success(['thread_id' => $thread_id]);
    } else {
        wp_send_json_error(['message' => 'Error creating thread.']);
    }
}
add_action('wp_ajax_openai_assistant_create_thread', 'openai_assistant_create_thread');
add_action('wp_ajax_nopriv_openai_assistant_create_thread', 'openai_assistant_create_thread');

?>
