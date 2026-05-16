// assets/js/main.js - Global Energy B.C.

// Confirmación antes de acciones destructivas
document.addEventListener('click', function(e) {
    if (e.target.dataset.confirm) {
        if (!confirm(e.target.dataset.confirm)) e.preventDefault();
    }
});

// Cerrar alertas al hacer clic
document.querySelectorAll('.alert').forEach(function(el) {
    el.style.cursor = 'pointer';
    el.title = 'Clic para cerrar';
    el.addEventListener('click', function() {
        this.style.display = 'none';
    });
});
