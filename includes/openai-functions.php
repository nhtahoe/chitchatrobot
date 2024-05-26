<?php
function create_thread($api_key, &$logs) {
    $url = 'https://api.openai.com/v1/threads';
    $response = wp_remote_post($url, array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
            'OpenAI-Beta' => 'assistants=v2',
        ),
        'body' => json_encode(array()),
    ));

    if (is_wp_error($response)) {
        $logs[] = 'Error creating thread: ' . $response->get_error_message();
        return null;
    } else {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $logs[] = 'Create thread response: ' . print_r($body, true);
        return $body['id'];
    }
}

function create_message($api_key, $thread_id, $message, &$logs) {
    $url = 'https://api.openai.com/v1/threads/' . $thread_id . '/messages';
    $body_data = json_encode(array(
        'role' => 'user',
        'content' => array(
            array(
                'type' => 'text',
                'text' => $message,
            ),
        ),
    ));
    $logs[] = 'Create message request: ' . $body_data;
    $response = wp_remote_post($url, array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
            'OpenAI-Beta' => 'assistants=v2',
        ),
        'body' => $body_data,
    ));

    if (is_wp_error($response)) {
        $logs[] = 'Error creating message: ' . $response->get_error_message();
        return null;
    } else {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $logs[] = 'Create message response: ' . print_r($body, true);
        return $body['id'];
    }
}

function create_run($api_key, $assistant_id, $thread_id, &$logs) {
    $url = 'https://api.openai.com/v1/threads/' . $thread_id . '/runs';
    $body_data = json_encode(array(
        'assistant_id' => $assistant_id,
    ));
    $logs[] = 'Create run request: ' . $body_data;
    $response = wp_remote_post($url, array(
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer ' . $api_key,
            'OpenAI-Beta' => 'assistants=v2',
        ),
        'body' => $body_data,
    ));

    if (is_wp_error($response)) {
        $logs[] = 'Error creating run: ' . $response->get_error_message();
        return null;
    } else {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $logs[] = 'Create run response: ' . print_r($body, true);
        return $body['id'];
    }
}

function poll_run($api_key, $thread_id, $run_id, &$logs) {
    $url = 'https://api.openai.com/v1/threads/' . $thread_id . '/runs/' . $run_id;
    $max_attempts = 10;
    $attempt = 0;
    $response = null;

    do {
        $response = wp_remote_get($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'OpenAI-Beta' => 'assistants=v2',
            ),
        ));

        if (is_wp_error($response)) {
            $logs[] = 'Error polling run: ' . $response->get_error_message();
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status = $body['status'] ?? 'in_progress';
        $logs[] = 'Polling run response: ' . print_r($body, true);

        if ($status == 'completed') {
            break;
        }

        sleep(2); // Sleep for 2 seconds before the next poll attempt
        $attempt++;
    } while ($status != 'completed' && $attempt < $max_attempts);

    return $response;
}

function extract_reply_from_run($api_key, $thread_id, $run_id, &$logs) {
    $url = 'https://api.openai.com/v1/threads/' . $thread_id . '/runs/' . $run_id . '/steps';
    $response = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'OpenAI-Beta' => 'assistants=v2',
        ),
    ));

    if (is_wp_error($response)) {
        $logs[] = 'Error extracting reply from run: ' . $response->get_error_message();
        return null;
    } else {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $logs[] = 'Retrieve run steps response: ' . print_r($body, true);

        if (isset($body['data']) && is_array($body['data'])) {
            foreach ($body['data'] as $step) {
                if ($step['type'] == 'message_creation' && isset($step['step_details']['message_creation']['message_id'])) {
                    $message_id = $step['step_details']['message_creation']['message_id'];
                    return retrieve_message_content($api_key, $thread_id, $message_id, $logs);
                }
            }
        }
    }

    return null;
}

function retrieve_message_content($api_key, $thread_id, $message_id, &$logs) {
    $url = 'https://api.openai.com/v1/threads/' . $thread_id . '/messages/' . $message_id;
    $response = wp_remote_get($url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'OpenAI-Beta' => 'assistants=v2',
        ),
    ));

    if (is_wp_error($response)) {
        $logs[] = 'Error retrieving message content: ' . $response->get_error_message();
        return null;
    } else {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        $logs[] = 'Retrieve message content response: ' . print_r($body, true);

        if (isset($body['content']) && is_array($body['content'])) {
            foreach ($body['content'] as $content) {
                if ($content['type'] == 'text' && isset($content['text']['value'])) {
                    return $content['text']['value'];
                }
            }
        }
    }

    return null;
}

function clean_response($response) {
    // Remove everything after the first occurrence of "【"
    $position = strpos($response, '【');
    if ($position !== false) {
        $response = substr($response, 0, $position);
    }
    // Ensure the response ends with a period
    $response = rtrim($response, " \t\n\r\0\x0B.") . '.';
    return $response;
}
?>
