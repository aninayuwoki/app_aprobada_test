// Modal Management for the main User form
function openUserModal() {
    document.getElementById('userModal').classList.add('active');
    document.getElementById('userForm').reset();
    document.getElementById('formAction').value = 'create';
    document.getElementById('modalTitle').textContent = 'Nuevo Usuario';
    document.getElementById('cedula').removeAttribute('readonly');
    document.getElementById('nuevaInstruccionContainer').style.display = 'none';
}

function closeUserModal() {
    document.getElementById('userModal').classList.remove('active');
}

// Toggle nueva instrucción
function toggleNuevaInstruccion() {
    const select = document.getElementById('instruccion');
    const container = document.getElementById('nuevaInstruccionContainer');
    const nuevaInstruccionInput = document.getElementById('nueva_instruccion');
    
    if (select.value === 'nueva') {
        container.style.display = 'block';
        nuevaInstruccionInput.required = true;
    } else {
        container.style.display = 'none';
        nuevaInstruccionInput.required = false;
        nuevaInstruccionInput.value = '';
        document.getElementById('institucion').value = '';
    }
}

// Edit User
function editUser(user) {
    document.getElementById('userModal').classList.add('active');
    document.getElementById('formAction').value = 'update';
    document.getElementById('modalTitle').textContent = 'Editar Usuario';
    document.getElementById('userId').value = user.US_ID;
    document.getElementById('nuevaInstruccionContainer').style.display = 'none';
    
    // Fill form with user data
    document.getElementById('cedula').value = user.US_CEDULA;
    document.getElementById('cedula').setAttribute('readonly', 'readonly');
    document.getElementById('nombre').value = user.US_NOMBRE;
    document.getElementById('apellido').value = user.US_APELLIDO;
    document.getElementById('fecha_nacimiento').value = user.US_FECHA_NACIMIENTO;
    document.getElementById('edad').value = user.US_EDAD || '';
    document.getElementById('genero').value = user.US_GENERO || '';
    document.getElementById('estado_civil').value = user.US_ESTADO_CIVIL || '';
    document.getElementById('tipo_sangre').value = user.US_TIPO_SANGRE || '';
    document.getElementById('etnia').value = user.US_ETNIA || '';
    document.getElementById('telefono').value = user.US_TELEFONO || '';
    document.getElementById('celular').value = user.US_CELULAR;
    document.getElementById('correo').value = user.US_CORREO;
    document.getElementById('parroquia').value = user.PRR_ID;
    document.getElementById('tipo_usuario').value = user.TUS_ID;
    document.getElementById('instruccion').value = user.INF_ID || '';
}

// Delete User
function deleteUser(id) {
    if (confirm('¿Está seguro de que desea eliminar este usuario?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Close the main user modal on outside click
window.addEventListener('click', function(event) {
    const userModal = document.getElementById('userModal');
    if (event.target === userModal) {
        closeUserModal();
    }
});

// Form validation
document.getElementById('userForm').addEventListener('submit', function(e) {
    const cedula = document.getElementById('cedula').value;
    const celular = document.getElementById('celular').value;
    const telefono = document.getElementById('telefono').value;
    const instruccion = document.getElementById('instruccion').value;
    const nuevaInstruccion = document.getElementById('nueva_instruccion').value;
    
    if (cedula.length !== 10 || !/^\d+$/.test(cedula)) {
        e.preventDefault();
        alert('La cédula debe tener exactamente 10 dígitos');
        return false;
    }
    
    if (celular.length !== 10 || !/^\d+$/.test(celular)) {
        e.preventDefault();
        alert('El celular debe tener exactamente 10 dígitos');
        return false;
    }
    
    if (telefono && (telefono.length < 7 || !/^\d+$/.test(telefono))) {
        e.preventDefault();
        alert('El teléfono debe tener entre 7 y 10 dígitos');
        return false;
    }
    
    // Validar que al menos uno de los campos de instrucción esté lleno
    if (instruccion === 'nueva' && !nuevaInstruccion.trim()) {
        e.preventDefault();
        alert('Por favor, ingrese el título de la nueva instrucción');
        return false;
    }
});

// Auto-hide alert after 5 seconds
window.addEventListener('load', function() {
    const alert = document.getElementById('alert');
    if (alert) {
        setTimeout(function() {
            alert.style.animation = 'slideUp 0.4s ease-out reverse';
            setTimeout(function() {
                alert.style.display = 'none';
            }, 400);
        }, 5000);
    }
});

// Only allow numbers in numeric fields
document.getElementById('cedula').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').substring(0, 10);
});

document.getElementById('celular').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').substring(0, 10);
});

document.getElementById('telefono').addEventListener('input', function(e) {
    this.value = this.value.replace(/\D/g, '').substring(0, 10);
});

// Format names (capitalize first letter)
function capitalizeWords(input) {
    return input.value.split(' ')
        .map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase())
        .join(' ');
}

document.getElementById('nombre').addEventListener('blur', function() {
    this.value = capitalizeWords(this);
});

document.getElementById('apellido').addEventListener('blur', function() {
    this.value = capitalizeWords(this);
});