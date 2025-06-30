function activeMenuLink(slug: string): void {
    const menuRoot = document.querySelector(`#toplevel_page_${slug}`);
    if (!menuRoot) return;

    const currentUrl = window.location.href;
    const currentPath = currentUrl.substring(currentUrl.indexOf('admin.php'));

    // Click handler for menu links
    const handleClick = (event: MouseEvent) => {
        const target = event.target as HTMLElement;
        const anchor = target.closest('a');
        if (!anchor) return;

        const submenuItems = menuRoot.querySelectorAll<HTMLLIElement>('ul.wp-submenu li');
        submenuItems.forEach((item) => item.classList.remove('current'));

        if (anchor.classList.contains('wp-has-submenu')) {
            const firstItem = menuRoot.querySelector('li.wp-first-item');
            if (firstItem) firstItem.classList.add('current');
        } else {
            const parentLi = anchor.closest('li');
            if (parentLi) parentLi.classList.add('current');
        }
    };

    menuRoot.addEventListener('click', handleClick as EventListener);

    // Highlight current submenu item on page load
    const submenuLinks = menuRoot.querySelectorAll<HTMLAnchorElement>('ul.wp-submenu a');
    submenuLinks.forEach((link) => {
        let href = link.getAttribute('href');
        if (href?.endsWith('#')) {
            href = href.substring(0, href.length - 1);
        }
        if (href === currentPath) {
            const parentLi = link.closest('li');
            if (parentLi) parentLi.classList.add('current');
        }
    });
}

export default activeMenuLink;
