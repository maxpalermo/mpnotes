/**
 * Classe per gestire operazioni asincrone con progresso
 * Utilizza un sistema di polling per monitorare lo stato delle operazioni
 */
class ProgressOperation {
    /**
     * @param {string} baseUrl - URL base per le richieste API
     * @param {string} progressBarId - ID dell'elemento HTML della barra di progresso
     * @param {string} statusElementId - ID dell'elemento HTML per i messaggi di stato
     */
    constructor(baseUrl, progressBarId, statusElementId) {
        this.baseUrl = baseUrl;
        this.progressBar = document.getElementById(progressBarId);
        this.statusElement = document.getElementById(statusElementId);
        this.pollingInterval = null;
        this.isRunning = false;
        this.operationId = null;
        this.finalResult = null;
        this.lastUpdate = 0;
        this.pollingDelay = 1000; // Intervallo di polling in ms (1 secondo)
        this.timeoutDelay = 30000; // Timeout in ms (30 secondi)
    }

    /**
     * Avvia un'operazione asincrona
     * @param {FormData} formData - Dati del form da inviare
     * @returns {Promise} Promise che si risolve quando l'operazione è completata
     */
    async start(formData) {
        if (this.isRunning) {
            throw new Error("Operazione già in corso");
        }

        this.isRunning = true;
        this.progressBar.value = 0;
        this.statusElement.textContent = "Avvio operazione...";
        this.lastUpdate = Date.now();
        this.finalResult = null;

        try {
            // Avvia l'operazione e ottieni l'ID per il polling
            const operationResult = await this.startOperation(formData);
            
            if (!operationResult.success || !operationResult.operationId) {
                throw new Error(operationResult.message || "Errore durante l'avvio dell'operazione");
            }
            
            this.operationId = operationResult.operationId;
            
            // Restituisci una promise che si risolverà quando l'operazione sarà completata
            return new Promise((resolve, reject) => {
                // Avvia il polling per monitorare lo stato dell'operazione
                this.pollingInterval = setInterval(async () => {
                    try {
                        // Verifica lo stato dell'operazione
                        const state = await this.checkProgress();
                        
                        // Aggiorna l'interfaccia utente
                        this.updateProgress(state);
                        
                        // Se l'operazione è completata, interrompi il polling
                        if (state.complete) {
                            this.stopPolling();
                            
                            if (state.success) {
                                resolve(this.finalResult);
                            } else {
                                reject(new Error(state.message || "Operazione fallita"));
                            }
                        }
                        
                        // Aggiorna il timestamp dell'ultimo aggiornamento
                        this.lastUpdate = Date.now();
                        
                    } catch (error) {
                        console.error("Errore durante il polling:", error);
                        
                        // Se l'errore persiste, interrompi il polling
                        if (Date.now() - this.lastUpdate > this.timeoutDelay) {
                            this.stopPolling();
                            this.statusElement.textContent = "Timeout dell'operazione";
                            reject(new Error("Timeout dell'operazione"));
                        }
                    }
                }, this.pollingDelay);
                
                // Imposta un timeout per verificare se l'operazione è bloccata
                this.timeoutId = setInterval(() => {
                    if (Date.now() - this.lastUpdate > this.timeoutDelay) {
                        this.stopPolling();
                        this.statusElement.textContent = "Timeout dell'operazione";
                        reject(new Error("Timeout dell'operazione"));
                    }
                }, 5000); // Controlla ogni 5 secondi
            });
            
        } catch (error) {
            this.isRunning = false;
            this.progressBar.value = 0;
            this.statusElement.textContent = `Errore: ${error.message}`;
            throw error;
        }
    }

