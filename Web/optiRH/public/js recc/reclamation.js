// public/js recc/reclamation.js

function showFlashMessages() {
    document.querySelectorAll('.flash-message').forEach(function(flash) {
        const type = flash.dataset.type;
        const message = flash.dataset.message;

        Swal.fire({
            icon: type === 'success' ? 'success' : (type === 'error' ? 'error' : 'info'),
            title: message,
            showConfirmButton: false,
            timer: 3000
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les tooltips Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Confirmation SweetAlert pour suppression
    document.querySelectorAll('form[data-swal-confirm]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: "Cette action est irréversible !",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui, supprimer',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Ici au lieu de form.submit(), on fait manuellement la soumission
                    const formData = new FormData(form);
                    fetch(form.action, {
                        method: 'POST',
                        body: formData
                    }).then(response => {
                        if (response.redirected) {
                            window.location.href = response.url;
                        } else {
                            window.location.reload();
                        }
                    });
                }
            });
        });
    });

    // Appeler après DOM ready
    showFlashMessages();
});
