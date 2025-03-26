<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License version 3.0
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/AFL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    Massimiliano Palermo <maxx.palermo@gmail.com>
 * @copyright Since 2016 Massimiliano Palermo
 * @license   https://opensource.org/licenses/AFL-3.0 Academic Free License version 3.0
 */

namespace MpSoft\MpNotes\Helpers;

class AsyncOperations
{
    private $operationId;
    private $timeout;
    private $updateInterval;
    private $lastProgressFile;
    private $resultFile;
    private $shouldAbort = false;

    /**
     * Costruttore
     * 
     * @param string $operationId Identificativo unico dell'operazione
     * @param int $timeout Timeout in secondi (default: 300)
     * @param int $updateInterval Intervallo aggiornamento in ms (default: 200)
     */
    public function __construct(
        string $operationId,
        int $timeout = 300,
        int $updateInterval = 200
    ) {
        $this->operationId = $operationId;
        $this->timeout = $timeout;
        $this->updateInterval = $updateInterval;

        // Crea una cartella temporanea se non esiste
        $tmpDir = sys_get_temp_dir() . '/mpnotes_operations';
        if (!file_exists($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $this->lastProgressFile = $tmpDir . '/progress_' . $this->operationId . '.json';
        $this->resultFile = $tmpDir . '/result_' . $this->operationId . '.json';

        // Cancella eventuali file residui
        $this->cleanup();
    }

    /**
     * Esegue un'operazione lunga con aggiornamento del progresso
     * 
     * @param callable $operation Funzione che esegue l'operazione
     * @param array $params Parametri per la funzione
     *
     * @return array Risultato finale
     */
    public function execute(callable $operation, array $params = []): array
    {
        // Configura l'ambiente per l'output in streaming
        $this->setupStreaming();

        // Registra i gestori per la cancellazione
        $this->registerShutdownHandlers();

        try {
            // Esegui l'operazione
            $result = call_user_func_array($operation, array_merge([$this], $params));

            // Salva il risultato finale
            $this->saveResult([
                'success' => true,
                'progress' => 100,
                'message' => 'Operazione completata',
                'data' => $result,
                'completed_at' => date('Y-m-d H:i:s'),
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->saveResult([
                'success' => false,
                'progress' => 0,
                'message' => 'Errore: ' . $e->getMessage(),
                'error' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ],
                'failed_at' => date('Y-m-d H:i:s'),
            ]);

            throw $e;
        } finally {
            $this->cleanup();
        }
    }

    /**
     * Aggiorna lo stato del progresso
     * 
     * @param int $progress Percentuale di completamento (0-100)
     * @param string $message Messaggio di stato
     */
    public function updateProgress(int $progress, string $message): void
    {
        if ($this->shouldAbort) {
            throw new \Exception('Operazione annullata dall\'utente');
        }

        $data = [
            'progress' => $progress,
            'message' => $message,
            'timestamp' => time(),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        file_put_contents($this->lastProgressFile, json_encode($data));

        // Forza l'output per il flush
        if (ob_get_level() > 0) {
            ob_flush();
        }
        flush();

        // Simula un intervallo di aggiornamento
        usleep($this->updateInterval * 1000);
    }

    /**
     * Verifica lo stato dell'operazione
     * 
     * @return array Stato corrente
     */
    public function checkProgress(): array
    {
        if (file_exists($this->resultFile)) {
            $result = json_decode(file_get_contents($this->resultFile), true);
            $this->cleanup();

            return $result;
        }

        if (file_exists($this->lastProgressFile)) {
            return json_decode(file_get_contents($this->lastProgressFile), true);
        }

        return [
            'progress' => 0,
            'message' => 'Operazione non iniziata',
            'timestamp' => time(),
        ];
    }

    /**
     * Annulla l'operazione in corso
     */
    public function abort(): void
    {
        $this->shouldAbort = true;
        $this->saveResult([
            'success' => false,
            'progress' => 0,
            'message' => 'Operazione annullata dall\'utente',
            'aborted_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Configura l'ambiente per lo streaming
     */
    private function setupStreaming(): void
    {
        if (php_sapi_name() !== 'cli') {
            header('Content-Type: application/json');
            header('Cache-Control: no-cache');
            header('X-Accel-Buffering: no');
        }

        @ini_set('output_buffering', 'Off');
        @ini_set('zlib.output_compression', 0);
        @ini_set('implicit_flush', 1);

        ob_implicit_flush(true);
        set_time_limit($this->timeout);
    }

    /**
     * Registra i gestori per shutdown e abort
     */
    private function registerShutdownHandlers(): void
    {
        register_shutdown_function(function () {
            if (connection_aborted() && !$this->shouldAbort) {
                $this->saveResult([
                    'success' => false,
                    'progress' => 0,
                    'message' => 'Operazione interrotta (connessione chiusa)',
                    'aborted_at' => date('Y-m-d H:i:s'),
                ]);
            }
        });

        // Per ambienti non-CLI, gestisci l'abort via ignore_user_abort
        if (php_sapi_name() !== 'cli') {
            ignore_user_abort(false);
        }
    }

    /**
     * Salva il risultato finale
     */
    private function saveResult(array $data): void
    {
        file_put_contents($this->resultFile, json_encode($data));
    }

    /**
     * Pulisce i file temporanei
     */
    private function cleanup(): void
    {
        if (file_exists($this->lastProgressFile)) {
            @unlink($this->lastProgressFile);
        }

        if (file_exists($this->resultFile)) {
            @unlink($this->resultFile);
        }
    }

    /**
     * Genera un ID unico per l'operazione
     */
    public static function generateOperationId(): string
    {
        return bin2hex(random_bytes(16));
    }
}
