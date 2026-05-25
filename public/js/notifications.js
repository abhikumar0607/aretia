(function () {
    var wrap = document.getElementById('notification-bell');
    if (!wrap) return;

    var toggle = document.getElementById('notification-bell-toggle');
    var dropdown = document.getElementById('notification-dropdown');
    var list = document.getElementById('notification-list');
    var badge = document.getElementById('notification-badge');
    var markAllBtn = document.getElementById('notification-mark-all');
    var indexUrl = wrap.dataset.indexUrl;
    var readAllUrl = wrap.dataset.readAllUrl;
    var readUrlTemplate = wrap.dataset.readUrlTemplate || '';
    var csrf = wrap.dataset.csrf;

    var markReadIcon = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">' +
        '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';

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
            if (!r.ok) {
                return r.json().catch(function () { return {}; }).then(function (data) {
                    throw new Error(data.message || 'Request failed');
                });
            }
            return r.json();
        });
    }

    function markReadUrl(id) {
        if (readUrlTemplate) {
            return readUrlTemplate.replace('__ID__', encodeURIComponent(id));
        }
        return '/notifications/' + encodeURIComponent(id) + '/read';
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

    function setItemRead(id) {
        if (!list) return;
        var item = list.querySelector('.notification-item[data-id="' + CSS.escape(id) + '"]');
        if (!item) return;
        item.classList.remove('notification-item-unread');
        var dot = item.querySelector('.notification-unread-dot');
        if (dot) dot.remove();
        var btn = item.querySelector('[data-mark-read]');
        if (btn) btn.remove();
    }

    function renderList(items) {
        if (!items.length) {
            list.innerHTML = '<li class="notification-empty">No notifications yet</li>';
            return;
        }

        list.innerHTML = items.map(function (n) {
            var unread = !n.read_at;
            var unreadClass = unread ? ' notification-item-unread' : '';
            var href = n.url ? ' href="' + escapeHtml(n.url) + '"' : '';
            var dot = unread ? '<span class="notification-unread-dot" aria-hidden="true"></span>' : '';
            var markBtn = unread
                ? '<button type="button" class="notification-mark-one" data-mark-read title="Mark as read" aria-label="Mark as read">' + markReadIcon + '</button>'
                : '';

            return (
                '<li class="notification-item' + unreadClass + '" data-id="' + escapeHtml(n.id) + '">' +
                    '<div class="notification-item-inner">' +
                        '<a class="notification-item-main"' + href + '>' +
                            dot +
                            '<div class="notification-content">' +
                                '<p class="notification-title">' + escapeHtml(n.title) + '</p>' +
                                '<p class="notification-message">' + escapeHtml(n.message) + '</p>' +
                                '<p class="notification-time">' + escapeHtml(n.created_at) + '</p>' +
                            '</div>' +
                        '</a>' +
                        markBtn +
                    '</div>' +
                '</li>'
            );
        }).join('');
    }

    function loadNotifications() {
        return postJson(indexUrl).then(function (data) {
            updateBadge(data.unread_count);
            renderList(data.notifications || []);
        }).catch(function () {
            list.innerHTML = '<li class="notification-empty">Could not load notifications</li>';
        });
    }

    function markRead(id) {
        return postJson(markReadUrl(id), 'POST').then(function (data) {
            updateBadge(data.unread_count);
            setItemRead(id);
            return data;
        }).catch(function () {
            if (typeof window.showToast === 'function') {
                window.showToast('error', 'Could not mark notification as read.', { title: 'Error' });
            }
        });
    }

    function showToast(payload) {
        if (typeof window.showToast === 'function') {
            window.showToast('success', payload.message || 'New notification', { title: payload.title || 'Notification' });
        }
    }

    if (toggle && dropdown) {
        toggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var chatDropdown = document.getElementById('chat-inbox-dropdown');
            var chatToggle = document.getElementById('chat-inbox-toggle');
            if (chatDropdown && !chatDropdown.hidden) {
                chatDropdown.hidden = true;
                if (chatToggle) chatToggle.setAttribute('aria-expanded', 'false');
            }
            var userDropdown = document.getElementById('portal-user-dropdown');
            var userToggle = document.getElementById('portal-settings-toggle');
            if (userDropdown && !userDropdown.hidden) {
                userDropdown.hidden = true;
                if (userToggle) userToggle.setAttribute('aria-expanded', 'false');
            }
            var open = !dropdown.hidden;
            dropdown.hidden = open;
            toggle.setAttribute('aria-expanded', open ? 'false' : 'true');
            if (!open) loadNotifications();
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
                loadNotifications();
            });
        });
    }

    if (list) {
        list.addEventListener('click', function (e) {
            var markBtn = e.target.closest('[data-mark-read]');
            var item = e.target.closest('.notification-item');
            if (!item || !item.dataset.id) return;

            var id = item.dataset.id;
            var isUnread = item.classList.contains('notification-item-unread');
            var link = item.querySelector('.notification-item-main');
            var targetUrl = link && link.getAttribute('href');

            if (markBtn) {
                e.preventDefault();
                e.stopPropagation();
                if (isUnread) markRead(id);
                return;
            }

            if (!isUnread) return;

            e.preventDefault();
            markRead(id).then(function () {
                if (targetUrl) {
                    window.location.href = targetUrl;
                }
            });
        });
    }

    var echo = typeof window.getAretiaEcho === 'function' ? window.getAretiaEcho(csrf) : null;
    if (echo) {
        echo.private('App.Models.User.' + window.AretiaBroadcast.userId)
            .notification(function (payload) {
                if ((payload.type || '') === 'case_message') {
                    return;
                }
                loadNotifications();
                showToast(payload);
            });
    }

    loadNotifications();
})();
