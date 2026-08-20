(function () {
    'use strict';

    var body = document.body;
    var sidebar = document.getElementById('sidebar');
    var sidebarBackdrop;

    function targetFromTrigger(trigger) {
        var selector = trigger.getAttribute('data-bs-target') || trigger.getAttribute('href');

        if (!selector || selector === '#' || selector.charAt(0) !== '#') {
            return null;
        }

        try {
            return document.querySelector(selector);
        } catch (error) {
            return null;
        }
    }

    function closeDropdowns(except) {
        document.querySelectorAll('.dropdown-menu.show').forEach(function (menu) {
            if (menu !== except) {
                menu.classList.remove('show');
                var toggle = menu.parentElement && menu.parentElement.querySelector('[data-bs-toggle="dropdown"]');
                if (toggle) toggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    function toggleDropdown(trigger) {
        var menu = trigger.parentElement && trigger.parentElement.querySelector('.dropdown-menu');
        if (!menu) return;

        var willOpen = !menu.classList.contains('show');
        closeDropdowns(menu);
        menu.classList.toggle('show', willOpen);
        trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    }

    function dispatchModalEvent(modal, name, relatedTarget) {
        var event = new Event(name, { bubbles: true, cancelable: true });
        event.relatedTarget = relatedTarget || null;
        return modal.dispatchEvent(event);
    }

    function showModal(modal, trigger) {
        if (!modal || modal.classList.contains('show')) return;
        if (!dispatchModalEvent(modal, 'show.bs.modal', trigger)) return;

        var backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.setAttribute('data-dashboard-modal-backdrop', '');
        document.body.appendChild(backdrop);

        modal.style.display = 'block';
        modal.removeAttribute('aria-hidden');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('role', 'dialog');
        modal.classList.add('show');
        body.classList.add('modal-open');
        dispatchModalEvent(modal, 'shown.bs.modal', trigger);

        var focusTarget = modal.querySelector('[autofocus], .btn-close, button, input, select, textarea, [tabindex]:not([tabindex="-1"])');
        if (focusTarget) focusTarget.focus();
    }

    function hideModal(modal) {
        if (!modal || !modal.classList.contains('show')) return;
        if (!dispatchModalEvent(modal, 'hide.bs.modal')) return;

        modal.classList.remove('show');
        modal.style.display = 'none';
        modal.setAttribute('aria-hidden', 'true');
        modal.removeAttribute('aria-modal');
        modal.removeAttribute('role');
        document.querySelectorAll('[data-dashboard-modal-backdrop]').forEach(function (backdrop) {
            backdrop.remove();
        });
        body.classList.remove('modal-open');
        dispatchModalEvent(modal, 'hidden.bs.modal');
    }

    function activateTab(trigger) {
        var target = targetFromTrigger(trigger);
        var group = trigger.closest('[role="tablist"], .nav, .list-group');

        if (group) {
            group.querySelectorAll('[data-bs-toggle="tab"], [data-bs-toggle="pill"], [data-bs-toggle="list"]').forEach(function (item) {
                item.classList.remove('active');
                item.setAttribute('aria-selected', 'false');
            });
        }

        if (target && target.parentElement) {
            target.parentElement.querySelectorAll(':scope > .tab-pane').forEach(function (pane) {
                pane.classList.remove('active', 'show');
            });
            target.classList.add('active', 'show');
        }

        trigger.classList.add('active');
        trigger.setAttribute('aria-selected', 'true');
    }

    function toggleCollapse(trigger) {
        var target = targetFromTrigger(trigger);
        if (!target) return;

        var willOpen = !target.classList.contains('show');
        target.classList.toggle('show', willOpen);
        trigger.classList.toggle('collapsed', !willOpen);
        trigger.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
    }

    function ensureSidebarBackdrop() {
        if (sidebarBackdrop) return sidebarBackdrop;
        sidebarBackdrop = document.createElement('button');
        sidebarBackdrop.type = 'button';
        sidebarBackdrop.className = 'dashboard-sidebar-backdrop';
        sidebarBackdrop.setAttribute('aria-label', 'Tutup menu navigasi');
        sidebarBackdrop.addEventListener('click', closeSidebar);
        document.body.appendChild(sidebarBackdrop);
        return sidebarBackdrop;
    }

    function openSidebar() {
        if (!sidebar) return;
        sidebar.classList.add('active');
        body.classList.add('sidebar-visible');
        ensureSidebarBackdrop().classList.add('show');
    }

    function closeSidebar() {
        if (!sidebar) return;
        sidebar.classList.remove('active');
        body.classList.remove('sidebar-visible');
        if (sidebarBackdrop) sidebarBackdrop.classList.remove('show');
    }

    function initialiseSidebar() {
        if (!sidebar) return;

        sidebar.querySelectorAll('.sidebar-item.has-sub').forEach(function (item) {
            var link = item.querySelector(':scope > .sidebar-link');
            var submenu = item.querySelector(':scope > .submenu');
            if (!link || !submenu) return;

            var isOpen = item.classList.contains('active') || submenu.querySelector('.submenu-item.active');
            submenu.classList.toggle('submenu-open', Boolean(isOpen));
            submenu.classList.toggle('submenu-closed', !isOpen);
            link.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

            link.addEventListener('click', function (event) {
                event.preventDefault();
                var willOpen = submenu.classList.contains('submenu-closed');
                submenu.classList.toggle('submenu-open', willOpen);
                submenu.classList.toggle('submenu-closed', !willOpen);
                item.classList.toggle('active', willOpen);
                link.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
            });
        });

        document.querySelectorAll('.burger-btn').forEach(function (button) {
            button.addEventListener('click', openSidebar);
        });
        document.querySelectorAll('.sidebar-hide').forEach(function (button) {
            button.addEventListener('click', closeSidebar);
        });

        if (window.matchMedia('(max-width: 1199px)').matches) {
            sidebar.classList.remove('active');
        }
    }

    function initialiseTables() {
        if (!window.simpleDatatables || !window.simpleDatatables.DataTable) return;

        document.querySelectorAll('#table1').forEach(function (table) {
            if (table.getAttribute('data-dashboard-table-ready') === 'true') return;
            table.setAttribute('data-dashboard-table-ready', 'true');
            new window.simpleDatatables.DataTable(table, {
                perPage: 10,
                perPageSelect: [10, 25, 50, 100],
                searchable: table.getAttribute('data-dashboard-searchable') !== 'false',
                labels: {
                    placeholder: 'Cari data...',
                    perPage: '',
                    noRows: 'Belum ada data yang tersedia',
                    info: 'Menampilkan {start}–{end} dari {rows} data'
                }
            });
        });
    }

    document.addEventListener('click', function (event) {
        var dropdownTrigger = event.target.closest('[data-bs-toggle="dropdown"]');
        if (dropdownTrigger) {
            event.preventDefault();
            toggleDropdown(dropdownTrigger);
            return;
        }

        if (!event.target.closest('.dropdown')) closeDropdowns();

        var modalTrigger = event.target.closest('[data-bs-toggle="modal"]');
        if (modalTrigger) {
            event.preventDefault();
            showModal(targetFromTrigger(modalTrigger), modalTrigger);
            return;
        }

        var modalDismiss = event.target.closest('[data-bs-dismiss="modal"]');
        if (modalDismiss) {
            event.preventDefault();
            hideModal(modalDismiss.closest('.modal'));
            return;
        }

        var tabTrigger = event.target.closest('[data-bs-toggle="tab"], [data-bs-toggle="pill"], [data-bs-toggle="list"]');
        if (tabTrigger) {
            event.preventDefault();
            activateTab(tabTrigger);
            return;
        }

        var collapseTrigger = event.target.closest('[data-bs-toggle="collapse"]');
        if (collapseTrigger) {
            event.preventDefault();
            toggleCollapse(collapseTrigger);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        closeDropdowns();
        var openModal = document.querySelector('.modal.show');
        if (openModal) hideModal(openModal);
        else closeSidebar();
    });

    document.querySelectorAll('.modal').forEach(function (modal) {
        modal.addEventListener('click', function (event) {
            if (event.target === modal) hideModal(modal);
        });
    });

    initialiseSidebar();
    initialiseTables();
}());
