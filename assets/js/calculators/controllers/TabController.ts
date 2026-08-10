export class TabController {
    init(): void {
        const sipTab = document.getElementById('tab-sip');
        const swpTab = document.getElementById('tab-swp');

        const switchTab = (tab: string) => {
            const sipPanel = document.getElementById('panel-sip');
            const swpPanel = document.getElementById('panel-swp');

            if (!sipPanel || !swpPanel || !sipTab || !swpTab) return;

            const sipSpan = sipTab.querySelector('span');
            const swpSpan = swpTab.querySelector('span');

            if (tab === 'sip') {
                sipPanel.classList.remove('hidden');
                swpPanel.classList.add('hidden');
                sipTab.classList.add('bg-emerald-500', 'text-white');
                sipTab.classList.remove('bg-white', 'text-slate-500');
                if (sipSpan) {
                    sipSpan.classList.add('bg-white/20');
                    sipSpan.classList.remove('bg-slate-100');
                }
                swpTab.classList.add('bg-white', 'text-slate-500');
                swpTab.classList.remove('bg-rose-500', 'text-white');
                if (swpSpan) {
                    swpSpan.classList.add('bg-slate-100');
                    swpSpan.classList.remove('bg-white/20');
                }
                sipTab.setAttribute('aria-selected', 'true');
                swpTab.setAttribute('aria-selected', 'false');
            } else {
                swpPanel.classList.remove('hidden');
                sipPanel.classList.add('hidden');
                swpTab.classList.add('bg-rose-500', 'text-white');
                swpTab.classList.remove('bg-white', 'text-slate-500');
                if (swpSpan) {
                    swpSpan.classList.add('bg-white/20');
                    swpSpan.classList.remove('bg-slate-100');
                }
                sipTab.classList.add('bg-white', 'text-slate-500');
                sipTab.classList.remove('bg-emerald-500', 'text-white');
                if (sipSpan) {
                    sipSpan.classList.add('bg-slate-100');
                    sipSpan.classList.remove('bg-white/20');
                }
                sipTab.setAttribute('aria-selected', 'false');
                swpTab.setAttribute('aria-selected', 'true');
            }
        };

        if (sipTab) {
            sipTab.addEventListener('click', () => switchTab('sip'));
        }
        if (swpTab) {
            swpTab.addEventListener('click', () => switchTab('swp'));
        }
    }
}
