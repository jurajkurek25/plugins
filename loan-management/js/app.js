/**
 * Main Application JavaScript
 * Dashboard a správa pôžičiek
 */

let allLoans = [];
let allUsers = [];

// Helper funkcia na zobrazenie alertov
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alert-container');
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;

    alertContainer.innerHTML = '';
    alertContainer.appendChild(alertDiv);

    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Helper funkcia pre formátovanie dátumu
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('sk-SK', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Helper funkcia pre formátovanie sumy
function formatAmount(amount) {
    return parseFloat(amount).toFixed(2) + ' €';
}

// Navigácia medzi sekciami
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', (e) => {
        e.preventDefault();

        // Odstránenie aktívnej triedy zo všetkých linkov
        document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
        e.target.classList.add('active');

        // Skrytie všetkých sekcií
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.add('hidden');
        });

        // Zobrazenie vybranej sekcie
        const sectionId = e.target.dataset.section;
        document.getElementById(`section-${sectionId}`).classList.remove('hidden');

        // Načítanie dát podľa sekcie
        switch (sectionId) {
            case 'overview':
                loadOverview();
                break;
            case 'my-loans':
                loadAllLoans();
                break;
            case 'borrowed':
                loadBorrowedLoans();
                break;
            case 'lent':
                loadLentLoans();
                break;
            case 'pending':
                loadPendingLoans();
                break;
            case 'qr-code':
                loadQRCode();
                break;
            case 'notifications':
                loadNotifications();
                break;
        }
    });
});

// Odhlásenie
document.getElementById('logout-btn').addEventListener('click', async () => {
    const formData = new FormData();
    formData.append('action', 'logout');

    try {
        const response = await fetch('php/api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            window.location.href = 'login.php';
        }
    } catch (error) {
        console.error('Logout error:', error);
    }
});

// Načítanie prehľadu
async function loadOverview() {
    await loadLoans();
    calculateStats();
}

// Načítanie pôžičiek
async function loadLoans() {
    try {
        const response = await fetch('php/api.php?action=get_user_loans');
        const result = await response.json();

        if (result.success) {
            allLoans = result.loans;
        }
    } catch (error) {
        console.error('Load loans error:', error);
    }
}

// Vypočítanie štatistík
function calculateStats() {
    let totalLent = 0;
    let totalBorrowed = 0;
    let activeCount = 0;
    let pendingCount = 0;

    allLoans.forEach(loan => {
        if (loan.lender_id == currentUserId) {
            totalLent += parseFloat(loan.remaining_amount);
            if (loan.status === 'active') activeCount++;
        }
        if (loan.borrower_id == currentUserId) {
            totalBorrowed += parseFloat(loan.remaining_amount);
        }
        if (loan.status === 'pending' && loan.lender_id == currentUserId) {
            pendingCount++;
        }
    });

    document.getElementById('stat-lent').textContent = formatAmount(totalLent);
    document.getElementById('stat-borrowed').textContent = formatAmount(totalBorrowed);
    document.getElementById('stat-active').textContent = activeCount;
    document.getElementById('stat-pending').textContent = pendingCount;
}

// Načítanie všetkých pôžičiek
async function loadAllLoans() {
    await loadLoans();
    const container = document.getElementById('all-loans-container');
    container.innerHTML = renderLoansTable(allLoans);
}

// Načítanie požičaných pôžičiek
async function loadBorrowedLoans() {
    await loadLoans();
    const borrowed = allLoans.filter(loan => loan.borrower_id == currentUserId);
    const container = document.getElementById('borrowed-loans-container');
    container.innerHTML = renderLoansTable(borrowed);
}

// Načítanie poskytnutých pôžičiek
async function loadLentLoans() {
    await loadLoans();
    const lent = allLoans.filter(loan => loan.lender_id == currentUserId);
    const container = document.getElementById('lent-loans-container');
    container.innerHTML = renderLoansTable(lent);
}

// Načítanie čakajúcich žiadostí
async function loadPendingLoans() {
    await loadLoans();
    const pending = allLoans.filter(loan => loan.status === 'pending' && loan.lender_id == currentUserId);
    const container = document.getElementById('pending-loans-container');

    if (pending.length === 0) {
        container.innerHTML = '<p>Nemáte žiadne čakajúce žiadosti.</p>';
        return;
    }

    let html = '<table class="loans-table"><thead><tr>';
    html += '<th>Od</th><th>Suma</th><th>Dátum</th><th>Akcie</th>';
    html += '</tr></thead><tbody>';

    pending.forEach(loan => {
        html += `<tr>
            <td>${loan.borrower_name}</td>
            <td>${formatAmount(loan.amount)}</td>
            <td>${formatDate(loan.request_date)}</td>
            <td>
                <button class="btn btn-success btn-sm" onclick="approveLoan(${loan.id})">Schváliť</button>
                <button class="btn btn-danger btn-sm" onclick="rejectLoan(${loan.id})">Zamietnuť</button>
            </td>
        </tr>`;
    });

    html += '</tbody></table>';
    container.innerHTML = html;
}

