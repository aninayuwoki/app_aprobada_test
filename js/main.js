function escapeHTML(str) {
    if (str === null || str === undefined) return '';
    str = String(str);
    
    return str.replace(/[&<>'"]/g,
        tag => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#39;',
            '"': '&quot;'
        }[tag] || tag)
    );
}

// Sistema de notificaciones
function mostrarNotificacion(mensaje, tipo = 'info') {
    let contenedor = document.getElementById('notification-container');
    if (!contenedor) {
        contenedor = document.createElement('div');
        contenedor.id = 'notification-container';
        contenedor.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
        `;
        document.body.appendChild(contenedor);
    }
    
    const notificacion = document.createElement('div');
    notificacion.className = `notification notification-${tipo}`;
    notificacion.style.cssText = `
        background: linear-gradient(135deg, ${tipo === 'success' ? '#10b981' : tipo === 'error' ? '#ef4444' : '#3b82f6'} 0%, ${tipo === 'success' ? '#059669' : tipo === 'error' ? '#dc2626' : '#2563eb'} 100%);
        color: white;
        padding: 16px 24px;
        border-radius: 12px;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
        min-width: 300px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-weight: 600;
        animation: slideInRight 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        backdrop-filter: blur(10px);
    `;
    
    const icono = document.createElement('span');
    icono.textContent = tipo === 'success' ? '✓' : tipo === 'error' ? '⚠' : 'ℹ';
    icono.style.cssText = 'font-size: 24px; font-weight: bold;';
    
    const texto = document.createElement('span');
    texto.textContent = mensaje;
    
    notificacion.appendChild(icono);
    notificacion.appendChild(texto);
    contenedor.appendChild(notificacion);
    
    setTimeout(() => {
        notificacion.style.animation = 'slideOutRight 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
        setTimeout(() => notificacion.remove(), 400);
    }, 3000);
}

// Loading spinner
function crearLoadingSpinner() {
    return `
        <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 60px 20px;">
            <div class="loading-spinner-wrapper">
                <div class="loading-spinner"></div>
                <div class="loading-dots">
                    <span style="animation-delay: 0s">.</span>
                    <span style="animation-delay: 0.2s">.</span>
                    <span style="animation-delay: 0.4s">.</span>
                </div>
            </div>
            <p style="margin-top: 24px; color: #023A60; font-weight: 600; font-size: 18px; animation: pulse 2s ease-in-out infinite;">
                Consultando información
            </p>
        </div>
    `;
}

// Verificar si el formulario existe antes de agregar el evento
const queryForm = document.getElementById('queryForm');
if (queryForm) {
    queryForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const cedula = document.getElementById('cedula').value;
        if (cedula.length !== 10 || isNaN(cedula)) {
            mostrarNotificacion('La cédula debe tener 10 dígitos numéricos', 'error');
            return;
        }

        const resultsDiv = document.getElementById('results');
        resultsDiv.classList.remove('visible');
        resultsDiv.innerHTML = crearLoadingSpinner();
        resultsDiv.classList.add('visible');

        fetch('query.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `cedula=${encodeURIComponent(cedula)}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.text();
        })
        .then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Respuesta del servidor:', text);
                throw new Error('La respuesta no es JSON válido');
            }
        })
        .then(data => {
            let html = '';
            if (data.success) {
                html += `
                    <div class="user-data" style="animation: scaleIn 0.6s ease-out;">
                        <h2>Datos del Usuario</h2>
                        <table>
                            <tr style="animation: fadeInUp 0.6s ease-out 0.1s both;">
                                <th>Cédula</th>
                                <td>${escapeHTML(data.user.us_cedula)}</td>
                            </tr>
                            <tr style="animation: fadeInUp 0.6s ease-out 0.2s both;">
                                <th>Nombre Completo</th>
                                <td>${escapeHTML(data.user.us_nombre)} ${escapeHTML(data.user.us_apellido)}</td>
                            </tr>
                            <tr style="animation: fadeInUp 0.6s ease-out 0.3s both;">
                                <th>Género</th>
                                <td>${escapeHTML(data.user.us_genero)}</td>
                            </tr>
                        </table>
                    </div>
                `;
                
                if (data.courses && data.courses.length > 0) {
                    html += `<h2 style="animation: fadeInUp 0.6s ease-out 0.4s both;">Cursos Matriculados (${data.courses.length})</h2>
                    <div style="overflow-x: auto; animation: fadeInUp 0.6s ease-out 0.5s both;">
                        <table>
                            <thead>
                                <tr>
                                    <th>Código Matrícula</th>
                                    <th>Curso</th>
                                    <th>Empresa</th>
                                    <th>Horario Inicio</th>
                                    <th>Horario Fin</th>
                                    <th>Horas Totales</th>
                                </tr>
                            </thead>
                            <tbody>
                    `;
                    data.courses.forEach((course, index) => {
                        html += `
                            <tr style="animation: fadeInUp 0.4s ease-out ${0.6 + (index * 0.05)}s both;">
                                <td><strong style="font-family: 'Courier New', monospace; color: #023A60;">${escapeHTML(course.mat_codigo)}</strong></td>
                                <td>${escapeHTML(course.cur_nombre)}</td>
                                <td>${escapeHTML(course.emp_nombre)}</td>
                                <td>${escapeHTML(course.dh_hora_inicio)}</td>
                                <td>${escapeHTML(course.dh_hora_fin)}</td>
                                <td><strong>${escapeHTML(course.cur_hora_total)} hrs</strong></td>
                            </tr>
                        `;
                    });
                    html += `
                            </tbody>
                        </table>
                    </div>
                    `;
                    mostrarNotificacion(`Se encontraron ${data.courses.length} matrícula(s)`, 'success');
                } else {
                    // Usuario sin matrículas
                    html += `
                        <div style="text-align: center; padding: 60px 20px; animation: scaleIn 0.6s ease-out;">
                            <div style="font-size: 80px; margin-bottom: 20px; animation: float 3s ease-in-out infinite;">📋</div>
                            <p style="font-size: 20px; color: #023A60; font-weight: 600; margin-bottom: 10px;">Usuario encontrado</p>
                            <p style="color: #666; font-size: 16px;">Este usuario no tiene matrículas activas registradas</p>
                        </div>
                    `;
                    mostrarNotificacion('Usuario encontrado sin matrículas', 'info');
                }
            } else {
                html = `
                    <div style="text-align: center; padding: 60px 20px; animation: scaleIn 0.6s ease-out;">
                        <div style="font-size: 80px; margin-bottom: 20px; animation: float 3s ease-in-out infinite;">🔍</div>
                        <p style="font-size: 20px; color: #023A60; font-weight: 600; margin-bottom: 10px;">${escapeHTML(data.message)}</p>
                        <p style="color: #666; font-size: 16px;">Intenta con otra cédula</p>
                    </div>
                `;
                mostrarNotificacion('No se encontró el usuario', 'info');
            }
            
            resultsDiv.innerHTML = html;
            resultsDiv.classList.remove('visible');
            setTimeout(() => resultsDiv.classList.add('visible'), 50);
        })
        .catch(error => {
            console.error('Error:', error);
            resultsDiv.innerHTML = `
                <div style="text-align: center; padding: 60px 20px; animation: scaleIn 0.6s ease-out;">
                    <div style="font-size: 80px; margin-bottom: 20px;">⚠️</div>
                    <p style="font-size: 20px; color: #ff5252; font-weight: 600; margin-bottom: 10px;">Error al conectar con el servidor</p>
                    <p style="color: #666; font-size: 16px;">${escapeHTML(error.message)}</p>
                </div>
            `;
            resultsDiv.classList.remove('visible');
            setTimeout(() => resultsDiv.classList.add('visible'), 50);
            
            mostrarNotificacion('Error al procesar la solicitud', 'error');
        });
    });
}

// Agregar estilos para animaciones
document.addEventListener('DOMContentLoaded', function() {
    const style = document.createElement('style');
    style.textContent = `
        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(100px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        @keyframes slideOutRight {
            from {
                opacity: 1;
                transform: translateX(0);
            }
            to {
                opacity: 0;
                transform: translateX(100px);
            }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .loading-spinner-wrapper {
            position: relative;
            width: 60px;
            height: 60px;
        }
        
        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(149, 215, 255, 0.3);
            border-top-color: #023A60;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .loading-dots {
            position: absolute;
            bottom: -30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 4px;
            font-size: 24px;
            font-weight: bold;
            color: #023A60;
        }
        
        .loading-dots span {
            animation: bounce 1.4s ease-in-out infinite;
        }
        
        @keyframes bounce {
            0%, 80%, 100% {
                transform: translateY(0);
                opacity: 0.4;
            }
            40% {
                transform: translateY(-10px);
                opacity: 1;
            }
        }
    `;
    document.head.appendChild(style);
});