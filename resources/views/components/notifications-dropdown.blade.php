@php
    $initialUnread = auth()->user()->unreadNotifications()->count();
    $initialNotifications = auth()->user()->notifications()->latest()->limit(10)->get();
@endphp

<div class="relative">
    <button type="button" id="notifToggle" class="touch-target relative flex h-10 w-10 items-center justify-center rounded-lg text-slate-500 transition-all hover:bg-slate-50 hover:text-slate-900" aria-label="Buka notifikasi" aria-controls="notifPanel" aria-expanded="false">
        <span id="notifBadge" class="{{ $initialUnread > 0 ? '' : 'hidden' }} absolute right-0 top-0 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-bold text-white ring-2 ring-white transition-transform" aria-live="polite">{{ $initialUnread > 0 ? $initialUnread : '' }}</span>
        <svg width="22" height="22" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
    </button>

    <div id="notifPanel" class="fixed left-4 right-4 z-50 hidden max-h-[calc(100dvh-5rem)] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl sm:absolute sm:left-auto sm:right-0 sm:top-full sm:mt-1 sm:w-80" role="region" aria-labelledby="notifTitle" tabindex="-1">
        <div class="p-4 border-b border-slate-100 flex items-center justify-between">
            <h3 id="notifTitle" class="text-sm font-semibold text-slate-900">Notifikasi</h3>
            <button type="button" id="notifMarkAllRead" class="{{ $initialUnread > 0 ? '' : 'hidden' }} min-h-10 rounded-lg px-2 text-xs font-semibold text-corpblue-600 hover:bg-corpblue-50">Tandai semua dibaca</button>
        </div>

        <div id="notifList" class="notification-list overflow-y-auto divide-y divide-slate-50">
            @forelse($initialNotifications as $notification)
                @php
                    $data = $notification->data;
                    $type = $data['type'] ?? 'info';
                    $styles = [
                        'new_request' => ['dot' => 'bg-blue-500', 'badge' => 'bg-blue-50 text-blue-700'],
                        'approved' => ['dot' => 'bg-emerald-500', 'badge' => 'bg-emerald-50 text-emerald-700'],
                        'rejected' => ['dot' => 'bg-red-500', 'badge' => 'bg-red-50 text-red-700'],
                        'delayed' => ['dot' => 'bg-amber-500', 'badge' => 'bg-amber-50 text-amber-700'],
                        'warning' => ['dot' => 'bg-amber-500', 'badge' => 'bg-amber-50 text-amber-700'],
                        'completed' => ['dot' => 'bg-indigo-500', 'badge' => 'bg-indigo-50 text-indigo-700'],
                    ][$type] ?? ['dot' => 'bg-slate-400', 'badge' => 'bg-slate-100 text-slate-600'];
                @endphp
                <form method="POST" action="/notifications/{{ $notification->id }}/read" class="{{ $notification->read_at ? '' : 'bg-blue-50/30' }} hover:bg-slate-50 transition-all">
                    @csrf
                    <button type="submit" class="w-full text-left p-4 cursor-pointer">
                        <div class="flex items-start space-x-3">
                            <div class="w-2 h-2 mt-1.5 {{ $styles['dot'] }} rounded-full shrink-0 {{ $notification->read_at ? '' : 'animate-pulse' }}"></div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold {{ $styles['badge'] }} px-2 py-0.5 rounded w-max mb-1">{{ $data['title'] ?? 'Notifikasi' }}</p>
                                <p class="text-xs text-slate-600 leading-normal">{{ $data['message'] ?? '' }}</p>
                                <span class="text-[10px] text-slate-400 block mt-1">{{ $notification->created_at->diffForHumans(['locale' => 'id']) }}</span>
                            </div>
                        </div>
                    </button>
                </form>
            @empty
                <p class="text-xs text-slate-400 py-8 text-center">Tidak ada notifikasi.</p>
            @endforelse
        </div>
        <p id="notifStatus" class="sr-only" aria-live="polite"></p>
    </div>
</div>

