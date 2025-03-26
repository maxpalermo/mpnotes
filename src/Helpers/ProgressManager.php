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

/**
 * Classe per la gestione del progresso delle operazioni asincrone
 * Implementa un sistema di polling per monitorare lo stato delle operazioni
 */
class ProgressManager
{
    /** @var string ID dell'operazione corrente */
    private static $operationId = null;
    
    /** @var string Directory temporanea per i file di stato */
    private static $tempDir = null;
    
    /**
     * Inizializza un'operazione con progresso
     * 
     * @param string $operationId ID univoco dell'operazione (opzionale)
     * @return string ID dell'operazione
     */
    public static function start($operationId = null)
    {
        // Se non viene fornito un ID, ne genera uno casuale
        if ($operationId === null) {
            $operationId = uniqid('op_', true);
        }
        
        self::$operationId = $operationId;
        self::$tempDir = _PS_CACHE_DIR_ . 'mpnotes/progress/';
        
        // Crea la directory temporanea se non esiste
        if (!is_dir(self::$tempDir)) {
            mkdir(self::$tempDir, 0755, true);
        }
        
        // Inizializza lo stato dell'operazione
        $initialState = [
            'id' => $operationId,
            'progress' => 0,
            'message' => 'Inizializzazione operazione...',
            'complete' => false,
            'success' => false,
            'aborted' => false,
            'timestamp' => time(),
            'data' => [],
        ];
        
        // Salva lo stato iniziale
        self::saveState($initialState);
        
        // Imposta un limite di tempo più lungo per l'esecuzione
        set_time_limit(300); // 5 minuti
        
        // Ignora la disconnessione del client
        ignore_user_abort(true);
        
        // Registra shutdown function per gestire errori
        register_shutdown_function([self::class, 'handleShutdown']);
        
        // Restituisce l'ID dell'operazione per il polling
        return $operationId;
    }

    /**
     * Gestisce gli errori fatali durante l'esecuzione
     */
    public static function handleShutdown()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            self::updateProgress(
                0, 
                'Errore fatale: ' . $error['message'], 
                true, 
                false, 
                ['error' => $error]
            );
        }
    }

    /**
     * Aggiorna il progresso dell'operazione
     * 
     * @param int $progress Percentuale di completamento (0-100)
     * @param string $message Messaggio di stato
     * @param bool $complete Se l'operazione è completata
     * @param bool $success Se l'operazione è stata completata con successo
     * @param array $data Dati aggiuntivi
     * @return array Stato aggiornato dell'operazione
     */
    public static function updateProgress($progress, $message, $complete = false, $success = false, $data = [])
    {
        // Carica lo stato corrente
        $state = self::getState();
        
        // Aggiorna i campi
        $state['progress'] = $progress;
        $state['message'] = $message;
        $state['timestamp'] = time();
        
        if ($complete) {
            $state['complete'] = true;
            $state['success'] = $success;
        }
        
        // Aggiorna i dati aggiuntivi
        if (!empty($data)) {
            if (!isset($state['data'])) {
                $state['data'] = [];
            }
            $state['data'] = array_merge($state['data'], $data);
        }
        
        // Salva lo stato aggiornato
        self::saveState($state);
        
        return $state;
    }

    /**
     * Verifica se l'operazione è stata annullata
     * 
     * @return bool True se l'operazione è stata annullata
     */
    public static function checkAbort()
    {
        // Controlla se esiste un file di abort per questa operazione
        $abortFile = self::$tempDir . self::$operationId . '.abort';
        
        if (file_exists($abortFile)) {
            // Aggiorna lo stato dell'operazione
            self::updateProgress(
                0, 
                'Operazione annullata dall\'utente', 
                true, 
                false, 
                ['aborted' => true]
            );
            unlink($abortFile); // Rimuove il file di abort
            return true;
        }
        
        return false;
    }

    /**
     * Completa un'operazione con successo
     * 
     * @param array $processed Dati elaborati
     * @return array Stato finale dell'operazione
     */
    public static function stop($processed = [])
    {
        try {
            return self::updateProgress(
                100, 
                'Operazione completata con successo', 
                true, 
                true, 
                ['processed' => $processed, 'timestamp' => time()]
            );
        } catch (\Throwable $e) {
            return self::updateProgress(
                0, 
                'Errore: ' . $e->getMessage(), 
                true, 
                false, 
                [
                    'error' => [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                    ]
                ]
            );
        }
    }
    
    /**
     * Salva lo stato dell'operazione
     * 
     * @param array $state Stato dell'operazione
     * @return bool True se il salvataggio è riuscito
     */
    private static function saveState($state)
    {
        $stateFile = self::$tempDir . $state['id'] . '.json';
        return file_put_contents($stateFile, json_encode($state)) !== false;
    }
    
    /**
     * Carica lo stato dell'operazione corrente
     * 
     * @return array Stato dell'operazione
     */
    private static function getState()
    {
        $stateFile = self::$tempDir . self::$operationId . '.json';
        
        if (file_exists($stateFile)) {
            $content = file_get_contents($stateFile);
            $state = json_decode($content, true);
            return $state ?: [];
        }
        
        return [];
    }
    
    /**
     * Ottiene lo stato di un'operazione dato il suo ID
     * 
     * @param string $operationId ID dell'operazione
     * @return array|null Stato dell'operazione o null se non trovata
     */
    public static function getOperationState($operationId)
    {
        $tempDir = _PS_CACHE_DIR_ . 'mpnotes/progress/';
        $stateFile = $tempDir . $operationId . '.json';
        
        if (file_exists($stateFile)) {
            $content = file_get_contents($stateFile);
            $state = json_decode($content, true);
            return $state ?: null;
        }
        
        return null;
    }
    
    /**
     * Annulla un'operazione dato il suo ID
     * 
     * @param string $operationId ID dell'operazione
     * @return bool True se l'annullamento è riuscito
     */
    public static function abortOperation($operationId)
    {
        $tempDir = _PS_CACHE_DIR_ . 'mpnotes/progress/';
        $abortFile = $tempDir . $operationId . '.abort';
        
        // Crea un file di abort che verrà controllato dal processo
        return file_put_contents($abortFile, time()) !== false;
    }
}
