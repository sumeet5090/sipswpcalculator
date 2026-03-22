document.addEventListener('DOMContentLoaded', () => {
    const mainContent = document.getElementById('main-content');
    const tocList = document.getElementById('toc-list');

    if (!mainContent || !tocList) return;

    const headings = mainContent.querySelectorAll('h2, h3');
    if (headings.length === 0) {
        tocList.innerHTML = '<li class="text-slate-400 italic">No sections found.</li>';
        return;
    }

    let tocHTML = '';
    const tocItems = []; // For observer

    headings.forEach((heading, index) => {
        // Ensure each heading has an ID
        if (!heading.id) {
            heading.id = heading.textContent.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
        }
        if (!heading.id) {
            heading.id = `section-${index}`;
        }

        const level = parseInt(heading.tagName.substring(1));
        // Simple indentation logic based on H2 vs H3
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

    // Intersection Observer for active TOC highlighting
    const observerOptions = {
        root: null,
        rootMargin: '0px 0px -80% 0px',
        threshold: 0
    };

    const tocLinks = document.querySelectorAll('.toc-link');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                // Remove active class from all
                tocLinks.forEach(link => {
                    link.classList.remove('text-emerald-600', 'font-bold');
                    link.classList.add('text-slate-600');
                });

                // Add to current desktop link
                const activeLink = document.querySelector(`#toc-list .toc-link[data-target="${entry.target.id}"]`);
                if (activeLink) {
                    activeLink.classList.remove('text-slate-600');
                    activeLink.classList.add('text-emerald-600', 'font-bold');
                }
            }
        });
    }, observerOptions);

    tocItems.forEach(item => observer.observe(item));

    // Smooth scroll for links
    document.querySelectorAll('.toc-link').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = link.getAttribute('href').substring(1);
            const targetEl = document.getElementById(targetId);
            if (targetEl) {
                targetEl.scrollIntoView({ behavior: 'smooth' });
            }
        });
    });
});
