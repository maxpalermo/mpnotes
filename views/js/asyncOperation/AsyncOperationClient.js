class AsyncOperationClient {
    constructor(operationId, options = {}) {
        this.operationId = operationId;
        this.endpoint = options.endpoint || "modules/mpnotes/controllers/front/ajax.php";
        this.progressInterval = options.progressInterval || 1000;
        this.progressCallback = options.onProgress || (() => {});
        this.completeCallback = options.onComplete || (() => {});
        this.errorCallback = options.onError || (() => {});
        this.progressTimer = null;
    }

    start() {
        return new Promise((resolve, reject) => {
            this._fetch("start")
                .then((data) => {
                    this.operationId = data.operation_id;
                    this._monitorProgress(resolve, reject);
                })
                .catch(reject);
        });
    }

    cancel() {
        if (this.progressTimer) {
            clearInterval(this.progressTimer);
        }
        return this._fetch("cancel");
    }

    _monitorProgress(resolve, reject) {
        this.progressTimer = setInterval(() => {
            this._fetch("progress")
                .then((data) => {
                    if (data.success === false) {
                        clearInterval(this.progressTimer);
                        this.errorCallback(data);
                        reject(new Error(data.message));
                    } else if (data.progress >= 100 || data.completed_at) {
                        clearInterval(this.progressTimer);
                        this.completeCallback(data);
                        resolve(data);
                    } else {
                        this.progressCallback(data);
                    }
                })
                .catch((error) => {
                    clearInterval(this.progressTimer);
                    this.errorCallback({ message: error.message });
                    reject(error);
                });
        }, this.progressInterval);
    }

    _fetch(action) {
        const formData = new FormData();
        formData.append("action", action);
        if (this.operationId) {
            formData.append("operation_id", this.operationId);
        }

        return fetch(this.endpoint, {
            method: "POST",
            body: formData
        }).then((response) => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        });
    }
}
