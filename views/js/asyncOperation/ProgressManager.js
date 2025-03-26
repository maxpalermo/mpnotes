/**
 * ProgressManager - Gestisce operazioni asincrone con progresso
 * Versione indipendente dal backend, utilizza callback per le operazioni
 */
class ProgressManager {
    /**
     * @param {Object} options - Opzioni di configurazione
     * @param {HTMLElement|string} options.progressBar - Elemento o ID della progress bar
     * @param {HTMLElement|string} options.statusElement - Elemento o ID dell'elemento di stato
     * @param {HTMLElement|string} [options.progressPercent] - Elemento per la percentuale
     * @param {HTMLElement|string} [options.resultContainer] - Elemento per i risultati
     * @param {number} [options.timeout=30000] - Timeout in millisecondi
     * @param {number} [options.updateInterval=100] - Intervallo aggiornamento UI in ms
     */
    constructor(options) {
        // Inizializza elementi UI
        this.progressBar = this._getElement(options.progressBar);
        this.statusElement = this._getElement(options.statusElement);
        this.progressPercent = options.progressPercent ? this._getElement(options.progressPercent) : null;
        this.resultContainer = options.resultContainer ? this._getElement(options.resultContainer) : null;

        // Configurazione
        this.timeout = options.timeout || 30000;
        this.updateInterval = options.updateInterval || 100;

        // Stato interno
        this._operation = null;
        this._progress = 0;
        this._lastUpdate = 0;
        this._timeoutHandle = null;
        this._uiUpdateHandle = null;
        this._resolve = null;
        this._reject = null;
    }

    /**
     * Avvia un'operazione asincrona
     * @param {Function} operationFn - Funzione che esegue l'operazione
     * @param {Object} [context] - Contesto per la funzione
     * @return {Promise} Promise che si risolve al completamento
     */
    async start(operationFn, context = null) {
        if (this._operation) {
            throw new Error("Un'operazione è già in corso");
        }

        this._reset();
        this._updateUI(0, "Inizio operazione...");

        return new Promise((resolve, reject) => {
            this._resolve = resolve;
            this._reject = reject;

            // Avvia il monitoraggio del timeout
            this._startTimeoutMonitor();

            // Avvia aggiornamento periodico UI
            this._startUIUpdater();

            // Esegui l'operazione
            this._operation = Promise.resolve()
                .then(() => operationFn.call(context, this))
                .then((result) => {
                    this._complete(true, "Operazione completata", result);
                    return result;
                })
                .catch((error) => {
                    this._complete(false, error.message || "Errore nell'operazione");
                    throw error;
                });
        });
    }

    /**
     * Annulla l'operazione in corso
     */
    cancel() {
        if (this._operation) {
            this._complete(false, "Operazione annullata dall'utente");
        }
    }

    /**
     * Aggiorna manualmente lo stato del progresso
     * @param {number} progress - Percentuale di completamento (0-100)
     * @param {string} [message] - Messaggio di stato
     */
    updateProgress(progress, message) {
        if (progress < 0 || progress > 100) {
            throw new Error("Il progresso deve essere tra 0 e 100");
        }

        this._progress = progress;
        this._lastUpdate = Date.now();

        if (message) {
            this._updateStatus(message);
        }
    }

    // Metodi privati
    _getElement(elementOrId) {
        return typeof elementOrId === "string" ? document.getElementById(elementOrId) : elementOrId;
    }

    _reset() {
        this._progress = 0;
        this._lastUpdate = Date.now();
        this._operation = null;

        if (this._timeoutHandle) {
            clearTimeout(this._timeoutHandle);
            this._timeoutHandle = null;
        }

        if (this._uiUpdateHandle) {
            cancelAnimationFrame(this._uiUpdateHandle);
            this._uiUpdateHandle = null;
        }
    }

    _startTimeoutMonitor() {
        this._lastUpdate = Date.now();

        const checkTimeout = () => {
            if (!this._operation) return;

            const elapsed = Date.now() - this._lastUpdate;
            if (elapsed > this.timeout) {
                this._complete(false, `Timeout: nessun aggiornamento per ${this.timeout / 1000} secondi`);
            } else {
                this._timeoutHandle = setTimeout(checkTimeout, 1000);
            }
        };

        this._timeoutHandle = setTimeout(checkTimeout, 1000);
    }

    _startUIUpdater() {
        const update = () => {
            if (!this._operation) return;

            this._updateProgressBar(this._progress);
            this._uiUpdateHandle = requestAnimationFrame(update);
        };

        this._uiUpdateHandle = requestAnimationFrame(update);
    }

    _updateUI(progress, message) {
        this._progress = progress;
        this._lastUpdate = Date.now();
        this._updateProgressBar(progress);
        this._updateStatus(message);
    }

    _updateProgressBar(progress) {
        this.progressBar.value = progress;
        if (this.progressPercent) {
            this.progressPercent.textContent = `${Math.round(progress)}%`;
        }
    }

    _updateStatus(message) {
        this.statusElement.textContent = message;
    }

    _showResult(message, success) {
        if (this.resultContainer) {
            this.resultContainer.style.display = "block";
            this.resultContainer.innerHTML = message;
            this.resultContainer.style.color = success ? "green" : "red";
        }
    }

    _complete(success, message, result = null) {
        // Aggiorna lo stato finale
        this._updateUI(success ? 100 : 0, message);
        this._showResult(message, success);

        // Pulisci le risorse
        if (this._timeoutHandle) {
            clearTimeout(this._timeoutHandle);
        }
        if (this._uiUpdateHandle) {
            cancelAnimationFrame(this._uiUpdateHandle);
        }

        // Risolvi o rigetta la Promise
        if (success) {
            this._resolve && this._resolve(result);
        } else {
            this._reject && this._reject(new Error(message));
        }

        this._operation = null;
        this._resolve = null;
        this._reject = null;
    }
}