// Rendering tabuľky pôžičiek
function renderLoansTable(loans) {
    if (loans.length === 0) {
        return '<p>Žiadne pôžičky.</p>';
    }

    let html = '<table class="loans-table"><thead><tr>';
    html += '<th>Veriteľ</th><th>Dlžník</th><th>Suma</th><th>Zostáva</th><th>Stav</th><th>Dátum</th>';
    html += '</tr></thead><tbody>';

    loans.forEach(loan => {
        html += `<tr class="clickable" onclick="showLoanDetails(${loan.id})">
            <td>${loan.lender_name}</td>
            <td>${loan.borrower_name}</td>
            <td>${formatAmount(loan.amount)}</td>
            <td>${formatAmount(loan.remaining_amount)}</td>
            <td><span class="status-badge status-${loan.status}">${getStatusText(loan.status)}</span></td>
            <td>${formatDate(loan.request_date)}</td>
        </tr>`;
    });

    html += '</tbody></table>';
    return html;
}

// Prevod status kódu na text
function getStatusText(status) {
    const statusMap = {
        'pending': 'Čaká',
        'active': 'Aktívna',
        'completed': 'Splatená',
        'rejected': 'Zamietnutá',
        'cancelled': 'Zrušená'
    };
    return statusMap[status] || status;
}

// Otvorenie modalu pre novú pôžičku
async function openNewLoanModal() {
    // Načítanie používateľov
    if (allUsers.length === 0) {
        await loadUsers();
    }

    const select = document.getElementById('lender-select');
    select.innerHTML = '<option value="">Vyberte používateľa...</option>';

    allUsers.forEach(user => {
        const option = document.createElement('option');
        option.value = user.id;
        option.textContent = `${user.full_name} (@${user.username})`;
        select.appendChild(option);
    });

    document.getElementById('new-loan-modal').classList.add('active');
}

// Zatvorenie modalu pre novú pôžičku
function closeNewLoanModal() {
    document.getElementById('new-loan-modal').classList.remove('active');
    document.getElementById('new-loan-form').reset();
}

// Načítanie používateľov
async function loadUsers() {
    try {
        const response = await fetch('php/api.php?action=get_all_users');
        const result = await response.json();

        if (result.success) {
            allUsers = result.users;
        }
    } catch (error) {
        console.error('Load users error:', error);
    }
}

// Odoslanie žiadosti o pôžičku
document.getElementById('new-loan-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    const formData = new FormData(e.target);
    formData.append('action', 'create_loan_request');

    try {
        const response = await fetch('php/api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showAlert(result.message, 'success');
            closeNewLoanModal();
            loadOverview();
        } else {
            showAlert(result.message, 'danger');
        }
    } catch (error) {
        showAlert('Chyba pri vytváraní žiadosti', 'danger');
        console.error('Create loan error:', error);
    }
});

