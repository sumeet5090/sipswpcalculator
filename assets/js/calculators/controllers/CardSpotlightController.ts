export class CardSpotlightController {
    init(): void {
        if (typeof window === 'undefined') return;

        const cards = document.querySelectorAll<HTMLElement>('.spotlight-card');
        cards.forEach(card => {
            card.addEventListener('pointermove', (e: PointerEvent) => {
                const rect = card.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;

                card.style.setProperty('--mouse-x', `${x}px`);
                card.style.setProperty('--mouse-y', `${y}px`);
            });

            card.addEventListener('pointerleave', () => {
                card.style.setProperty('--mouse-x', '-999px');
                card.style.setProperty('--mouse-y', '-999px');
            });
        });
    }
}
