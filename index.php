<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini Reconciliation Tool</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <header class="header">
            <h1><i class="fas fa-calculator"></i> Mini Reconciliation Tool</h1>
            <p>Compare and reconcile financial transactions with ease</p>
        </header>

        <div class="upload-section">
            <div class="upload-card">
                <h2><i class="fas fa-upload"></i> Upload Transaction Files</h2>
                <form id="reconciliationForm" enctype="multipart/form-data">
                    <div class="file-input-group">
                        <div class="file-input-wrapper">
                            <label for="file1" class="file-label">
                                <i class="fas fa-file-csv"></i>
                                <span>Source File (CSV)</span>
                                <input type="file" id="file1" name="file1" accept=".csv" required>
                            </label>
                            <div class="file-info" id="file1-info"></div>
                        </div>
                        
                        <div class="file-input-wrapper">
                            <label for="file2" class="file-label">
                                <i class="fas fa-file-csv"></i>
                                <span>Target File (CSV)</span>
                                <input type="file" id="file2" name="file2" accept=".csv" required>
                            </label>
                            <div class="file-info" id="file2-info"></div>
                        </div>
                    </div>

                    <div class="settings-section">
                        <h3><i class="fas fa-cog"></i> Reconciliation Settings</h3>
                        <div class="settings-grid">
                            <div class="setting-item">
                                <label for="tolerance">Amount Tolerance:</label>
                                <input type="number" id="tolerance" name="tolerance" step="0.01" value="0.00" min="0">
                            </div>
                            <div class="setting-item">
                                <label for="date_range">Date Range (days):</label>
                                <input type="number" id="date_range" name="date_range" value="0" min="0">
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary" id="reconcileBtn">
                        <i class="fas fa-sync-alt"></i> Start Reconciliation
                    </button>
                </form>
            </div>
        </div>

        <div class="results-section" id="resultsSection" style="display: none;">
            <div class="results-header">
                <h2><i class="fas fa-chart-bar"></i> Reconciliation Results</h2>
                <div class="summary-cards" id="summaryCards"></div>
            </div>
            
            <div class="tabs">
                <button class="tab-button active" data-tab="matched">Matched Transactions</button>
                <button class="tab-button" data-tab="unmatched-source">Unmatched Source</button>
                <button class="tab-button" data-tab="unmatched-target">Unmatched Target</button>
                <button class="tab-button" data-tab="duplicates">Potential Duplicates</button>
            </div>

            <div class="tab-content">
                <div id="matched" class="tab-pane active">
                    <div class="table-container">
                        <table class="results-table" id="matchedTable">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                
                <div id="unmatched-source" class="tab-pane">
                    <div class="table-container">
                        <table class="results-table" id="unmatchedSourceTable">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                
                <div id="unmatched-target" class="tab-pane">
                    <div class="table-container">
                        <table class="results-table" id="unmatchedTargetTable">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
                
                <div id="duplicates" class="tab-pane">
                    <div class="table-container">
                        <table class="results-table" id="duplicatesTable">
                            <thead></thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="export-section">
                <button class="btn-secondary" id="exportBtn">
                    <i class="fas fa-download"></i> Export Results
                </button>
            </div>
        </div>

        <div class="loading" id="loading" style="display: none;">
            <div class="spinner"></div>
            <p>Processing reconciliation...</p>
        </div>
    </div>

    <script src="assets/js/script.js"></script>
</body>
</html>