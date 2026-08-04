/**
 * EventBus.ts
 * A simple publish-subscribe message broker to decouple UI events, calculations, and visualizers.
 */
type EventCallback = (data: any) => void;

class EventBus {
    private listeners: Record<string, EventCallback[]> = {};

    /**
     * Subscribe to an event topic.
     */
    subscribe(topic: string, callback: EventCallback): () => void {
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
    publish(topic: string, data?: any): void {
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
