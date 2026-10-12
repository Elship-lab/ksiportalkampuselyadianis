/**
 * NEXUS CAMPUS PORTAL
 * Master JavaScript — UI interactions, AJAX, animations
 */

'use strict';

/* ── Sidebar Toggle ──────────────────────────────────────────── */
const sidebar   = document.getElementById('sidebar');
const hamburger = document.getElementById('hamburger');
const overlay   = document.getElementById('sidebarOverlay');

if (hamburger && sidebar) {
    hamburger.addEventListener('click', () => {
        sidebar.classList.toggle('mobile-open');
        overlay?.classList.toggle('active');
    });
    overlay?.addEventListener('click', () => {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
    });
}

/* ── Auto-fill Demo Accounts ─────────────────────────────────── */
document.querySelectorAll('.demo-item').forEach(item => {
    item.addEventListener('click', () => {
        const u = item.dataset.user;
        const p = item.dataset.pass;
        const userField = document.getElementById('username');
        const passField = document.getElementById('password');
        if (userField) userField.value = u;
        if (passField) passField.value = p;
        passField?.focus();
        // Ripple effect
        item.style.background = 'rgba(0,212,170,0.1)';
        setTimeout(() => item.style.background = '', 300);
    });
});

/* ── Modal System ────────────────────────────────────────────── */
const NexusModal = {
    open(id) {
        const overlay = document.getElementById(id);
        if (overlay) {
            overlay.classList.add('open');
            document.body.style.overflow = 'hidden';
        }
    },
    close(id) {
        const overlay = document.getElementById(id);
        if (overlay) {
            overlay.classList.remove('open');
            document.body.style.overflow = '';
        }
    },
    closeAll() {
        document.querySelectorAll('.modal-overlay').forEach(m => {
            m.classList.remove('open');
        });
        document.body.style.overflow = '';
    }
};

// Close on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) NexusModal.closeAll();
    });
});

// Close on ESC
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') NexusModal.closeAll();
});

/* ── Toast Notification ──────────────────────────────────────── */
const NexusToast = {
    container: null,
    init() {
        this.container = document.createElement('div');
        this.container.id = 'toast-container';
        Object.assign(this.container.style, {
            position: 'fixed',
            top: '80px', right: '20px',
            zIndex: '9999',
            display: 'flex',
            flexDirection: 'column',
            gap: '8px',
            pointerEvents: 'none'
        });
        document.body.appendChild(this.container);
    },
    show(msg, type = 'info', duration = 3500) {
        if (!this.container) this.init();
        const colors = {
            success: { bg: 'rgba(16,185,129,0.15)', border: 'rgba(16,185,129,0.3)', color: '#10b981', icon: '✓' },
            danger:  { bg: 'rgba(239,68,68,0.15)',  border: 'rgba(239,68,68,0.3)',  color: '#ef4444', icon: '✕' },
            warning: { bg: 'rgba(245,158,11,0.15)', border: 'rgba(245,158,11,0.3)', color: '#f59e0b', icon: '⚠' },
            info:    { bg: 'rgba(67,97,238,0.15)',  border: 'rgba(67,97,238,0.3)',  color: '#60a5fa', icon: 'ℹ' },
        };
        const c = colors[type] || colors.info;
        const toast = document.createElement('div');
        toast.style.cssText = `
            background: ${c.bg}; border: 1px solid ${c.border}; color: ${c.color};
            padding: 12px 16px; border-radius: 10px; font-size: 0.83rem;
            font-family: 'Sora', sans-serif; font-weight: 500;
            backdrop-filter: blur(12px); pointer-events: auto;
            display: flex; align-items: center; gap: 10px;
            transform: translateX(120%); transition: transform 0.3s ease;
            max-width: 320px; box-shadow: 0 8px 24px rgba(0,0,0,0.4);
            cursor: pointer;
        `;
        toast.innerHTML = `<span style="font-size:1rem">${c.icon}</span><span>${msg}</span>`;
        this.container.appendChild(toast);
        requestAnimationFrame(() => { toast.style.transform = 'translateX(0)'; });
        toast.addEventListener('click', () => this._remove(toast));
        setTimeout(() => this._remove(toast), duration);
    },
    _remove(toast) {
        toast.style.transform = 'translateX(120%)';
        setTimeout(() => toast.remove(), 300);
    }
};

/* ── Upload Zone Drag & Drop ─────────────────────────────────── */
const uploadZones = document.querySelectorAll('.upload-zone');
uploadZones.forEach(zone => {
    const input = zone.querySelector('input[type="file"]');

    zone.addEventListener('click', () => input?.click());
    zone.addEventListener('dragover', e => {
        e.preventDefault();
        zone.classList.add('dragover');
    });
    zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
    zone.addEventListener('drop', e => {
        e.preventDefault();
        zone.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (input && files.length) {
            const dt = new DataTransfer();
            dt.items.add(files[0]);
            input.files = dt.files;
            updateFileLabel(zone, files[0]);
        }
    });
    input?.addEventListener('change', () => {
        if (input.files[0]) updateFileLabel(zone, input.files[0]);
    });
});

