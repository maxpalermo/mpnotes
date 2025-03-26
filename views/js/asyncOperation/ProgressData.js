class ProgressData {
    total = 100;
    current = 0;
    message = "Pronto per iniziare...";
    progressBar = null;
    statusElement = null;
    progressPercent = null;

    constructor(total, progressBar = null, statusElement = null, progressPercent = null) {
        this.total = total;
        this.current = 0;
        this.progressBar = progressBar;
        this.statusElement = statusElement;
        this.progressPercent = progressPercent;
    }

    /**
     * Incrementa il contatore corrente
     * @param {number} increment - Valore da aggiungere al contatore (default: 1)
     * @param {string} message - Messaggio di stato opzionale
     * @returns {number} La percentuale di completamento
     */
    increment(increment = 1, message = null) {
        this.current += increment;
        const percent = this.getPercentage();

        if (!message) {
            message = "Operazione in corso...";
        }

        if (this.progressBar) {
            this.progressBar.value = percent;
            this.progressBar.max = 100;
        }

        if (message && this.statusElement) {
            this.statusElement.innerHTML = message;
        }

        if (this.progressPercent) {
            this.progressPercent.innerHTML = `${percent} %`;
        }

        return percent;
    }

    setValue(value, message) {
        if (value > this.total) {
            value = this.total;
        }
        this.current = value;
        this.increment(0, message);
    }

    /**
     * Aggiunge un valore al contatore corrente
     * @param {number} value - Valore da aggiungere al contatore
     */
    update(value) {
        this.increment(value);
    }

    /**
     * Resetta il contatore
     * @param {string} message - Messaggio di stato opzionale
     */
    start() {
        this.setValue(0, "Operazione in corso...");
    }

    /**
     * Interrompe l'operazione
     * @param {string} message - Messaggio di stato opzionale
     */
    stop(message = null) {
        this.setValue(0, message || "Operazione interrotta");
    }

    /**
     * Termina l'operazione
     */
    end() {
        this.setValue(this.total, "Operazione completata");
    }

    /**
     * Imposta il messaggio di stato
     * @param {string} message - Messaggio di stato
     */
    setMessage(message) {
        this.message = message;
        if (this.statusElement) {
            this.statusElement.innerHTML = message;
        }
    }

    /**
     * Imposta il valore corrente
     * @param {number} value - Valore corrente
     * @param {string} message - Messaggio di stato opzionale
     * @returns {number} La percentuale di completamento
     */
    setCurrent(value, message = null) {
        this.current = value;
        return this.increment(0, message);
    }

    /**
     * Imposta il valore corrente
     * @param {number} value - Valore corrente
     * @param {string} message - Messaggio di stato opzionale
     * @returns {number} La percentuale di completamento
     */
    setTotal(value, message = null) {
        this.total = value;
        this.setValue(0, message);
    }

    /**
     * Calcola la percentuale di completamento
     * @returns {number} Percentuale di completamento (0-100)
     */
    getPercentage() {
        if (this.total <= 0) return 0;
        return Math.min(100, (this.current / this.total) * 100).toFixed(2);
    }

    /**
     * Resetta il contatore
     * @param {string} message - Messaggio di stato opzionale
     */
    reset(message = "Pronto per iniziare...") {
        this.current = 0;
        this.increment(0, message);
    }
}
