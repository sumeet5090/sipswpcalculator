import { MathEngine } from '../assets/js/calculators/MathEngine.ts';

try {
    // Read input parameters JSON from command line arguments
    const inputArgs = JSON.parse(process.argv[2]);

    if (inputArgs.action === 'required_sip') {
        const res = MathEngine.calculateRequiredSip(inputArgs.inputs, inputArgs.target_corpus);
        console.log(JSON.stringify({ result: res }));
    } else if (inputArgs.action === 'swp_required_corpus') {
        const res = MathEngine.calculateRequiredStartingCorpusForSwp(inputArgs.inputs);
        console.log(JSON.stringify({ result: res }));
    } else if (inputArgs.action === 'inflation_discount') {
        const res = MathEngine.calculateInflationDiscount(inputArgs.corpus, inputArgs.years, inputArgs.inflation);
        console.log(JSON.stringify({ result: res }));
    } else if (inputArgs.action === 'delay_cost') {
        const res = MathEngine.calculateDelayCost(inputArgs.inputs);
        console.log(JSON.stringify({ result: res }));
    } else {
        const results = MathEngine.calculate(inputArgs);
        console.log(JSON.stringify(results));
    }
} catch (err) {
    console.error("Node.js JS Calculator Runner Error:", err);
    process.exit(1);
}
