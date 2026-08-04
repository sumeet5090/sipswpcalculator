document.addEventListener('DOMContentLoaded', () => {
    const mainContent = document.getElementById('main-content');
    const tocList = document.getElementById('toc-list');

    if (!mainContent || !tocList) return;

    const headings = mainContent.querySelectorAll<HTMLElement>('h2, h3');
    if (headings.length === 0) {
        tocList.innerHTML = '<li class="text-slate-400 italic">No sections found.</li>';
        return;
    }

    let tocHTML = '';
    const tocItems: HTMLElement[] = [];

    headings.forEach((heading, index) => {
        if (!heading.id) {
            heading.id = (heading.textContent || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
        }
        if (!heading.id) {
            heading.id = `section-${index}`;
        }

        const level = parseInt(heading.tagName.substring(1));
        const indentClass = level === 3 ? 'ml-4 border-l border-slate-200 pl-3 text-slate-500' : 'font-semibold text-slate-700';

        tocHTML += `
            <li class="toc-item-wrapper ${indentClass}">
                <a href="#${heading.id}" class="toc-link block py-1 hover:text-emerald-600 transition-colors" data-target="${heading.id}">
                    ${heading.textContent}
                </a>
            </li>
        `;
        tocItems.push(heading);
    });

    tocList.innerHTML = tocHTML;

    const observerOptions: IntersectionObserverInit = {
        root: null,
        rootMargin: '0px 0px -80% 0px',
        threshold: 0
    };

    const tocLinks = document.querySelectorAll<HTMLElement>('.toc-link');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                tocLinks.forEach(link => {
                    link.classList.remove('text-emerald-600', 'font-bold');
                    link.classList.add('text-slate-600');
                });

                const activeLink = document.querySelector<HTMLElement>(`#toc-list .toc-link[data-target="${entry.target.id}"]`);
                if (activeLink) {
                    activeLink.classList.remove('text-slate-600');
                    activeLink.classList.add('text-emerald-600', 'font-bold');
                }
            }
        });
    }, observerOptions);

    tocItems.forEach(item => observer.observe(item));

    document.querySelectorAll<HTMLElement>('.toc-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const href = link.getAttribute('href');
            if (href) {
                const targetId = href.substring(1);
                const targetEl = document.getElementById(targetId);
                if (targetEl) {
                    targetEl.scrollIntoView({ behavior: 'smooth' });
                }
            }
        });
    });
});
