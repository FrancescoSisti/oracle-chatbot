class NLPProcessor {
    constructor() {
        this.model = new TransformerModel();
    }

    async analyzeIntent(text) {
        // Implementazione analisi dell'intento
        return await this.model.classify(text);
    }

    async extractEntities(text) {
        // Estrazione entità dal testo
        return await this.model.extractEntities(text);
    }
}
