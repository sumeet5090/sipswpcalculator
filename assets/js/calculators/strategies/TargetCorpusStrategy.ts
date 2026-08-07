import { CalculatorStrategy } from './CalculatorStrategy';
import { MathEngine } from '../MathEngine';
import { InvestmentInputs } from '../../types';

export class TargetCorpusStrategy extends CalculatorStrategy {
    override execute(inputs: InvestmentInputs): InvestmentInputs {
        // In Target Corpus mode, we need to enforce the presence of target_corpus element
        const targetCorpusRaw = this.dom.getValue('target_corpus', true);
        const targetCorpus = this.validator.validate('target_corpus', targetCorpusRaw ? Number(targetCorpusRaw) : 10000000);
        
        const requiredSip = MathEngine.calculateRequiredSip(inputs, targetCorpus);
        
        // Update DOM elements with the required SIP
        this.dom.setValue('sip', requiredSip);
        this.dom.setValue('sip_range', requiredSip);
        
        return { ...inputs, sip: requiredSip };
    }
}
