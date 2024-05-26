<?php
// Add the settings page to the admin menu
function openai_assistant_add_admin_menu() {
    add_options_page(
        'OpenAI Assistant Settings',
        'OpenAI Assistant',
        'manage_options',
        'openai-assistant-settings',
        'openai_assistant_settings_page'
    );
}
add_action('admin_menu', 'openai_assistant_add_admin_menu');

// Register settings and fields
function openai_assistant_settings_init() {
    register_setting('openai_assistant_settings_group', 'openai_assistant_api_key');
    register_setting('openai_assistant_settings_group', 'openai_assistant_id');
    register_setting('openai_assistant_settings_group', 'openai_assistant_max_height');
    register_setting('openai_assistant_settings_group', 'openai_assistant_name');

    add_settings_section(
        'openai_assistant_settings_section',
        'OpenAI Assistant Settings',
        'openai_assistant_settings_section_callback',
        'openai-assistant-settings'
    );

    add_settings_field(
        'openai_assistant_api_key',
        'OpenAI API Key',
        'openai_assistant_api_key_callback',
        'openai-assistant-settings',
        'openai_assistant_settings_section'
    );

    add_settings_field(
        'openai_assistant_id',
        'Assistant ID',
        'openai_assistant_id_callback',
        'openai-assistant-settings',
        'openai_assistant_settings_section'
    );

    add_settings_field(
        'openai_assistant_max_height',
        'Max Height (px)',
        'openai_assistant_max_height_callback',
        'openai-assistant-settings',
        'openai_assistant_settings_section'
    );

    add_settings_field(
        'openai_assistant_name',
        'Assistant Name',
        'openai_assistant_name_callback',
        'openai-assistant-settings',
        'openai_assistant_settings_section'
    );
}
add_action('admin_init', 'openai_assistant_settings_init');

function openai_assistant_settings_section_callback() {
    echo '<p>Settings for the OpenAI Assistant plugin.</p>';
}

function openai_assistant_api_key_callback() {
    $api_key = get_option('openai_assistant_api_key', '');
    echo '<input type="text" name="openai_assistant_api_key" value="' . esc_attr($api_key) . '" />';
}

function openai_assistant_id_callback() {
    $assistant_id = get_option('openai_assistant_id', '');
    echo '<input type="text" name="openai_assistant_id" value="' . esc_attr($assistant_id) . '" />';
}

function openai_assistant_max_height_callback() {
    $max_height = get_option('openai_assistant_max_height', 500);
    echo '<input type="number" name="openai_assistant_max_height" value="' . esc_attr($max_height) . '" />';
}

function openai_assistant_name_callback() {
    $assistant_name = get_option('openai_assistant_name', 'Assistant');
    echo '<input type="text" name="openai_assistant_name" value="' . esc_attr($assistant_name) . '" />';
}

function openai_assistant_settings_page() {
    ?>
    <div class="wrap">
        <h1>OpenAI Assistant Settings</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('openai_assistant_settings_group');
            do_settings_sections('openai-assistant-settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}
?>
