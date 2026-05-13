// ============================================
// AQUAIRRIG - JAVASCRIPT PRINCIPAL
// ============================================

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const wrapper = document.getElementById('mainWrapper');
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('mobile-open');
    } else {
        sidebar.classList.toggle('collapsed');
        wrapper.classList.toggle('expanded');
    }
}

// Auto-close alerts
document.querySelectorAll('.alert').forEach(alert => {
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transition = 'opacity .5s';
        setTimeout(() => alert.remove(), 500);
    }, 4000);
});

// Modal helpers
function openModal(id) {
    document.getElementById(id).classList.add('active');
}

function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.classList.remove('active');
    });
});

// Confirm delete
function confirmDelete(message, form) {
    if (confirm(message || 'Confirmer la suppression ?')) {
        form.submit();
    }
}

// Search table
function searchTable(inputId, tableId) {
    const input = document.getElementById(inputId);
    const table = document.getElementById(tableId);
    if (!input || !table) return;
    input.addEventListener('keyup', () => {
        const term = input.value.toLowerCase();
        table.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    });
}

// Animate KPI counters
function animateCounters() {
    document.querySelectorAll('[data-count]').forEach(el => {
        const target = parseFloat(el.dataset.count);
        const isFloat = el.dataset.count.includes('.');
        const duration = 1200;
        const step = target / (duration / 16);
        let current = 0;

        const timer = setInterval(() => {
            current = Math.min(current + step, target);
            el.textContent = isFloat
                ? current.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ')
                : Math.round(current).toLocaleString('fr-FR');
            if (current >= target) clearInterval(timer);
        }, 16);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    animateCounters();
    document.querySelectorAll('table').forEach((t, i) => {
        if (!t.id) t.id = 'table-' + i;
    });
});

// Print invoice
function printInvoice() {
    window.print();
}