    /**
     * Avvia l'operazione sul server
     * @param {FormData} formData - Dati del form da inviare
     * @returns {Promise<Object>} Promise con il risultato dell'operazione
     */
    async startOperation(formData) {
        // Crea l'URL con i parametri del form
        const params = new URLSearchParams();
        for (const [key, value] of formData.entries()) {
            params.append(key, value);
        }
        
        // Aggiungi un timestamp per evitare la cache
        params.append('_', Date.now());
        
        console.log('Invio richiesta a:', this.baseUrl);
        console.log('Parametri:', params.toString());
        
        const response = await fetch(this.baseUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: params.toString() // Assicurati che sia una stringa
        });
        
        if (!response.ok) {
            throw new Error(`Errore HTTP: ${response.status}`);
        }
        
        const result = await response.json();
        console.log('Risposta ricevuta:', result);
        return result;
    }

    /**
     * Verifica lo stato dell'operazione
     * @returns {Promise<Object>} Promise con lo stato dell'operazione
     */
    async checkProgress() {
        if (!this.operationId) {
            throw new Error("Nessuna operazione in corso");
        }
        
        const params = new URLSearchParams({
            ajax: 1,
            action: 'checkProgress',
            operationId: this.operationId,
            _: Date.now() // Evita la cache
        });
        
        const url = `${this.baseUrl}?${params.toString()}`;
        console.log('Controllo progresso:', url);
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('Errore nella risposta:', errorText);
            throw new Error(`Errore HTTP: ${response.status}`);
        }
        
        try {
            const result = await response.json();
            console.log('Stato progresso:', result);
            return result;
        } catch (error) {
            console.error('Errore nel parsing JSON:', error);
            const text = await response.text();
            console.error('Risposta non valida:', text);
            throw new Error('Risposta non valida dal server');
        }
    }

    /**
     * Aggiorna l'interfaccia utente con lo stato dell'operazione
     * @param {Object} data - Dati sullo stato dell'operazione
     */
    updateProgress(data) {
        this.lastUpdate = Date.now();

        if (data.progress !== undefined) {
            this.progressBar.value = data.progress;
        }

        if (data.message) {
            this.statusElement.textContent = data.message;
        }

        if (data.complete) {
            this.finalResult = data;
        }
    }

    /**
     * Ottiene il risultato finale dell'operazione
     * @returns {Object} Risultato finale dell'operazione
     */
    getFinalResult() {
        if (!this.finalResult) {
            throw new Error("L'operazione non ha restituito un risultato finale");
        }
        return this.finalResult;
    }

    /**
     * Annulla l'operazione in corso
     * @returns {Promise<boolean>} Promise che indica se l'annullamento è riuscito
     */
    async cancel() {
        if (!this.isRunning || !this.operationId) {
            console.warn('Nessuna operazione in corso da annullare');
            return false;
        }
        
        try {
            // Invia la richiesta di annullamento al server
            const params = new URLSearchParams({
                ajax: 1,
                action: 'cancelOperation',
                operationId: this.operationId,
                _: Date.now() // Evita la cache
            });
            
            const url = `${this.baseUrl}?${params.toString()}`;
            console.log('Annullamento operazione:', url);
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Errore nella risposta di annullamento:', errorText);
                throw new Error(`Errore HTTP: ${response.status}`);
            }
            
            const result = await response.json();
            console.log('Risultato annullamento:', result);
            
            // Interrompi il polling e aggiorna l'interfaccia utente
            this.stopPolling();
            this.statusElement.textContent = "Operazione annullata";
            this.progressBar.value = 0;
            this.isRunning = false;
            
            return result.success;
            
        } catch (error) {
            console.error("Errore durante l'annullamento:", error);
            this.stopPolling();
            this.isRunning = false;
            return false;
        }
    }
    
    /**
     * Interrompe il polling e pulisce le risorse
     */
    stopPolling() {
        console.log('Arresto polling...');
        // Cancella gli intervalli se esistono
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
        
        if (this.timeoutId) {
            clearInterval(this.timeoutId);
            this.timeoutId = null;
        }
        
        this.isRunning = false;
        console.log('Polling arrestato');
    }
}
