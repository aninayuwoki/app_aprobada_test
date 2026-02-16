// js/modal-manager.js

// Single event listener for the Escape key to close the topmost active modal.
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        const modals = document.querySelectorAll('.modal.active');
        if (modals.length > 0) {
            // Get the last modal in the NodeList, which will be the topmost one.
            const topmostModal = modals[modals.length - 1];
            const entity = topmostModal.id.replace('modal-', '');
            closeModal(entity);
        }
    }
});


// Function to open a modal with content loaded from the server
function openModal(entity, parentSelectId = null) {
    // Prevent opening a modal if one is already active with the same entity
    if (document.getElementById(`modal-${entity}`)) {
        console.warn(`Modal for ${entity} is already open.`);
        return;
    }

    // Create modal structure
    const modal = document.createElement('div');
    modal.classList.add('modal', 'active');
    modal.id = `modal-${entity}`;
    modal.onclick = function(event) {
        if (event.target === modal) {
            closeModal(entity);
        }
    };

    const modalContent = document.createElement('div');
    modalContent.classList.add('modal-content');

    const modalHeader = document.createElement('div');
    modalHeader.classList.add('modal-header');
    modalHeader.innerHTML = `<h2 id="modalTitle">Nuevo ${entity.charAt(0).toUpperCase() + entity.slice(1).replace('_', ' ')}</h2><span class="close" onclick="closeModal('${entity}')">&times;</span>`;

    const formContainer = document.createElement('div');
    formContainer.id = `form-container-${entity}`;
    formContainer.innerHTML = '<div class="spinner"></div>'; // Loading spinner

    modalContent.appendChild(modalHeader);
    modalContent.appendChild(formContainer);
    modal.appendChild(modalContent);

    document.getElementById('modal-container').appendChild(modal);

    // Fetch form content
    fetch(`api/handler.php?action=get_form&entity=${entity}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            formContainer.innerHTML = html;
            const form = formContainer.querySelector('form');
            if (form) {
                form.onsubmit = (e) => handleFormSubmit(e, entity, parentSelectId);
            }
        })
        .catch(error => {
            formContainer.innerHTML = `<p>Error al cargar el formulario: ${error}</p>`;
        });
}

// Function to close a modal
function closeModal(entity) {
    const modal = document.getElementById(`modal-${entity}`);
    if (modal) {
        modal.remove();
    }
}

// Function to handle form submission
function handleFormSubmit(event, entity, parentSelectId) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);

    fetch(`api/handler.php?action=create&entity=${entity}`, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (parentSelectId) {
                const parentSelect = document.getElementById(parentSelectId);
                if (parentSelect) {
                    const newOption = new Option(data.text, data.id, true, true);
                    parentSelect.appendChild(newOption);
                    parentSelect.dispatchEvent(new Event('change'));
                }
            }
            closeModal(entity);
        } else {
            alert('Error al guardar: ' + (data.message || 'Ocurrió un error desconocido.'));
        }
    })
    .catch(error => {
        alert('Error en la conexión: ' + error);
    });
}
