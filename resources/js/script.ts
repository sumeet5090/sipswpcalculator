/**
 * script.js
 * Entry point for modular client-side calculations.
 * Instantiates and bootstraps the Object-Oriented CalculatorApp.
 */
import { CalculatorApp } from '../../assets/js/calculators/CalculatorApp';
import { initSaveCalculationUI } from './save-calculation';

document.addEventListener('DOMContentLoaded', () => {
    const app = new CalculatorApp();
    app.init();
    initSaveCalculationUI();
});
