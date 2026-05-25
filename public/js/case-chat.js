(function () {
    var root = document.getElementById('case-chat-root');
    if (!root) return;

    var toggle = document.getElementById('case-chat-toggle');
    var widget = document.getElementById('case-chat-widget');
    var closeBtn = document.getElementById('case-chat-close');
    var messagesEl = document.getElementById('case-chat-messages');
    var form = document.getElementById('case-chat-form');
    var input = document.getElementById('case-chat-input');

    var caseId = root.dataset.caseId;
    var caseRef = root.dataset.caseRef || '';
    var indexUrl = root.dataset.indexUrl;
    var storeUrl = root.dataset.storeUrl;
    var readUrl = root.dataset.readUrl;
    var currentUserId = parseInt(root.dataset.currentUserId, 10);
    var partnerName = root.dataset.partnerName || '';
    var csrf = root.dataset.csrf;
    var loaded = false;
    var echoSubscribed = false;
    var pollTimer = null;

    var tickSentSvg = '<svg class="case-chat-tick-icon" viewBox="0 0 12 11" width="14" height="13" aria-hidden="true">' +
        '<path fill="currentColor" d="M11.2 1.4 4.4 8.2 1.5 5.3.8 6l3.6 3.6 7.4-7.4-.8-.8z"/>' +
        '</svg>';

    var tickReadSvg = '<svg class="case-chat-tick-icon" viewBox="0 0 16 11" width="16" height="13" aria-hidden="true">' +
        '<path fill="currentColor" d="M11.2 1.4 4.4 8.2 1.5 5.3.8 6l3.6 3.6 7.4-7.4-.8-.8z"/>' +
        '<path fill="currentColor" d="M15.2 1.4 8.4 8.2 7.6 7.4l6.6-6.6-.8-.8z"/>' +
        '</svg>';

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function isRead(msg) {
        return !!(msg.is_read || msg.read_at);
    }

    function renderTicks(msg, isMine) {
        if (!isMine) return '';
        var state = isRead(msg) ? 'is-read' : 'is-sent';
        var label = isRead(msg) ? 'Seen' : 'Sent';
        var icon = state === 'is-read' ? tickReadSvg : tickSentSvg;
        return '<span class="case-chat-ticks ' + state + '" aria-label="' + label + '">' + icon + '</span>';
    }

    function scrollToBottom() {
        if (!messagesEl) return;
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function messageExists(id) {
        return !!findBubble(id);
    }

    function findBubble(id) {
        return messagesEl.querySelector('.case-chat-bubble-wrap[data-msg-id="' + id + '"]');
    }

    function setTicksRead(messageId) {
        var wrap = findBubble(messageId);
        if (!wrap) return;
        var ticks = wrap.querySelector('.case-chat-ticks');
        if (!ticks) return;
        ticks.classList.remove('is-sent');
        ticks.classList.add('is-read');
        ticks.setAttribute('aria-label', 'Seen');
        ticks.innerHTML = tickReadSvg;
    }

    function applyReadReceipts(messageIds) {
        (messageIds || []).forEach(setTicksRead);
    }

    function renderBubble(msg) {
        var isMine = parseInt(msg.sender_id, 10) === currentUserId;
        var time = msg.created_at_label || '';
        var ticks = renderTicks(msg, isMine);

        return (
            '<div class="case-chat-bubble-wrap' + (isMine ? ' is-mine' : ' is-theirs') + '" data-msg-id="' + escapeHtml(String(msg.id)) + '">' +
                '<div class="case-chat-bubble">' +
                    '<p class="case-chat-bubble-text">' + escapeHtml(msg.body) + '</p>' +
                    '<div class="case-chat-bubble-foot">' +
                        '<span class="case-chat-bubble-time">' + escapeHtml(time) + '</span>' +
                        ticks +
                    '</div>' +
                '</div>' +
            '</div>'
        );
    }

    function appendMessage(msg) {
        if (!messagesEl) return;
        if (messageExists(msg.id)) {
            if (isRead(msg)) setTicksRead(msg.id);
            return;
        }

        var empty = messagesEl.querySelector('.case-chat-empty');
        var loading = messagesEl.querySelector('.case-chat-loading');
        if (empty) empty.remove();
        if (loading) loading.remove();

        var isMine = parseInt(msg.sender_id, 10) === currentUserId;
        messagesEl.insertAdjacentHTML('beforeend', renderBubble(msg));
        scrollToBottom();

        if (!isMine && widget && !widget.hidden) {
            markAsRead();
        }
    }

    function renderAll(messages) {
        if (!messages.length) {
            messagesEl.innerHTML = '<p class="case-chat-empty">No messages yet. Say hello!</p>';
            return;
        }
        messagesEl.innerHTML = messages.map(renderBubble).join('');
        scrollToBottom();
    }

    function fetchJson(url, method, body) {
        var opts = {
            method: method || 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        };
        if (body) {
            opts.body = body;
        }
        return fetch(url, opts).then(function (r) {
            if (!r.ok) {
                return r.json().catch(function () { return {}; }).then(function (data) {
                    throw new Error(data.message || 'Request failed');
                });
            }
            return r.json();
        });
    }

    function markAsRead() {
        if (!readUrl) return Promise.resolve();
        return fetchJson(readUrl, 'POST').then(function (data) {
            applyReadReceipts(data.read_message_ids || []);
        }).catch(function () {});
    }

    function loadMessages() {
        return fetchJson(indexUrl).then(function (data) {
            if (data.chat_partner && data.chat_partner.name) {
                partnerName = data.chat_partner.name;
            }
            renderAll(data.messages || []);
            loaded = true;
            return markAsRead();
        }).catch(function () {
            messagesEl.innerHTML = '<p class="case-chat-empty">Could not load messages.</p>';
        });
    }

    function syncReadState(messages) {
        (messages || []).forEach(function (msg) {
            if (parseInt(msg.sender_id, 10) === currentUserId && isRead(msg)) {
                setTicksRead(msg.id);
            }
        });
    }

    function startPolling() {
        if (pollTimer) return;
        pollTimer = window.setInterval(function () {
            if (!widget || widget.hidden) return;
            fetchJson(indexUrl).then(function (data) {
                (data.messages || []).forEach(appendMessage);
                syncReadState(data.messages || []);
            }).catch(function () {});
        }, 5000);
    }

    function stopPolling() {
        if (pollTimer) {
            window.clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    function handleMessagesRead(payload) {
        if (String(payload.case_id) !== String(caseId)) return;
        applyReadReceipts(payload.message_ids || []);
    }

    function subscribeEcho() {
        if (echoSubscribed) return;
        var echo = typeof window.getAretiaEcho === 'function' ? window.getAretiaEcho(csrf) : null;
        if (!echo) return;

        echoSubscribed = true;

        echo.private('case.' + caseId)
            .listen('.message.sent', function (payload) {
                if (String(payload.case_id) === String(caseId)) {
                    appendMessage(payload);
                }
            })
            .listen('.messages.read', handleMessagesRead);

        if (window.AretiaBroadcast && window.AretiaBroadcast.userId) {
            echo.private('App.Models.User.' + window.AretiaBroadcast.userId)
                .listen('.message.sent', function (payload) {
                    if (String(payload.case_id) === String(caseId)) {
                        appendMessage(payload);
                    }
                })
                .listen('.messages.read', handleMessagesRead);
        }
    }

    function openChat() {
        if (!widget) return;
        widget.hidden = false;
        widget.setAttribute('aria-hidden', 'false');
        if (toggle) toggle.setAttribute('aria-expanded', 'true');
        var load = loaded ? Promise.resolve().then(markAsRead) : loadMessages();
        load.then(function () {
            subscribeEcho();
            startPolling();
        });
        setTimeout(scrollToBottom, 50);
        if (input) input.focus();
    }

    function closeChat() {
        if (!widget) return;
        widget.hidden = true;
        widget.setAttribute('aria-hidden', 'true');
        if (toggle) toggle.setAttribute('aria-expanded', 'false');
        stopPolling();
    }

    if (toggle) {
        toggle.addEventListener('click', function () {
            if (widget && widget.hidden) {
                openChat();
            } else {
                closeChat();
            }
        });
    }

    if (closeBtn) {
        closeBtn.addEventListener('click', closeChat);
    }

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var body = (input && input.value || '').trim();
            if (!body) return;

            var sendBtn = form.querySelector('.case-chat-send');
            if (sendBtn) sendBtn.disabled = true;

            var fd = new FormData();
            fd.append('body', body);

            fetchJson(storeUrl, 'POST', fd)
                .then(function (data) {
                    if (data.message) appendMessage(data.message);
                    if (input) {
                        input.value = '';
                        input.focus();
                    }
                })
                .catch(function () {
                    if (typeof window.showToast === 'function') {
                        window.showToast('error', 'Could not send message. Try again.', { title: 'Chat' });
                    }
                })
                .finally(function () {
                    if (sendBtn) sendBtn.disabled = false;
                });
        });

        if (input) {
            input.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    form.requestSubmit();
                }
            });
        }
    }

    document.addEventListener('aretia:case-message', function (e) {
        var payload = e.detail || {};
        if (String(payload.case_id) === String(caseId) && payload.message_id) {
            loadMessages();
        }
    });

    subscribeEcho();
    startPolling();

    if (new URLSearchParams(window.location.search).get('chat') === '1') {
        openChat();
    }
})();