function updateFileLabel(zone, file) {
    const label = zone.querySelector('.upload-label');
    const size  = (file.size / 1024 / 1024).toFixed(2);
    if (label) {
        label.innerHTML = `<i class="fas fa-file-check" style="color:var(--accent-cyan)"></i>
            <strong>${file.name}</strong><br>
            <small style="color:var(--text-muted)">${size} MB</small>`;
    }
}

/* ── Animated Number Counter ─────────────────────────────────── */
function animateCounter(el, target, duration = 1200) {
    let start = 0;
    const step = target / (duration / 16);
    const timer = setInterval(() => {
        start += step;
        if (start >= target) { el.textContent = target; clearInterval(timer); }
        else el.textContent = Math.floor(start);
    }, 16);
}

/* Run counters on page load */
document.querySelectorAll('[data-count]').forEach(el => {
    const target = parseFloat(el.dataset.count);
    animateCounter(el, target);
});

/* ── Progress Bar Animation ──────────────────────────────────── */
document.querySelectorAll('.progress-bar-fill[data-width]').forEach(bar => {
    setTimeout(() => {
        bar.style.width = bar.dataset.width + '%';
    }, 200);
});

/* ── Table Row Selector ──────────────────────────────────────── */
document.querySelectorAll('.table-selectable tbody tr').forEach(row => {
    row.style.cursor = 'pointer';
    row.addEventListener('click', () => {
        const href = row.dataset.href;
        if (href) window.location.href = href;
    });
});

/* ── AJAX Notification Mark Read ─────────────────────────────── */
function markNotifRead(id, el) {
    fetch(`modules/mahasiswa/notifikasi_action.php?action=read&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (data.success && el) {
                el.classList.remove('unread');
                const dot = el.querySelector('.notif-dot-indicator');
                if (dot) dot.remove();
                const badge = document.querySelector('.nav-badge');
                if (badge) {
                    const count = parseInt(badge.textContent) - 1;
                    if (count <= 0) badge.style.display = 'none';
                    else badge.textContent = count;
                }
            }
        })
        .catch(() => {});
}

/* ── Grade Input Validator ───────────────────────────────────── */
document.querySelectorAll('input[data-type="grade"]').forEach(input => {
    input.addEventListener('input', () => {
        const val = parseFloat(input.value);
        if (input.value === '') return;
        if (val < 0) input.value = 0;
        if (val > 100) input.value = 100;

        // Update grade preview
        const preview = input.closest('tr')?.querySelector('.grade-preview');
        if (preview) {
            const tugasEl = input.closest('tr')?.querySelector('[name*="tugas"]');
            const utsEl   = input.closest('tr')?.querySelector('[name*="uts"]');
            const uasEl   = input.closest('tr')?.querySelector('[name*="uas"]');
            const t = parseFloat(tugasEl?.value || 0);
            const u = parseFloat(utsEl?.value || 0);
            const a = parseFloat(uasEl?.value || 0);
            const final = (t * 0.3 + u * 0.3 + a * 0.4).toFixed(1);
            const grade = gradeFromScore(final);
            preview.textContent = grade;
            preview.className = `grade-pill grade-${grade}`;
        }
    });
});

function gradeFromScore(score) {
    if (score >= 85) return 'A';
    if (score >= 75) return 'B';
    if (score >= 65) return 'C';
    if (score >= 55) return 'D';
    return 'E';
}

/* ── Copy to Clipboard ───────────────────────────────────────── */
document.querySelectorAll('[data-copy]').forEach(btn => {
    btn.addEventListener('click', () => {
        navigator.clipboard.writeText(btn.dataset.copy).then(() => {
            NexusToast.show('Disalin ke clipboard!', 'success', 2000);
        });
    });
});

/* ── Confirm Delete ──────────────────────────────────────────── */
document.querySelectorAll('[data-confirm]').forEach(el => {
    el.addEventListener('click', e => {
        if (!confirm(el.dataset.confirm)) e.preventDefault();
    });
});

/* ── Auto-hide alerts ────────────────────────────────────────── */
document.querySelectorAll('.alert[data-autohide]').forEach(alert => {
    const delay = parseInt(alert.dataset.autohide) || 4000;
    setTimeout(() => {
        alert.style.transition = 'opacity 0.4s, transform 0.4s';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        setTimeout(() => alert.remove(), 400);
    }, delay);
});

/* ── Active Nav Highlight ────────────────────────────────────── */
const currentPath = window.location.pathname;
document.querySelectorAll('.nav-item').forEach(item => {
    const href = item.getAttribute('href');
    if (href && currentPath.endsWith(href.split('/').pop())) {
        item.classList.add('active');
    }
});

/* ── Search Filter (client-side) ─────────────────────────────── */
const searchInput = document.getElementById('tableSearch');
if (searchInput) {
    searchInput.addEventListener('input', () => {
        const q = searchInput.value.toLowerCase();
        document.querySelectorAll('.searchable-row').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(q) ? '' : 'none';
        });
    });
}

/* ── Keyboard shortcuts ──────────────────────────────────────── */
document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && e.key === '/') {
        e.preventDefault();
        document.getElementById('tableSearch')?.focus();
    }
});

/* ── Init ────────────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
    NexusToast.init();
    // Show PHP flashed toasts if any
    const toastData = document.getElementById('__toast__');
    if (toastData) {
        NexusToast.show(toastData.dataset.msg, toastData.dataset.type);
    }
});
