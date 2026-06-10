document.querySelector('.btn-show-password-fields').addEventListener('click', (event) => {
    document.querySelectorAll('.field-password-policy.hidden').forEach(el => {
        el.classList.remove('hidden');
        el.querySelector('input').required = true;
    });

    event.target.classList.add('hidden');
});