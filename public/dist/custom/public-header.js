document.addEventListener('DOMContentLoaded', () => {
    const toggle = document.querySelector('.public-header .burger-btn');
    const navigation = document.querySelector('.public-header .main-navbar');

    if (!toggle || !navigation) {
        return;
    }

    const closeNavigation = () => {
        navigation.classList.remove('active');
        toggle.classList.remove('active');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Buka menu navigasi');
    };

    toggle.addEventListener('click', () => {
        const willOpen = !navigation.classList.contains('active');

        navigation.classList.toggle('active', willOpen);
        toggle.classList.toggle('active', willOpen);
        toggle.setAttribute('aria-expanded', String(willOpen));
        toggle.setAttribute('aria-label', willOpen ? 'Tutup menu navigasi' : 'Buka menu navigasi');
    });

    navigation.querySelectorAll('.menu-item.has-sub > .menu-link').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();

            if (window.innerWidth >= 1200) {
                return;
            }

            const submenu = trigger.nextElementSibling;
            const willOpen = !submenu.classList.contains('active');

            submenu.classList.toggle('active', willOpen);
            trigger.setAttribute('aria-expanded', String(willOpen));
        });
    });

    navigation.querySelectorAll('.submenu-item.has-sub > .submenu-link').forEach((trigger) => {
        trigger.addEventListener('click', (event) => {
            event.preventDefault();

            if (window.innerWidth >= 1200) {
                return;
            }

            trigger.nextElementSibling.classList.toggle('active');
        });
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth >= 1200) {
            closeNavigation();
            navigation.querySelectorAll('.submenu.active, .subsubmenu.active').forEach((submenu) => {
                submenu.classList.remove('active');
            });
        }
    });

    document.addEventListener('click', (event) => {
        if (window.innerWidth < 1200 && !event.target.closest('.public-header')) {
            closeNavigation();
        }
    });
});
