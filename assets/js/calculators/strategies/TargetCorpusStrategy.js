import { CalculatorStrategy } from './CalculatorStrategy.js';
import { MathEngine } from '../MathEngine.js';

export class TargetCorpusStrategy extends CalculatorStrategy {
    execute(inputs) {
        // In Target Corpus mode, we need to enforce the presence of target_corpus element
        const targetCorpusRaw = this.dom.getValue('target_corpus', true);
        const targetCorpus = this.validator.validate('target_corpus', targetCorpusRaw || 10000000);
        
        const requiredSip = MathEngine.calculateRequiredSip(inputs, targetCorpus);
        inputs.sip = requiredSip;
        
        // Update DOM elements with the required SIP
        this.dom.setValue('sip', requiredSip);
        this.dom.setValue('sip_range', requiredSip);
        
        return inputs;
    }
}
