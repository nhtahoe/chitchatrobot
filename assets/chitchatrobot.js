jQuery(document).ready(function($) {
    if (typeof openai_assistant_vars === 'undefined') {
        console.error('openai_assistant_vars is not defined');
        return;
    }

    $('#send-button').on('click', function() {
        var message = $('#chat-input').val();
        if (message.trim() !== '') {
            $('.placeholder').hide(); // Hide placeholder text after the first message is sent
            $('#chat-box').append('<div class="chat-message user-message"><strong>You:</strong> ' + message + '</div>');
            $('#chat-input').val('');
            scrollChatToBottom();

            // Add loading spinner
            var loadingSpinner = $('<div class="chat-message bot-message loading"><div class="spinner"></div><strong>' + openai_assistant_vars.assistant_name + ':</strong>&nbsp; is thinking...</div>');
            $('#chat-box').append(loadingSpinner);
            scrollChatToBottom();

            $.ajax({
                url: openai_assistant_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'openai_assistant_request',
                    message: message,
                    session_id: Date.now().toString() // Generate a unique session_id for each request
                },
                success: function(response) {
                    // Remove loading spinner
                    loadingSpinner.remove();

                    if (response.success) {
                        displayMessage({
                            role: 'assistant',
                            content: [{ text: { value: '<strong>' + openai_assistant_vars.assistant_name + ':</strong> ' + response.data.reply } }]
                        });
                        scrollChatToBottom();
                    } else {
                        console.error(response.data.message);
                    }
                }
            });
        }
    });

    $('#chat-input').on('keypress', function(e) {
        if (e.which === 13) { // Enter key pressed
            $('#send-button').click();
            return false; // Prevent default form submit
        }
    });

    function displayMessage(message) {
        var messageType = message.role === 'user' ? 'user-message' : 'bot-message';
        var messageContent = message.content.map(function(part) { return part.text.value; }).join(' ');
        $('#chat-box').append('<div class="chat-message ' + messageType + '">' + messageContent + '</div>');
        scrollChatToBottom();
    }

    function scrollChatToBottom() {
        $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight);
    }
});
