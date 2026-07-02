/**
 * script.js
 * Entry point for modular client-side calculations.
 * Instantiates and bootstraps the Object-Oriented CalculatorApp.
 */
import { CalculatorApp } from './assets/js/calculators/CalculatorApp.js';

document.addEventListener('DOMContentLoaded', () => {
    const app = new CalculatorApp();
    app.init();
});
