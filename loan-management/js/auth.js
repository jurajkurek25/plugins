/**
 * Authentication JavaScript
 * Prihlásenie a registrácia
 */

// Helper funkcia na zobrazenie alertov
function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alert-container');
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;

    alertContainer.innerHTML = '';
    alertContainer.appendChild(alertDiv);

    // Automatické skrytie po 5 sekundách
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

// Prihlásenie
const loginForm = document.getElementById('login-form');
if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(loginForm);
        formData.append('action', 'login');

        try {
            const response = await fetch('php/api.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert(result.message, 'success');
                setTimeout(() => {
                    window.location.href = 'index.php';
                }, 1000);
            } else {
                showAlert(result.message, 'danger');
            }
        } catch (error) {
            showAlert('Chyba pri komunikácii so serverom', 'danger');
            console.error('Login error:', error);
        }
    });
}

// Registrácia
const registerForm = document.getElementById('register-form');
if (registerForm) {
    registerForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const password = document.getElementById('password').value;
        const passwordConfirm = document.getElementById('password_confirm').value;

        // Kontrola zhody hesiel
        if (password !== passwordConfirm) {
            showAlert('Heslá sa nezhodujú', 'danger');
            return;
        }

        const formData = new FormData(registerForm);
        formData.append('action', 'register');

        try {
            const response = await fetch('php/api.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                showAlert(result.message + ' - Presmerovávam na prihlásenie...', 'success');
                setTimeout(() => {
                    window.location.href = 'login.php';
                }, 2000);
            } else {
                showAlert(result.message, 'danger');
            }
        } catch (error) {
            showAlert('Chyba pri komunikácii so serverom', 'danger');
            console.error('Registration error:', error);
        }
    });
}
