document.addEventListener('DOMContentLoaded', function () {
    // Attacher l'événement de suppression à chaque bouton
    document.querySelectorAll('.delete-btn').forEach(function (button) {
        button.addEventListener('click', function (event) {
            const reponseId = event.target.getAttribute('data-id');
            const formId = `#delete-form-${reponseId}`;

            // Afficher la confirmation avec SweetAlert
            Swal.fire({
                title: 'Êtes-vous sûr ?',
                text: 'Vous ne pourrez pas annuler cette action !',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Oui, supprimer !',
                cancelButtonText: 'Annuler'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Soumettre le formulaire si l'utilisateur confirme
                    document.querySelector(formId).submit();
                }
            });
        });
    });
});
