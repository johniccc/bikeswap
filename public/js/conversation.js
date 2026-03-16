/**
 * Conversation utilities — auto-scroll message thread to bottom on load.
 * Works with any element that has class "conversation-messages".
 */
(function () {
    'use strict';

    var container = document.querySelector('.conversation-messages');
    if (container) {
        container.scrollTop = container.scrollHeight;
    }
})();
