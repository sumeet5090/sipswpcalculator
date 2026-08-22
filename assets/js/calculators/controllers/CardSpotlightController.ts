export class CardSpotlightController {
    init(): void {
        if (typeof window === 'undefined') return;

        const cards = document.querySelectorAll<HTMLElement>('.spotlight-card');
        cards.forEach(card => {
            let ticking = false;
            let lastX = -999;
            let lastY = -999;

            card.addEventListener('pointermove', (e: PointerEvent) => {
                const rect = card.getBoundingClientRect();
                lastX = e.clientX - rect.left;
                lastY = e.clientY - rect.top;

                if (!ticking) {
                    ticking = true;
                    requestAnimationFrame(() => {
                        card.style.setProperty('--mouse-x', `${lastX}px`);
                        card.style.setProperty('--mouse-y', `${lastY}px`);
                        ticking = false;
                    });
                }
            });

            card.addEventListener('pointerleave', () => {
                ticking = false;
                card.style.setProperty('--mouse-x', '-999px');
                card.style.setProperty('--mouse-y', '-999px');
            });
        });
    }
}
