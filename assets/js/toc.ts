export function initToc(): void {
    const mainContent = document.getElementById('main-content');
    const tocList = document.getElementById('toc-list');

    if (!mainContent || !tocList) return;

    const headings = mainContent.querySelectorAll<HTMLElement>('h2, h3');
    if (headings.length === 0) {
        tocList.replaceChildren();
        const emptyItem = document.createElement('li');
        emptyItem.className = 'text-slate-400 italic';
        emptyItem.textContent = 'No sections found.';
        tocList.appendChild(emptyItem);
        return;
    }

    const fragment = document.createDocumentFragment();
    const tocItems: HTMLElement[] = [];

    headings.forEach((heading, index) => {
        if (!heading.id) {
            heading.id = (heading.textContent || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
        }
        if (!heading.id) {
            heading.id = `section-${index}`;
        }

        const level = parseInt(heading.tagName.substring(1), 10);
        const li = document.createElement('li');
        li.className = level === 3
            ? 'toc-item-wrapper ml-4 border-l border-slate-200 pl-3 text-slate-500'
            : 'toc-item-wrapper font-semibold text-slate-700';

        const a = document.createElement('a');
        a.href = `#${heading.id}`;
        a.className = 'toc-link block py-1 hover:text-emerald-600 transition-colors';
        a.dataset.target = heading.id;
        a.textContent = heading.textContent;

        li.appendChild(a);
        fragment.appendChild(li);
        tocItems.push(heading);
    });

    tocList.replaceChildren(fragment);

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

                const safeTargetId = typeof CSS !== 'undefined' && typeof CSS.escape === 'function'
                    ? CSS.escape(entry.target.id)
                    : entry.target.id.replace(/["\\]/g, '\\$&');
                const activeLink = document.querySelector<HTMLElement>(`#toc-list .toc-link[data-target="${safeTargetId}"]`);
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
}

if (typeof document !== 'undefined') {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initToc);
    } else {
        initToc();
    }
}
