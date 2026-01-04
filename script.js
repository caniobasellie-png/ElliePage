// ===== Data Storage =====
let assemblyEntries = [];
let repairEntries = [];

// ===== DOM Elements =====
const lineNumberSelect = document.getElementById('lineNumber');
const assemblyForm = document.getElementById('assemblyForm');
const repairForm = document.getElementById('repairForm');
const emptyFormState = document.getElementById('emptyFormState');
const assemblySection = document.getElementById('assemblySection');
const repairSection = document.getElementById('repairSection');
const emptyCardsState = document.getElementById('emptyCardsState');
const assemblyCards = document.getElementById('assemblyCards');
const repairCards = document.getElementById('repairCards');
const totalCount = document.getElementById('totalCount');
const assemblyCount = document.getElementById('assemblyCount');
const repairCount = document.getElementById('repairCount');
const serialContainer = document.getElementById('serialContainer');
const addSerialBtn = document.getElementById('addSerialBtn');

// ===== Line Number Selection =====
lineNumberSelect.addEventListener('change', function() {
    const value = this.value;
    
    // Hide all forms first
    assemblyForm.classList.add('hidden');
    repairForm.classList.add('hidden');
    emptyFormState.classList.add('hidden');
    
    if (value === '') {
        emptyFormState.classList.remove('hidden');
    } else if (value === 'Repair Area') {
        repairForm.classList.remove('hidden');
    } else {
        assemblyForm.classList.remove('hidden');
    }
});

// ===== Serial Number Management =====
let serialCount = 1;

addSerialBtn.addEventListener('click', function() {
    serialCount++;
    const row = document.createElement('div');
    row.className = 'serial-input-row';
    row.innerHTML = `
        <input type="text" class="form-input serial-input" placeholder="Serial Number ${serialCount}">
        <button type="button" class="btn-remove-serial" onclick="removeSerial(this)">✕</button>
    `;
    serialContainer.appendChild(row);
});

function removeSerial(btn) {
    const row = btn.parentElement;
    if (serialContainer.children.length > 1) {
        row.remove();
    }
}

// ===== Assembly Form Submit =====
assemblyForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const lineNumber = lineNumberSelect.value;
    const assemblyType = document.getElementById('assemblyType').value;
    const productNumber = document.getElementById('asmProductNumber').value;
    const orderNumber = document.getElementById('asmOrderNumber').value;
    const swVersion = document.getElementById('swVersion').value;
    const dateStarted = document.getElementById('asmDateStarted').value;
    const inCharge = document.getElementById('asmInCharge').value;
    
    // Get all serial numbers
    const serialInputs = serialContainer.querySelectorAll('.serial-input');
    const serialNumbers = [];
    serialInputs.forEach(input => {
        if (input.value.trim()) {
            serialNumbers.push(input.value.trim());
        }
    });
    
    if (!assemblyType) {
        alert('Please select Assembly Type');
        return;
    }
    
    const entry = {
        id: Date.now().toString(),
        lineNumber,
        assemblyType,
        productNumber,
        orderNumber,
        serialNumbers,
        swVersion,
        dateStarted,
        inCharge
    };
    
    assemblyEntries.unshift(entry);
    renderAssemblyCard(entry, true);
    updateCounts();
    resetAssemblyForm();
});

// ===== Repair Form Submit =====
repairForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const productNumber = document.getElementById('repProductNumber').value;
    const serialNumber = document.getElementById('repSerialNumber').value;
    const orderNumber = document.getElementById('repOrderNumber').value;
    const defect = document.getElementById('defect').value;
    const startDate = document.getElementById('startDate').value;
    const targetDate = document.getElementById('targetDate').value;
    const inCharge = document.getElementById('repInCharge').value;
    
    const entry = {
        id: Date.now().toString(),
        productNumber,
        serialNumber,
        orderNumber,
        defect,
        startDate,
        targetDate,
        inCharge
    };
    
    repairEntries.unshift(entry);
    renderRepairCard(entry, true);
    updateCounts();
    resetRepairForm();
});

