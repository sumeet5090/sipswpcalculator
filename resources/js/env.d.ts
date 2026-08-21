/// <reference types="vite/client" />

declare module '*.css';

declare global {
  interface Window {
    dataLayer?: Array<Record<string, unknown> | unknown[]>;
    CalculatorApp?: unknown;
  }
}

export {};
