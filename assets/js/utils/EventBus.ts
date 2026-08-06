/**
 * EventBus.ts
 * A simple typed publish-subscribe message broker to decouple UI events, calculations, and visualizers.
 */
type EventCallback<T = any> = (data: T) => void;

class EventBus {
    private listeners: Record<string, EventCallback<any>[]> = {};

    /**
     * Subscribe to an event topic.
     */
    subscribe<T = any>(topic: string, callback: EventCallback<T>): () => void {
        if (!this.listeners[topic]) {
            this.listeners[topic] = [];
        }
        this.listeners[topic].push(callback);

        // Return unsubscribe function
        return () => {
            if (this.listeners[topic]) {
                this.listeners[topic] = this.listeners[topic].filter(cb => cb !== callback);
            }
        };
    }

    /**
     * Publish an event to all subscribers.
     */
    publish<T = any>(topic: string, data?: T): void {
        if (!this.listeners[topic]) return;
        this.listeners[topic].forEach(callback => {
            try {
                callback(data);
            } catch (err) {
                console.error(`Error in event listener for ${topic}:`, err);
            }
        });
    }
}

export const eventBus = new EventBus();
