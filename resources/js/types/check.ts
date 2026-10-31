export type RiskLevel = 'unauffaellig' | 'verdaechtig' | 'gefaehrlich';

export type SignalWeight = 'weak' | 'strong';

export type SignalSource = 'rules' | 'ai';

export type Signal = {
    title: string;
    explanation: string;
    weight: SignalWeight;
    source: SignalSource;
};

export type LearningCard = {
    riskLevel: RiskLevel;
    summary: string;
    signals: Signal[];
    nextSteps: string[];
    heuristicsOnly: boolean;
};
