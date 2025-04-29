// Add this to your fetch request in add.html.twig script section
const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

fetch(translationUrl, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': token // Add CSRF token if available
    },
    body: new URLSearchParams({
        'text': textToTranslate,
        'target_lang': targetLanguage
    })
})
.then(response => {
    // Check if response is JSON before parsing
    const contentType = response.headers.get('content-type');
    if (!response.ok) {
        if (contentType && contentType.includes('application/json')) {
            return response.json().then(err => {
                throw new Error(err.error || 'Erreur de traduction');
            });
        } else {
            // Handle non-JSON responses
            return response.text().then(text => {
                console.error('Non-JSON response:', text.substring(0, 200));
                throw new Error('Erreur de communication avec le serveur');
            });
        }
    }
    
    if (contentType && contentType.includes('application/json')) {
        return response.json();
    } else {
        throw new Error('Le serveur n\'a pas renvoyé de JSON');
    }
})
// Modifiez la partie traitement de la réponse JSON dans votre script
.then(data => {
    if (data.success) {
        translatedTextField.value = data.translation;
        
        // Ajouter un indicateur du service utilisé (optionnel)
        if (data.method) {
            console.log('Traduction via: ' + data.method);
            const smallInfo = document.createElement('small');
            smallInfo.className = 'form-text text-muted mt-1';
            smallInfo.innerHTML = `<i class="fas fa-info-circle"></i> Traduction via: ${data.method}`;
            translatedTextField.parentNode.appendChild(smallInfo);
            
            // Supprimer après 3 secondes
            setTimeout(() => smallInfo.remove(), 3000);
        }
    } else {
        throw new Error(data.error || 'Erreur inconnue');
    }
})