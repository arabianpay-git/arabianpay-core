<div id="debug-panel" class="mt-6 p-4 border rounded-lg bg-gray-50 shadow-sm">
    <div class="flex justify-between items-center mb-3">
        <h2 class="text-lg font-bold text-gray-800">Debug Console</h2>
        <div class="flex space-x-2">
            <button type="button" id="test-connection"
                class="px-3 py-1.5 bg-green-600 hover:bg-green-700 text-white rounded text-sm font-medium">
                Test Connection
            </button>
            <button type="button" id="test-presence"
                class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white rounded text-sm font-medium">
                Test Presence
            </button>
            <button type="button" id="clear-logs"
                class="px-3 py-1.5 border border-gray-300 hover:bg-gray-100 rounded text-sm">
                Clear Logs
            </button>
        </div>
    </div>

    <div class="border rounded bg-white">
        <div class="flex justify-between items-center p-2 border-b bg-gray-50">
            <div class="text-sm font-medium text-gray-700">Event Log</div>
            <div class="flex items-center space-x-2">
                <span class="text-xs text-gray-500" id="log-count">0 entries</span>
            </div>
        </div>
        <pre id="dbg-log"
            class="p-3 text-xs font-mono overflow-auto h-64 bg-gray-900 text-gray-100 whitespace-pre-wrap break-all"></pre>
    </div>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const DebugPanel = {
                init() {
                    this.elements = {
                        container: document.getElementById('debug-panel'),
                        logElement: document.getElementById('dbg-log'),
                        logCount: document.getElementById('log-count'),
                        testConnectionBtn: document.getElementById('test-connection'),
                        testPresenceBtn: document.getElementById('test-presence'),
                        clearLogsBtn: document.getElementById('clear-logs')
                    };

                    this.logs = [];
                    this.setupEventListeners();
                    this.setupButtons();
                    window.ChatApp.logger.log('DebugPanel initialized');
                },

                setupEventListeners() {
                    // Listen for log events
                    window.ChatApp.events.on('log', (logEntry) => this.addLog(logEntry));

                    // Use global logger if available
                    if (window.ChatApp.logger && window.ChatApp.logger.logs) {
                        // Sync existing logs
                        window.ChatApp.logger.logs.forEach(log => this.addLog(log));
                    }
                },

                setupButtons() {
                    // Test connection button
                    if (this.elements.testConnectionBtn) {
                        this.elements.testConnectionBtn.addEventListener('click', () => {
                            this.testConnection();
                        });
                    }

                    // Test presence button
                    if (this.elements.testPresenceBtn) {
                        this.elements.testPresenceBtn.addEventListener('click', () => {
                            this.testPresence();
                        });
                    }

                    // Clear logs button
                    if (this.elements.clearLogsBtn) {
                        this.elements.clearLogsBtn.addEventListener('click', () => {
                            this.clearLogs();
                        });
                    }
                },

                addLog(logEntry) {
                    this.logs.unshift(logEntry);
                    if (this.logs.length > 200) this.logs.pop();

                    this.updateUI();
                },

                updateUI() {
                    if (this.elements.logElement) {
                        this.elements.logElement.textContent = this.logs.map(log =>
                            `[${log.timestamp}] ${log.message}${log.data ? '\n  ' + JSON.stringify(log.data, null, 2) : ''}`
                        ).join('\n');
                    }

                    if (this.elements.logCount) {
                        this.elements.logCount.textContent = `${this.logs.length} entries`;
                    }
                },

                testConnection() {
                    window.ChatApp.logger.log('Testing connection...');
                    window.ChatApp.events.emit('debug:test-connection');
                },

                testPresence() {
                    window.ChatApp.logger.log('Testing presence...');
                    window.ChatApp.events.emit('debug:test-presence');
                },

                clearLogs() {
                    this.logs = [];
                    this.updateUI();
                    window.ChatApp.logger.log('Logs cleared');
                }
            };

            DebugPanel.init();
        });
    </script>
@endpush
