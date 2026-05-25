(function () {
    var wrap = document.getElementById('chat-inbox-bell');
    if (!wrap) return;

    var toggle = document.getElementById('chat-inbox-toggle');
    var dropdown = document.getElementById('chat-inbox-dropdown');
    var list = document.getElementById('chat-inbox-list');
    var badge = document.getElementById('chat-inbox-badge');
    var markAllBtn = document.getElementById('chat-inbox-mark-all');
    var indexUrl = wrap.dataset.indexUrl;
    var readAllUrl = wrap.dataset.readAllUrl;
    var csrf = wrap.dataset.csrf;
    var userId = parseInt(wrap.dataset.userId, 10);

    function postJson(url, method) {
        return fetch(url, {
            method: method || 'GET',
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        }).then(function (r) {
            return r.json();
        });
    }

    function updateBadge(count) {
        if (!badge) return;
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : String(count);
            badge.hidden = false;
        } else {
            badge.hidden = true;
        }
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function renderAvatar(item) {
        if (item.sender_avatar) {
            return '<img class="chat-inbox-avatar-img" src="' + escapeHtml(item.sender_avatar) + '" alt="" width="36" height="36" loading="lazy">';
        }
        var initial = item.sender_initial || (item.sender_name ? item.sender_name.charAt(0).toUpperCase() : '?');
        return '<span class="chat-inbox-avatar-initial" aria-hidden="true">' + escapeHtml(initial) + '</span>';
    }

    function renderList(items) {
        if (!items.length) {
            list.innerHTML =
                '<li class="notification-empty chat-inbox-empty">' +
                    '<span class="chat-inbox-empty-icon" aria-hidden="true">' +
                        '<svg width="40" height="40" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>' +
                        '</svg>' +
                    '</span>' +
                    '<p>No new messages</p>' +
                    '<span class="chat-inbox-empty-hint">When someone messages you, it will show here.</span>' +
                '</li>';
            return;
        }

        list.innerHTML = items.map(function (item) {
            var unread = !item.read_at;
            var unreadClass = unread ? ' notification-item-unread' : '';
            var href = item.url ? ' href="' + escapeHtml(item.url) + '"' : '';
            var dot = unread ? '<span class="notification-unread-dot" aria-hidden="true"></span>' : '';
            var caseRef = item.case_reference
                ? '<span class="chat-inbox-case-pill">' + escapeHtml(item.case_reference) + '</span>'
                : '';

            return (
                '<li class="notification-item chat-inbox-item' + unreadClass + '" data-id="' + escapeHtml(String(item.id)) + '">' +
                    '<div class="notification-item-inner">' +
                        '<a class="notification-item-main chat-inbox-item-main"' + href + '>' +
                            dot +
                            '<span class="chat-inbox-avatar">' + renderAvatar(item) + '</span>' +
                            '<div class="notification-content">' +
                                '<div class="chat-inbox-meta">' +
                                    '<p class="notification-title chat-inbox-sender">' + escapeHtml(item.sender_name) + '</p>' +
                                    caseRef +
                                '</div>' +
                                '<p class="notification-message chat-inbox-preview">' + escapeHtml(item.preview) + '</p>' +
                                '<p class="notification-time">' + escapeHtml(item.created_at) + '</p>' +
                            '</div>' +
                        '</a>' +
                    '</div>' +
                '</li>'
            );
        }).join('');
    }

    function loadInbox() {
        return postJson(indexUrl).then(function (data) {
            updateBadge(data.unread_count || 0);
            renderList(data.messages || []);
        }).catch(function () {
            list.innerHTML = '<li class="notification-empty">Could not load messages</li>';
        });
    }

    function showToast(sender, preview, caseRef) {
        if (typeof window.showToast === 'function') {
            window.showToast('info', preview, {
                title: (sender || 'New message') + (caseRef ? ' · ' + caseRef : ''),
                duration: 5000,
            });
        }
    }

    if (toggle && dropdown) {
        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var notifDropdown = document.getElementById('notification-dropdown');
            var notifToggle = document.getElementById('notification-bell-toggle');
            if (notifDropdown && !notifDropdown.hidden) {
                notifDropdown.hidden = true;
                if (notifToggle) notifToggle.setAttribute('aria-expanded', 'false');
            }
            var open = !dropdown.hidden;
            dropdown.hidden = open;
            toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
            if (!open) loadInbox();
        });

        document.addEventListener('click', function (e) {
            if (!wrap.contains(e.target)) {
                dropdown.hidden = true;
                toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    if (markAllBtn) {
        markAllBtn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            postJson(readAllUrl, 'POST').then(function () {
                loadInbox();
            });
        });
    }

    var echo = typeof window.getAretiaEcho === 'function' ? window.getAretiaEcho(csrf) : null;
    if (echo && window.AretiaBroadcast) {
        echo.private('App.Models.User.' + window.AretiaBroadcast.userId)
            .listen('.message.sent', function (payload) {
                if (parseInt(payload.recipient_id, 10) !== userId) return;
                loadInbox();
                showToast(payload.sender_name, payload.body, payload.case_reference);
                document.dispatchEvent(new CustomEvent('aretia:case-message', { detail: payload }));
            })
            .listen('.messages.read', function () {
                loadInbox();
            });
    }

    document.addEventListener('aretia:case-message', function () {
        loadInbox();
    });

    loadInbox();
})();
