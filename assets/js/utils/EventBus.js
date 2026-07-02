/**
 * EventBus.js
 * A simple publish-subscribe message broker to decouple UI events, calculations, and visualizers.
 */
class EventBus {
    constructor() {
        this.listeners = {};
    }

    /**
     * Subscribe to an event topic.
     * @param {string} topic 
     * @param {function} callback 
     */
    subscribe(topic, callback) {
        if (!this.listeners[topic]) {
            this.listeners[topic] = [];
        }
        this.listeners[topic].push(callback);
        
        // Return unsubscribe function
        return () => {
            this.listeners[topic] = this.listeners[topic].filter(cb => cb !== callback);
        };
    }

    /**
     * Publish an event to all subscribers.
     * @param {string} topic 
     * @param {any} data 
     */
    publish(topic, data) {
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
