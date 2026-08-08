/* Global JS – jQuery + SweetAlert2 + DataTables helpers */
(function ($) {
  'use strict';

  // -------------------- Sidebar toggle --------------------
  $(document).on('click', '#sidebarToggle', function () {
    $('.sidebar').toggleClass('open');
  });

  // -------------------- AJAX defaults --------------------
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': window.CSRF_TOKEN || '' }
  });

  // -------------------- DataTables helper --------------------
  window.initTable = function (sel, opts) {
    return $(sel).DataTable(Object.assign({
      pageLength: 10,
      lengthMenu: [10, 25, 50, 100],
      order: [],
      language: { search: '', searchPlaceholder: 'Search…' }
    }, opts || {}));
  };

  // -------------------- SweetAlert helpers --------------------
  window.confirmDelete = function (message) {
    return Swal.fire({
      title: 'Are you sure?',
      text: message || 'This action cannot be undone.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#DC2626',
      cancelButtonColor: '#64748B',
      confirmButtonText: 'Yes, delete',
      cancelButtonText: 'Cancel'
    });
  };

  window.toast = function (type, message) {
    Swal.fire({
      toast: true, position: 'top-end', icon: type, title: message,
      showConfirmButton: false, timer: 2600, timerProgressBar: true
    });
  };

  // -------------------- Small utils --------------------
  function esc(s) {
    return $('<div>').text(s == null ? '' : String(s)).html();
  }
  function debounce(fn, wait) {
    let t; return function () { clearTimeout(t); const a = arguments, c = this;
      t = setTimeout(function(){ fn.apply(c,a); }, wait); };
  }
  function timeAgo(str) {
    if (!str) return '';
    const d = new Date(str.replace(' ', 'T'));
    if (isNaN(d)) return '';
    const s = Math.floor((Date.now() - d.getTime()) / 1000);
    if (s < 60) return 'just now';
    if (s < 3600) return Math.floor(s/60) + 'm ago';
    if (s < 86400) return Math.floor(s/3600) + 'h ago';
    if (s < 604800) return Math.floor(s/86400) + 'd ago';
    return d.toLocaleDateString();
  }
  function typeIcon(type) {
    const map = {
      low_stock:  { icon: 'fa-triangle-exclamation', color: '#B45309', bg: '#FEF3C7' },
      expired:    { icon: 'fa-circle-exclamation',   color: '#B91C1C', bg: '#FEE2E2' },
      near_expiry:{ icon: 'fa-hourglass-half',       color: '#B45309', bg: '#FEF3C7' },
      appt_approved: { icon: 'fa-circle-check',      color: '#166534', bg: '#DCFCE7' },
      appt_rejected: { icon: 'fa-circle-xmark',      color: '#B91C1C', bg: '#FEE2E2' },
      appt_completed:{ icon: 'fa-flag-checkered',    color: '#1D4ED8', bg: '#DBEAFE' },
      appt_new:   { icon: 'fa-calendar-plus',        color: '#1D4ED8', bg: '#DBEAFE' },
      system:     { icon: 'fa-bell',                 color: '#166534', bg: '#DCFCE7' },
    };
    return map[type] || map.system;
  }

  /* ===================================================================
     NOTIFICATIONS
     =================================================================== */
  const EMPTY_NOTIF_HTML =
      '<div class="text-center py-4">'
    +   '<div style="width:56px;height:56px;border-radius:16px;background:#F1F5F9;color:#94A3B8;display:inline-flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:10px"><i class="fa-solid fa-bell-slash"></i></div>'
    +   '<div class="text-muted small">You\'re all caught up</div>'
    + '</div>';

  let notifTimer = null;

  function fetchNotifications() {
    if (!window.NOTIF_URL) return;

    $.ajax({ url: window.NOTIF_URL, dataType: 'json', timeout: 8000 })
      .done(function (r) {
        // Badge
        if (r && r.unread > 0) {
          $('#notifDot').show();
          $('#notifCount').text(r.unread).show();
          $('#notifMarkAll').show();
        } else {
          $('#notifDot').hide();
          $('#notifCount').text('').hide();
          $('#notifMarkAll').hide();
        }

        // List
        let html = '';
        if (!r || !Array.isArray(r.items) || r.items.length === 0) {
          html = EMPTY_NOTIF_HTML;
        } else {
          r.items.forEach(function (n) {
            const unread = !n.is_read || n.is_read == 0 || n.is_read === '0';
            const link = n.link ? (window.BASE_URL + String(n.link).replace(/^\//,'')) : '#';
            const t = typeIcon(n.type);
            html += '<a class="notif-item ' + (unread ? 'unread' : '') + '" href="' + esc(link) + '" data-id="' + esc(n.id) + '">'
                  + '<div class="notif-icon" style="background:' + t.bg + ';color:' + t.color + '"><i class="fa-solid ' + t.icon + '"></i></div>'
                  + '<div class="notif-body">'
                  +   '<div class="notif-title">' + esc(n.title) + (unread ? ' <span class="unread-pill"></span>' : '') + '</div>'
                  +   '<div class="notif-msg">' + esc(n.message) + '</div>'
                  +   '<div class="notif-time">' + esc(timeAgo(n.created_at)) + '</div>'
                  + '</div>'
                  + '</a>';
          });
        }
        $('#notifList').html(html);
      })
      .fail(function () {
        $('#notifList').html(EMPTY_NOTIF_HTML);
        $('#notifDot').hide();
        $('#notifCount').text('').hide();
        $('#notifMarkAll').hide();
      });
  }

  // Kick off initial fetch + poll every 60s (independent of dropdown state)
  $(function () {
    if (!window.NOTIF_URL) return;
    fetchNotifications();
    notifTimer = setInterval(fetchNotifications, 60000);
  });

  // Refresh when dropdown opens (so user sees latest instantly)
  $(document).on('shown.bs.dropdown', function (e) {
    if ($(e.target).find('#notifList').length || $(e.target).is('#notifList').parents('.dropdown')) {
      fetchNotifications();
    }
  });

  // Click on a notification → mark as read (then follow link naturally)
  $(document).on('click', '.notif-item', function () {
    const id = $(this).data('id');
    if (!id || !$(this).hasClass('unread')) return;
    $.post(window.BASE_URL + 'index.php?url=api/notif_read',
      { id: id, _csrf: window.CSRF_TOKEN });
  });

  // Mark all read
  $(document).on('click', '#notifMarkAll', function (e) {
    e.preventDefault(); e.stopPropagation();
    $.post(window.BASE_URL + 'index.php?url=api/notif_read',
      { all: 1, _csrf: window.CSRF_TOKEN })
      .done(function () { fetchNotifications(); toast('success', 'All notifications marked as read'); });
  });

  /* ===================================================================
     GLOBAL TOPBAR SEARCH
     =================================================================== */
  const $sInput   = $('#globalSearch');
  const $sResults = $('#globalSearchResults');

  function renderGroup(label, icon, items) {
    if (!items || !items.length) return '';
    let h = '<div class="sr-group">'
          + '<div class="sr-group-title"><i class="fa-solid ' + icon + ' me-2"></i>' + esc(label) + '</div>';
    items.forEach(function (it) {
      const url = window.BASE_URL + String(it.link).replace(/^\//,'');
      h += '<a class="sr-item" href="' + esc(url) + '">'
         +   '<div class="sr-item-title">' + esc(it.title) + '</div>'
         +   '<div class="sr-item-sub">' + esc(it.sub) + '</div>'
         + '</a>';
    });
    h += '</div>';
    return h;
  }

  const runSearch = debounce(function (q) {
    if (!q || q.length < 2) { $sResults.hide().empty(); return; }
    $sResults.show().html('<div class="sr-loading text-muted small text-center py-3"><span class="spinner-border spinner-border-sm me-2"></span>Searching…</div>');

    $.ajax({ url: window.SEARCH_URL, data: { q: q }, dataType: 'json', timeout: 8000 })
      .done(function (r) {
        const total = (r.patients||[]).length + (r.medicines||[]).length + (r.appointments||[]).length;
        if (!total) {
          $sResults.html(
              '<div class="text-center py-4">'
            +   '<div style="width:52px;height:52px;border-radius:16px;background:#F1F5F9;color:#94A3B8;display:inline-flex;align-items:center;justify-content:center;font-size:22px;margin-bottom:8px"><i class="fa-solid fa-magnifying-glass"></i></div>'
            +   '<div class="text-muted small">No results for <strong>' + esc(q) + '</strong></div>'
            + '</div>'
          );
          return;
        }
        let html = '';
        html += renderGroup('Patients',     'fa-user-injured',   r.patients);
        html += renderGroup('Medicines',    'fa-pills',          r.medicines);
        html += renderGroup('Appointments', 'fa-calendar-check', r.appointments);
        $sResults.html(html);
      })
      .fail(function () {
        $sResults.html('<div class="text-danger small text-center py-3">Search failed. Please try again.</div>');
      });
  }, 250);

  $sInput.on('input', function () { runSearch($(this).val().trim()); });
  $sInput.on('focus', function () { if ($(this).val().trim().length >= 2) $sResults.show(); });

  $(document).on('click', function (e) {
    if (!$(e.target).closest('#globalSearchWrap').length) $sResults.hide();
  });
  $sInput.on('keydown', function (e) {
    if (e.key === 'Escape') { $sResults.hide(); $(this).val(''); }
  });

})(jQuery);