// ===== Render Assembly Card =====
function renderAssemblyCard(entry, isNew = false) {
    const card = document.createElement('div');
    card.className = 'assembly-card' + (isNew ? ' flash' : '');
    card.id = 'card-' + entry.id;
    
    const serialsHtml = entry.serialNumbers.map(s => 
        `<div class="serial-item">${escapeHtml(s)}</div>`
    ).join('');
    
    card.innerHTML = `
        <div class="card-header">
            <div class="card-header-label">Line Number</div>
            <div class="card-header-value">${escapeHtml(entry.lineNumber)}</div>
        </div>
        <div class="card-type">
            <div class="card-type-value">${escapeHtml(entry.assemblyType)}</div>
        </div>
        <div class="card-row">
            <div class="card-row-label">Product Number</div>
            <div class="card-row-value">${escapeHtml(entry.productNumber)}</div>
        </div>
        <div class="card-row">
            <div class="card-row-label">Order Number</div>
            <div class="card-row-value">${escapeHtml(entry.orderNumber)}</div>
        </div>
        <div class="card-serials">
            <div class="card-serials-header">
                <span class="card-serials-label">Serial Numbers</span>
                <button class="btn-add-serial-card" onclick="toggleSerialAdd('${entry.id}')">+</button>
            </div>
            <div class="serial-add-form" id="serial-add-${entry.id}">
                <input type="text" class="serial-add-input" id="serial-input-${entry.id}" placeholder="New serial number">
                <button class="btn-add-confirm" onclick="addSerialToCard('${entry.id}')">Add</button>
            </div>
            <div class="card-serials-list" id="serials-${entry.id}">
                ${serialsHtml}
            </div>
        </div>
        <div class="card-row">
            <div class="card-row-label">SW Version</div>
            <div class="card-row-value">${escapeHtml(entry.swVersion)}</div>
        </div>
        <div class="card-row">
            <div class="card-row-label">Date Started</div>
            <div class="card-row-value">${formatDate(entry.dateStarted)}</div>
        </div>
        <div class="card-row">
            <div class="card-row-label">In-Charge</div>
            <div class="card-row-value">${escapeHtml(entry.inCharge)}</div>
        </div>
    `;
    
    assemblyCards.prepend(card);
    
    // Remove flash class after animation
    if (isNew) {
        setTimeout(() => {
            card.classList.remove('flash');
        }, 1500);
    }
}

// ===== Render Repair Card =====
function renderRepairCard(entry, isNew = false) {
    const card = document.createElement('div');
    card.className = 'repair-card' + (isNew ? ' flash' : '');
    card.id = 'repair-' + entry.id;
    
    card.innerHTML = `
        <div class="repair-header">
            <h3>REPAIR AREA</h3>
        </div>
        <div class="repair-table-wrapper">
            <table class="repair-table">
                <thead>
                    <tr>
                        <th>Product Number</th>
                        <th>Serial Number</th>
                        <th>Order Number</th>
                        <th>Defect</th>
                        <th>Start Date</th>
                        <th>Target Date</th>
                        <th>In-Charge</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>${escapeHtml(entry.productNumber)}</td>
                        <td>${escapeHtml(entry.serialNumber)}</td>
                        <td>${escapeHtml(entry.orderNumber)}</td>
                        <td class="defect-cell">${escapeHtml(entry.defect)}</td>
                        <td>${formatDate(entry.startDate)}</td>
                        <td>${formatDate(entry.targetDate)}</td>
                        <td>${escapeHtml(entry.inCharge)}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    `;
    
    repairCards.prepend(card);
    
    if (isNew) {
        setTimeout(() => {
            card.classList.remove('flash');
        }, 1500);
    }
}

// ===== Add Serial to Existing Card =====
function toggleSerialAdd(entryId) {
    const form = document.getElementById('serial-add-' + entryId);
    form.classList.toggle('show');
}

function addSerialToCard(entryId) {
    const input = document.getElementById('serial-input-' + entryId);
    const value = input.value.trim();
    
    if (value) {
        // Update data
        const entry = assemblyEntries.find(e => e.id === entryId);
        if (entry) {
            entry.serialNumbers.push(value);
            
            // Update UI
            const serialsList = document.getElementById('serials-' + entryId);
            const newSerial = document.createElement('div');
            newSerial.className = 'serial-item';
            newSerial.textContent = value;
            serialsList.appendChild(newSerial);
            
            // Clear input and hide form
            input.value = '';
            toggleSerialAdd(entryId);
        }
    }
}

// ===== Update Counts =====
function updateCounts() {
    const total = assemblyEntries.length + repairEntries.length;
    totalCount.textContent = total;
    assemblyCount.textContent = `(${assemblyEntries.length})`;
    repairCount.textContent = `(${repairEntries.length})`;
    
    // Toggle sections visibility
    if (assemblyEntries.length > 0) {
        assemblySection.classList.remove('hidden');
    } else {
        assemblySection.classList.add('hidden');
    }
    
    if (repairEntries.length > 0) {
        repairSection.classList.remove('hidden');
    } else {
        repairSection.classList.add('hidden');
    }
    
    // Toggle empty state
    if (total > 0) {
        emptyCardsState.classList.add('hidden');
    } else {
        emptyCardsState.classList.remove('hidden');
    }
}

// ===== Reset Forms =====
function resetAssemblyForm() {
    document.getElementById('assemblyType').value = '';
    document.getElementById('asmProductNumber').value = '';
    document.getElementById('asmOrderNumber').value = '';
    document.getElementById('swVersion').value = '';
    document.getElementById('asmDateStarted').value = '';
    document.getElementById('asmInCharge').value = '';
    
    // Reset serial inputs
    serialContainer.innerHTML = `
        <div class="serial-input-row">
            <input type="text" class="form-input serial-input" placeholder="Serial Number 1">
        </div>
    `;
    serialCount = 1;
}

function resetRepairForm() {
    document.getElementById('repProductNumber').value = '';
    document.getElementById('repSerialNumber').value = '';
    document.getElementById('repOrderNumber').value = '';
    document.getElementById('defect').value = '';
    document.getElementById('startDate').value = '';
    document.getElementById('targetDate').value = '';
    document.getElementById('repInCharge').value = '';
}

// ===== Utility Functions =====
function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toISOString().split('T')[0].replace(/-/g, '/');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// ===== Initialize =====
updateCounts();
