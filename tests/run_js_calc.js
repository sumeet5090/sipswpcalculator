import { MathEngine } from '../assets/js/calculators/MathEngine.ts';

try {
    // Read input parameters JSON from command line arguments
    const inputArgs = JSON.parse(process.argv[2]);
    const results = MathEngine.calculate(inputArgs);
    console.log(JSON.stringify(results));
} catch (err) {
    console.error("Node.js JS Calculator Runner Error:", err);
    process.exit(1);
}
