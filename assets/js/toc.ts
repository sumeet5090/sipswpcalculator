import { WebHapticEngine } from './calculators/helpers/WebHapticEngine';

/**
 * toc.ts
 * Generates and synchronizes Table of Contents for both desktop sidebars and mobile bottom sheets.
 * Provides IntersectionObserver scroll-spy and tactile haptic feedback on navigation.
 */
export function initToc(): void {
    const mainContent = document.querySelector('.entry-content') || document.getElementById('main-content');
    const tocList = document.getElementById('toc-list');
    const mobileTocList = document.getElementById('mobile-toc-list');

    if (!mainContent || (!tocList && !mobileTocList)) return;

    const headings = mainContent.querySelectorAll<HTMLElement>('h2, h3');
    if (headings.length === 0) {
        if (tocList) {
            tocList.replaceChildren();
            const emptyItem = document.createElement('li');
            emptyItem.className = 'text-slate-400 italic text-sm';
            emptyItem.textContent = 'No sections found.';
            tocList.appendChild(emptyItem);
        }
        if (mobileTocList) {
            mobileTocList.replaceChildren();
            const emptyItem = document.createElement('li');
            emptyItem.className = 'text-slate-400 italic text-sm';
            emptyItem.textContent = 'No sections found.';
            mobileTocList.appendChild(emptyItem);
        }
        return;
    }

    const desktopFragment = document.createDocumentFragment();
    const mobileFragment = document.createDocumentFragment();
    const tocItems: HTMLElement[] = [];

    headings.forEach((heading, index) => {
        if (!heading.id) {
            heading.id = (heading.textContent || '').toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)+/g, '');
        }
        if (!heading.id) {
            heading.id = `section-${index}`;
        }

        const level = parseInt(heading.tagName.substring(1), 10);
        const headingText = heading.textContent || '';

        // Desktop item
        if (tocList) {
            const li = document.createElement('li');
            li.className = level === 3
                ? 'toc-item-wrapper ml-4 border-l border-slate-200 pl-3 text-slate-500'
                : 'toc-item-wrapper font-semibold text-slate-700';

            const a = document.createElement('a');
            a.href = `#${heading.id}`;
            a.className = 'toc-link block py-1 hover:text-emerald-600 transition-colors text-xs xl:text-sm';
            a.dataset.target = heading.id;
            a.textContent = headingText;

            li.appendChild(a);
            desktopFragment.appendChild(li);
        }

        // Mobile item
        if (mobileTocList) {
            const mLi = document.createElement('li');
            mLi.className = level === 3
                ? 'ml-3 border-l-2 border-slate-200 pl-2.5 text-slate-600'
                : 'font-semibold text-slate-800';

            const mA = document.createElement('a');
            mA.href = `#${heading.id}`;
            mA.className = 'mobile-toc-link block py-2.5 px-3 rounded-xl hover:bg-emerald-50 hover:text-emerald-700 text-sm transition-colors touch-manipulation text-slate-700';
            mA.dataset.target = heading.id;
            mA.textContent = headingText;

            mLi.appendChild(mA);
            mobileFragment.appendChild(mLi);
        }

        tocItems.push(heading);
    });

    if (tocList) {
        tocList.replaceChildren(desktopFragment);
    }
    if (mobileTocList) {
        mobileTocList.replaceChildren(mobileFragment);
    }

    // Mobile Sheet Controls
    const mobileSheet = document.getElementById('mobile-toc-sheet') as HTMLDialogElement | null;
    const openMobileBtn = document.getElementById('open-mobile-toc-btn');
    const closeMobileBtn = document.getElementById('close-mobile-toc-btn');

    const closeMobileSheet = () => {
        if (mobileSheet && mobileSheet.open) {
            mobileSheet.close();
            openMobileBtn?.setAttribute('aria-expanded', 'false');
        }
    };

    if (mobileSheet && openMobileBtn) {
        openMobileBtn.addEventListener('click', () => {
            WebHapticEngine.triggerTick(6);
            mobileSheet.showModal();
            openMobileBtn.setAttribute('aria-expanded', 'true');
        });

        closeMobileBtn?.addEventListener('click', () => {
            WebHapticEngine.triggerTick(6);
            closeMobileSheet();
        });

        mobileSheet.addEventListener('click', (e) => {
            if (e.target === mobileSheet) {
                closeMobileSheet();
            }
        });

        mobileSheet.addEventListener('close', () => {
            openMobileBtn.setAttribute('aria-expanded', 'false');
        });
    }

    // IntersectionObserver scroll spy
    const observerOptions: IntersectionObserverInit = {
        root: null,
        rootMargin: '0px 0px -75% 0px',
        threshold: 0
    };

    const desktopTocLinks = document.querySelectorAll<HTMLElement>('.toc-link');
    const mobileTocLinks = document.querySelectorAll<HTMLElement>('.mobile-toc-link');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const safeTargetId = typeof CSS !== 'undefined' && typeof CSS.escape === 'function'
                    ? CSS.escape(entry.target.id)
                    : entry.target.id.replace(/["\\]/g, '\\$&');

                // Update desktop links
                desktopTocLinks.forEach(link => {
                    link.classList.remove('text-emerald-600', 'font-bold');
                    link.classList.add('text-slate-600');
                });
                const activeDesktop = document.querySelector<HTMLElement>(`#toc-list .toc-link[data-target="${safeTargetId}"]`);
                if (activeDesktop) {
                    activeDesktop.classList.remove('text-slate-600');
                    activeDesktop.classList.add('text-emerald-600', 'font-bold');
                }

                // Update mobile links
                mobileTocLinks.forEach(link => {
                    link.classList.remove('text-emerald-700', 'bg-emerald-50', 'font-bold');
                    link.classList.add('text-slate-700');
                });
                const activeMobile = document.querySelector<HTMLElement>(`#mobile-toc-list .mobile-toc-link[data-target="${safeTargetId}"]`);
                if (activeMobile) {
                    activeMobile.classList.remove('text-slate-700');
                    activeMobile.classList.add('text-emerald-700', 'bg-emerald-50', 'font-bold');
                }
            }
        });
    }, observerOptions);

    tocItems.forEach(item => observer.observe(item));

    // Smooth scroll navigation
    desktopTocLinks.forEach(link => {
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

    mobileTocLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            WebHapticEngine.triggerTick(6);
            closeMobileSheet();
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
