document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.querySelector('.toggle-password');
    const pwInput   = document.getElementById('password');

    if (toggleBtn && pwInput) {
        toggleBtn.addEventListener('click', () => {
            const isPw = pwInput.type === 'password';
            pwInput.type = isPw ? 'text' : 'password';
            toggleBtn.querySelector('i').className = isPw ? 'fa fa-eye-slash' : 'fa fa-eye';
            toggleBtn.setAttribute('aria-label', isPw ? 'Skrýt heslo' : 'Zobrazit heslo');
        });
    }
});