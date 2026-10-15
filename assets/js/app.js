document.addEventListener('DOMContentLoaded', () => {
    const yearNode = document.querySelector('[data-current-year]');
    if (yearNode) {
        yearNode.textContent = new Date().getFullYear();
    }

    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                const originalText = button.textContent;
                button.textContent = 'Saving...';
                button.disabled = true;
                setTimeout(() => {
                    button.textContent = originalText;
                    button.disabled = false;
                }, 1500);
            }
        });
    }
});