// Schválenie pôžičky
async function approveLoan(loanId) {
    if (!confirm('Naozaj chcete schváliť túto pôžičku?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'approve_loan');
    formData.append('loan_id', loanId);

    try {
        const response = await fetch('php/api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showAlert(result.message, 'success');
            loadPendingLoans();
            loadOverview();
        } else {
            showAlert(result.message, 'danger');
        }
    } catch (error) {
        showAlert('Chyba pri schvaľovaní pôžičky', 'danger');
        console.error('Approve loan error:', error);
    }
}

// Zamietnutie pôžičky
async function rejectLoan(loanId) {
    if (!confirm('Naozaj chcete zamietnuť túto pôžičku?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'reject_loan');
    formData.append('loan_id', loanId);

    try {
        const response = await fetch('php/api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showAlert(result.message, 'success');
            loadPendingLoans();
            loadOverview();
        } else {
            showAlert(result.message, 'danger');
        }
    } catch (error) {
        showAlert('Chyba pri zamietaní pôžičky', 'danger');
        console.error('Reject loan error:', error);
    }
}

// Zobrazenie detailov pôžičky
async function showLoanDetails(loanId) {
    try {
        const response = await fetch(`php/api.php?action=get_loan_details&loan_id=${loanId}`);
        const result = await response.json();

        if (result.success) {
            renderLoanDetails(result.loan, result.payments);
            document.getElementById('loan-details-modal').classList.add('active');
        } else {
            showAlert(result.message, 'danger');
        }
    } catch (error) {
        showAlert('Chyba pri načítaní detailov', 'danger');
        console.error('Load loan details error:', error);
    }
}

// Rendering detailov pôžičky
function renderLoanDetails(loan, payments) {
    const isLender = loan.lender_id == currentUserId;
    const isBorrower = loan.borrower_id == currentUserId;

    let html = `
        <div style="margin-bottom: 20px;">
            <p><strong>Veriteľ:</strong> ${loan.lender_name}</p>
            <p><strong>Dlžník:</strong> ${loan.borrower_name}</p>
            <p><strong>Pôvodná suma:</strong> ${formatAmount(loan.amount)}</p>
            <p><strong>Zostáva splatiť:</strong> ${formatAmount(loan.remaining_amount)}</p>
            <p><strong>Stav:</strong> <span class="status-badge status-${loan.status}">${getStatusText(loan.status)}</span></p>
            <p><strong>Dátum žiadosti:</strong> ${formatDate(loan.request_date)}</p>
            ${loan.description ? `<p><strong>Popis:</strong> ${loan.description}</p>` : ''}
        </div>
    `;

    // Pridanie splátky (len pre dlžníka a aktívne pôžičky)
    if (isBorrower && loan.status === 'active') {
        html += `
            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <h3>Pridať splátku</h3>
                <form id="add-payment-form" onsubmit="addPayment(event, ${loan.id})">
                    <div class="form-group">
                        <label>Suma splátky (€)</label>
                        <input type="number" name="amount" step="0.01" min="0.01" max="${loan.remaining_amount}" required>
                    </div>
                    <div class="form-group">
                        <label>Poznámka (voliteľné)</label>
                        <input type="text" name="description">
                    </div>
                    <button type="submit" class="btn btn-primary">Pridať splátku</button>
                </form>
            </div>
        `;
    }

    // Zoznam splátok
    html += '<h3>História splátok</h3>';

    if (payments.length === 0) {
        html += '<p>Zatiaľ žiadne splátky.</p>';
    } else {
        html += '<div class="payments-list">';

        payments.forEach(payment => {
            html += `
                <div class="payment-item">
                    <div class="payment-info">
                        <strong>${formatAmount(payment.amount)}</strong>
                        <span class="status-badge status-${payment.status}">${getStatusText(payment.status)}</span>
                        <br>
                        <small>${formatDate(payment.payment_date)}</small>
                        ${payment.description ? `<br><small>${payment.description}</small>` : ''}
                    </div>
                    <div class="payment-actions">
                        ${payment.status === 'pending' && isLender ? `
                            <button class="btn btn-success btn-sm" onclick="confirmPayment(${payment.id}, ${loan.id})">Potvrdiť</button>
                            <button class="btn btn-danger btn-sm" onclick="rejectPayment(${payment.id}, ${loan.id})">Zamietnuť</button>
                        ` : ''}
                    </div>
                </div>
            `;
        });

        html += '</div>';
    }

    document.getElementById('loan-details-content').innerHTML = html;
}

// Zatvorenie modalu detailov
function closeLoanDetailsModal() {
    document.getElementById('loan-details-modal').classList.remove('active');
}

// Pridanie splátky
async function addPayment(event, loanId) {
    event.preventDefault();

    const form = event.target;
    const formData = new FormData(form);
    formData.append('action', 'add_payment');
    formData.append('loan_id', loanId);

    try {
        const response = await fetch('php/api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showAlert(result.message, 'success');
            showLoanDetails(loanId); // Reload details
        } else {
            showAlert(result.message, 'danger');
        }
    } catch (error) {
        showAlert('Chyba pri pridávaní splátky', 'danger');
        console.error('Add payment error:', error);
    }
}

// Potvrdenie splátky
async function confirmPayment(paymentId, loanId) {
    if (!confirm('Naozaj chcete potvrdiť túto splátku?')) {
        return;
    }

    const formData = new FormData();
    formData.append('action', 'confirm_payment');
    formData.append('payment_id', paymentId);

    try {
        const response = await fetch('php/api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showAlert(result.message, 'success');
            showLoanDetails(loanId);
            loadOverview();
        } else {
            showAlert(result.message, 'danger');
        }
    } catch (error) {
        showAlert('Chyba pri potvrdzovaní splátky', 'danger');
        console.error('Confirm payment error:', error);
    }
}

// Zamietnutie splátky
async function rejectPayment(paymentId, loanId) {
    const reason = prompt('Dôvod zamietnutia (voliteľné):');

    if (reason === null) {
        return; // Zrušené
    }

    const formData = new FormData();
    formData.append('action', 'reject_payment');
    formData.append('payment_id', paymentId);
    formData.append('reason', reason);

    try {
        const response = await fetch('php/api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showAlert(result.message, 'success');
            showLoanDetails(loanId);
        } else {
            showAlert(result.message, 'danger');
        }
    } catch (error) {
        showAlert('Chyba pri zamietaní splátky', 'danger');
        console.error('Reject payment error:', error);
    }
}

// Načítanie notifikácií
async function loadNotifications() {
    try {
        const response = await fetch('php/api.php?action=get_notifications&limit=20');
        const result = await response.json();

        if (result.success) {
            renderNotifications(result.notifications);
        }
    } catch (error) {
        console.error('Load notifications error:', error);
    }
}

// Rendering notifikácií
function renderNotifications(notifications) {
    const container = document.getElementById('notifications-container');

    if (notifications.length === 0) {
        container.innerHTML = '<p>Žiadne notifikácie.</p>';
        return;
    }

    let html = '';

    notifications.forEach(notif => {
        const unreadClass = notif.is_read == 0 ? 'unread' : '';
        html += `
            <div class="notification-item ${unreadClass}">
                <strong>${notif.message}</strong>
                <div class="time">${formatDate(notif.created_at)}</div>
            </div>
        `;
    });

    container.innerHTML = html;
}

// Načítanie QR kódu
async function loadQRCode() {
    const container = document.getElementById('qr-code-container');
    container.innerHTML = '<div class="spinner"></div><p>Načítavam QR kód...</p>';

    // Kontrola či je QRCode knižnica dostupná
    if (typeof QRCode === 'undefined') {
        container.innerHTML = '<p class="alert alert-danger">QR kód knižnica sa nenačítala. Obnovte stránku.</p>';
        console.error('QRCode library not loaded');
        return;
    }

    try {
        const response = await fetch('php/api.php?action=get_login_token');
        const result = await response.json();

        if (result.success && result.token) {
            const protocol = window.location.protocol;
            const host = window.location.host;
            const path = window.location.pathname.substring(0, window.location.pathname.lastIndexOf('/'));
            const loginUrl = `${protocol}//${host}${path}/login-token.php?token=${result.token}`;

            // Vyčistenie containera
            container.innerHTML = '';

            // Vytvorenie QR kódu
            new QRCode(container, {
                text: loginUrl,
                width: 256,
                height: 256,
                colorDark: '#000000',
                colorLight: '#ffffff',
                correctLevel: QRCode.CorrectLevel.H
            });

            // Uloženie URL pre kopírovanie
            window.currentLoginUrl = loginUrl;
        } else {
            container.innerHTML = '<p class="alert alert-danger">Chyba: ' + (result.message || 'Token nebol nájdený') + '</p>';
        }
    } catch (error) {
        console.error('Load QR code error:', error);
        container.innerHTML = '<p class="alert alert-danger">Chyba pri načítaní QR kódu: ' + error.message + '</p>';
    }
}

// Regenerácia tokenu
async function regenerateToken() {
    if (!confirm('Naozaj chcete vygenerovať nový QR kód? Starý QR kód prestane fungovať.')) {
        return;
    }

    try {
        const formData = new FormData();
        formData.append('action', 'regenerate_login_token');

        const response = await fetch('php/api.php', {
            method: 'POST',
            body: formData
        });

        const result = await response.json();

        if (result.success) {
            showAlert('Nový QR kód bol vygenerovaný', 'success');
            loadQRCode(); // Znovu načítaj QR kód
        } else {
            showAlert(result.message, 'danger');
        }
    } catch (error) {
        showAlert('Chyba pri generovaní nového QR kódu', 'danger');
        console.error('Regenerate token error:', error);
    }
}

// Kopírovanie prihlasovacieho odkazu
function copyLoginLink() {
    if (!window.currentLoginUrl) {
        showAlert('Najprv načítajte QR kód', 'warning');
        return;
    }

    navigator.clipboard.writeText(window.currentLoginUrl).then(() => {
        showAlert('Odkaz bol skopírovaný do schránky', 'success');
    }).catch(err => {
        showAlert('Chyba pri kopírovaní odkazu', 'danger');
        console.error('Copy error:', err);
    });
}

// Otvorenie náhľadu kartičky
function openCardPreview() {
    window.open('card.php', '_blank');
}

// Zatvorenie modalov pri kliku mimo
window.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('active');
    }
});

// Inicializácia po načítaní stránky
document.addEventListener('DOMContentLoaded', () => {
    loadOverview();
});
