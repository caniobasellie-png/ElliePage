<?php
/**
 * Production Line Tracker
 * PHP Backend for saving and loading entries
 */

// Database configuration - EDIT THESE VALUES
$db_host = 'localhost';
$db_name = 'production_tracker';
$db_user = 'root';
$db_pass = '';

// Initialize response
$response = ['success' => false, 'message' => '', 'data' => null];

// Handle API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $action = $_POST['action'];
        
        if ($action === 'add_assembly') {
            $stmt = $pdo->prepare("INSERT INTO assembly_entries (line_number, assembly_type, product_number, order_number, serial_numbers, sw_version, date_started, in_charge, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $_POST['lineNumber'],
                $_POST['assemblyType'],
                $_POST['productNumber'],
                $_POST['orderNumber'],
                $_POST['serialNumbers'], // JSON string
                $_POST['swVersion'],
                $_POST['dateStarted'],
                $_POST['inCharge']
            ]);
            $response = ['success' => true, 'message' => 'Assembly entry added', 'id' => $pdo->lastInsertId()];
        }
        elseif ($action === 'add_repair') {
            $stmt = $pdo->prepare("INSERT INTO repair_entries (product_number, serial_number, order_number, defect, start_date, target_date, in_charge, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([
                $_POST['productNumber'],
                $_POST['serialNumber'],
                $_POST['orderNumber'],
                $_POST['defect'],
                $_POST['startDate'],
                $_POST['targetDate'],
                $_POST['inCharge']
            ]);
            $response = ['success' => true, 'message' => 'Repair entry added', 'id' => $pdo->lastInsertId()];
        }
        elseif ($action === 'get_entries') {
            $assemblies = $pdo->query("SELECT * FROM assembly_entries ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
            $repairs = $pdo->query("SELECT * FROM repair_entries ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
            $response = ['success' => true, 'assemblies' => $assemblies, 'repairs' => $repairs];
        }
        elseif ($action === 'delete_assembly') {
            $stmt = $pdo->prepare("DELETE FROM assembly_entries WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $response = ['success' => true, 'message' => 'Assembly entry deleted'];
        }
        elseif ($action === 'delete_repair') {
            $stmt = $pdo->prepare("DELETE FROM repair_entries WHERE id = ?");
            $stmt->execute([$_POST['id']]);
            $response = ['success' => true, 'message' => 'Repair entry deleted'];
        }
        elseif ($action === 'add_serial') {
            $stmt = $pdo->prepare("UPDATE assembly_entries SET serial_numbers = ? WHERE id = ?");
            $stmt->execute([$_POST['serialNumbers'], $_POST['id']]);
            $response = ['success' => true, 'message' => 'Serial number added'];
        }
    } catch (PDOException $e) {
        $response = ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
    }
    
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Production Line Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="app">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="header-left">
                    <div class="logo">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                    </div>
                    <div class="header-title">
                        <h1>Production Line Tracker</h1>
                        <p>Manufacturing Control System</p>
                    </div>
                </div>
                <div class="header-right">
                    <div class="total-label">Total Entries</div>
                    <div class="total-count" id="totalCount">0</div>
                </div>
            </div>
        </header>

        <div class="main-container">
            <!-- Form Panel -->
            <div class="form-panel">
                <h2 class="form-title">Add New Entry</h2>
                
                <!-- Line Number Selector -->
                <div class="form-group">
                    <label class="form-label">Line Number</label>
                    <select id="lineNumber" class="form-select">
                        <option value="">Select Line Number</option>
                        <option value="Line 1">Line 1</option>
                        <option value="Line 2">Line 2</option>
                        <option value="Line 3">Line 3</option>
                        <option value="Line 4">Line 4</option>
                        <option value="Line 5">Line 5</option>
                        <option value="Line 6">Line 6</option>
                        <option value="Line 7">Line 7</option>
                        <option value="Line 8">Line 8</option>
                        <option value="Line 9">Line 9</option>
                        <option value="Line 10">Line 10</option>
                        <option value="Line 11">Line 11</option>
                        <option value="Line 12">Line 12</option>
                        <option value="Repair Area">Repair Area</option>
                    </select>
                </div>

                <!-- Assembly Form (Lines 1-12) -->
                <form id="assemblyForm" class="hidden">
                    <div class="form-group">
                        <label class="form-label">Type of Assembly</label>
                        <select id="assemblyType" class="form-select" required>
                            <option value="">Select Assembly Type</option>
                            <option value="Sub-Assembly">Sub-Assembly</option>
                            <option value="Main Assembly">Main Assembly</option>
                            <option value="Sub-Parts">Sub-Parts</option>
                        </select>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Product Number</label>
                            <input type="text" id="asmProductNumber" class="form-input" placeholder="Enter product number" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Order Number</label>
                            <input type="text" id="asmOrderNumber" class="form-input" placeholder="Enter order number" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="serial-header">
                            <label class="form-label">Serial Numbers</label>
                            <button type="button" id="addSerialBtn" class="btn-add-serial">+ Add Serial</button>
                        </div>
                        <div id="serialContainer" class="serial-container">
                            <div class="serial-input-row">
                                <input type="text" class="form-input serial-input" placeholder="Serial Number 1">
                            </div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">SW Version Program</label>
                            <input type="text" id="swVersion" class="form-input" placeholder="Enter SW version" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Date Started</label>
                            <input type="date" id="asmDateStarted" class="form-input" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">In-Charge</label>
                        <input type="text" id="asmInCharge" class="form-input" placeholder="Enter name of person in charge" required>
                    </div>

                    <button type="submit" class="btn-submit">Add Entry</button>
                </form>

                <!-- Repair Form (Repair Area) -->
                <form id="repairForm" class="hidden">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Product Number</label>
                            <input type="text" id="repProductNumber" class="form-input" placeholder="Enter product number" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Serial Number</label>
                            <input type="text" id="repSerialNumber" class="form-input" placeholder="Enter serial number" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Order Number</label>
                        <input type="text" id="repOrderNumber" class="form-input" placeholder="Enter order number" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Defect Description</label>
                        <textarea id="defect" class="form-textarea" placeholder="Describe the defect" required></textarea>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Start Date</label>
                            <input type="date" id="startDate" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Target Date</label>
                            <input type="date" id="targetDate" class="form-input" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">In-Charge</label>
                        <input type="text" id="repInCharge" class="form-input" placeholder="Enter name of person in charge" required>
                    </div>

                    <button type="submit" class="btn-submit btn-orange">Add Repair Entry</button>
                </form>

                <!-- Empty State -->
                <div id="emptyFormState" class="empty-state">
                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="empty-icon">
                        <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                        <line x1="8" y1="21" x2="16" y2="21"></line>
                        <line x1="12" y1="17" x2="12" y2="21"></line>
                    </svg>
                    <p>Select a Line Number to begin</p>
                </div>
            </div>

            <!-- Cards Display Panel -->
            <div class="cards-panel">
                <!-- Assembly Section -->
                <section id="assemblySection" class="hidden">
                    <h3 class="section-title">
                        <span class="section-dot dot-blue"></span>
                        Assembly Entries
                        <span class="section-count" id="assemblyCount">(0)</span>
                    </h3>
                    <div id="assemblyCards" class="cards-grid"></div>
                </section>

                <!-- Repair Section -->
                <section id="repairSection" class="hidden">
                    <h3 class="section-title">
                        <span class="section-dot dot-orange"></span>
                        Repair Area
                        <span class="section-count" id="repairCount">(0)</span>
                    </h3>
                    <div id="repairCards" class="repair-cards"></div>
                </section>

                <!-- Empty State -->
                <div id="emptyCardsState" class="empty-cards-state">
                    <div class="empty-cards-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="3" width="20" height="14" rx="2" ry="2"></rect>
                            <line x1="8" y1="21" x2="16" y2="21"></line>
                            <line x1="12" y1="17" x2="12" y2="21"></line>
                        </svg>
                    </div>
                    <h3>No Entries Yet</h3>
                    <p>Select a line number and fill out the form to add your first production entry. Cards will flash when added.</p>
                </div>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>