<script>
(function () {
    const POLL_INTERVAL = 5000;
    const CSRF_TOKEN = '{{ csrf_token() }}';
    const TYPE_STYLES = {
        new_request: { dot: 'bg-blue-500', badge: 'bg-blue-50 text-blue-700' },
        approved:    { dot: 'bg-emerald-500', badge: 'bg-emerald-50 text-emerald-700' },
        rejected:    { dot: 'bg-red-500', badge: 'bg-red-50 text-red-700' },
        delayed:     { dot: 'bg-amber-500', badge: 'bg-amber-50 text-amber-700' },
        warning:     { dot: 'bg-amber-500', badge: 'bg-amber-50 text-amber-700' },
        completed:   { dot: 'bg-indigo-500', badge: 'bg-indigo-50 text-indigo-700' },
    };
    const DEFAULT_STYLE = { dot: 'bg-slate-400', badge: 'bg-slate-100 text-slate-600' };

    let prevUnreadCount = {{ $initialUnread }};
    let lastPoll = Date.now();

    const toggle = document.getElementById('notifToggle');
    const panel = document.getElementById('notifPanel');
    const badge = document.getElementById('notifBadge');
    const list = document.getElementById('notifList');
    const markAllBtn = document.getElementById('notifMarkAllRead');
    const status = document.getElementById('notifStatus');

    function timeAgo(isoString) {
        const diff = Math.floor((Date.now() - new Date(isoString).getTime()) / 1000);
        if (diff < 60) return 'baru saja';
        const mins = Math.floor(diff / 60);
        if (mins < 60) return mins + ' menit yang lalu';
        const hours = Math.floor(mins / 60);
        if (hours < 24) return hours + ' jam yang lalu';
        const days = Math.floor(hours / 24);
        return days + ' hari yang lalu';
    }

    function escapeHtml(text) {
        var el = document.createElement('span');
        el.textContent = text || '';
        return el.innerHTML;
    }

    function renderNotification(n) {
        const type = (n.data && n.data.type) || 'info';
        const s = TYPE_STYLES[type] || DEFAULT_STYLE;
        const unread = !n.read_at;
        return '<form method="POST" action="/notifications/' + n.id + '/read" class="' + (unread ? 'bg-blue-50/30' : '') + ' hover:bg-slate-50 transition-all">' +
            '<input type="hidden" name="_token" value="' + CSRF_TOKEN + '">' +
            '<button type="button" data-notif-id="' + n.id + '" data-url="' + escapeHtml(n.data.url) + '" class="notif-item w-full text-left p-4 cursor-pointer">' +
                '<div class="flex items-start space-x-3">' +
                    '<div class="w-2 h-2 mt-1.5 ' + s.dot + ' rounded-full shrink-0 ' + (unread ? 'animate-pulse' : '') + '"></div>' +
                    '<div class="flex-1 min-w-0">' +
                        '<p class="text-xs font-semibold ' + s.badge + ' px-2 py-0.5 rounded w-max mb-1">' + escapeHtml(n.data.title || 'Notifikasi') + '</p>' +
                        '<p class="text-xs text-slate-600 leading-normal">' + escapeHtml(n.data.message || '') + '</p>' +
                        '<span class="text-[10px] text-slate-400 block mt-1">' + timeAgo(n.created_at) + '</span>' +
                    '</div>' +
                '</div>' +
            '</button>' +
        '</form>';
    }

    function updateBadge(count) {
        if (count > 0) {
            badge.textContent = count;
            badge.classList.remove('hidden');
            markAllBtn.classList.remove('hidden');
            if (count !== prevUnreadCount) {
                badge.classList.add('scale-125');
                setTimeout(function () { badge.classList.remove('scale-125'); }, 300);
            }
        } else {
            if (document.activeElement === markAllBtn) panel.focus();
            badge.classList.add('hidden');
            markAllBtn.classList.add('hidden');
        }
        prevUnreadCount = count;
    }

    function poll() {
        return fetch('/notifications/poll', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) {
                if (!r.ok) throw new Error('Gagal memuat notifikasi.');
                return r.json();
            })
            .then(function (data) {
                updateBadge(data.unread_count);

                var focusedElement = document.activeElement;
                var preserveFocusedItem = focusedElement !== panel && panel.contains(focusedElement);
                if (!preserveFocusedItem) {
                    if (data.notifications.length === 0) {
                        list.innerHTML = '<p class="text-xs text-slate-500 py-8 text-center">Tidak ada notifikasi.</p>';
                    } else {
                        list.innerHTML = data.notifications.map(renderNotification).join('');
                        bindNotifClicks();
                    }
                }

                lastPoll = Date.now();
                status.textContent = '';
            })
            .catch(function () { status.textContent = 'Notifikasi belum dapat diperbarui.'; });
    }

    function markAsRead(notifId, formEl) {
        return fetch('/notifications/' + notifId + '/read', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_token=' + encodeURIComponent(CSRF_TOKEN)
        }).then(function (response) {
            if (!response.ok) throw new Error('Gagal menandai notifikasi.');
            return response.json();
        }).then(function (data) {
            if (formEl) {
                formEl.classList.remove('bg-blue-50/30');
                var dot = formEl.querySelector('.rounded-full');
                if (dot) dot.classList.remove('animate-pulse');
            }
            return data;
        }).catch(function () {
            status.textContent = 'Status notifikasi belum dapat diperbarui.';
            return null;
        });
    }

    function markAllAsRead() {
        if (document.activeElement === markAllBtn) panel.focus();
        markAllBtn.disabled = true;
        status.textContent = 'Memperbarui notifikasi.';
        fetch('/notifications/read-all', {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF_TOKEN, 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_token=' + encodeURIComponent(CSRF_TOKEN)
        }).then(function (response) {
            if (!response.ok) throw new Error('Gagal menandai semua notifikasi.');
            status.textContent = 'Semua notifikasi ditandai sudah dibaca.';
            return poll();
        }).catch(function () {
            status.textContent = 'Notifikasi belum dapat diperbarui.';
        }).finally(function () {
            markAllBtn.disabled = false;
        });
    }

    function bindNotifClicks() {
        list.querySelectorAll('.notif-item').forEach(function (item) {
            item.addEventListener('click', function (e) {
                e.preventDefault();
                var form = item.closest('form');
                var id = item.dataset.notifId;
                var url = item.dataset.url;
                markAsRead(id, form).then(function (data) {
                    var destination = data?.url || url;
                    if (destination) window.location.href = destination;
                });
            });
        });
    }

    function positionPanel() {
        if (window.innerWidth < 640) {
            panel.style.top = Math.round(toggle.getBoundingClientRect().bottom + 8) + 'px';
        } else {
            panel.style.top = '';
        }
    }

    function openPanel() {
        positionPanel();
        panel.classList.remove('hidden', 'anim-dropdown-out');
        panel.classList.add('anim-dropdown-in');
        toggle.setAttribute('aria-expanded', 'true');
        setTimeout(function () { panel.focus(); }, 30);
        if (Date.now() - lastPoll > 5000) poll();
    }

    function closePanel(restoreFocus) {
        if (panel.classList.contains('hidden')) return;
        panel.classList.remove('anim-dropdown-in');
        panel.classList.add('anim-dropdown-out');
        toggle.setAttribute('aria-expanded', 'false');
        setTimeout(function() {
            panel.classList.add('hidden');
            panel.classList.remove('anim-dropdown-out');
            if (restoreFocus) toggle.focus();
        }, 130);
    }

    toggle.addEventListener('click', function (e) {
        e.stopPropagation();
        if (panel.classList.contains('hidden')) {
            openPanel();
        } else {
            closePanel(false);
        }
    });

    document.addEventListener('click', function (e) {
        if (panel.classList.contains('hidden')) return;
        if (panel.contains(e.target) || toggle.contains(e.target)) return;
        closePanel(false);
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !panel.classList.contains('hidden')) closePanel(true);
    });

    window.addEventListener('resize', function () {
        if (!panel.classList.contains('hidden')) positionPanel();
    });

    markAllBtn.addEventListener('click', function () { markAllAsRead(); });

    setInterval(function () {
        if (!document.hidden) poll();
    }, POLL_INTERVAL);
    bindNotifClicks();
})();
</script>
