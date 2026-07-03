const path = require('path');

// Read input parameters JSON from command line arguments
const inputArgs = JSON.parse(process.argv[2]);

// Load the ES Module MathEngine class using dynamic import
import(path.join(__dirname, '../assets/js/calculators/MathEngine.js'))
    .then((module) => {
        const MathEngine = module.MathEngine;
        const results = MathEngine.calculateCorpus(inputArgs);
        console.log(JSON.stringify(results));
    })
    .catch((err) => {
        console.error("Node.js JS Calculator Runner Error:", err);
        process.exit(1);
    });
