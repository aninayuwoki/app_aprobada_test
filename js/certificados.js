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

function formatearFecha(fecha) {
    if (!fecha) return 'N/A';
    const opciones = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(fecha).toLocaleDateString('es-ES', opciones);
}

// Efecto ripple para botones
function createRipple(event) {
    const button = event.currentTarget;
    const ripple = document.createElement('span');
    const rect = button.getBoundingClientRect();
    const size = Math.max(rect.width, rect.height);
    const x = event.clientX - rect.left - size / 2;
    const y = event.clientY - rect.top - size / 2;
    
    ripple.style.width = ripple.style.height = size + 'px';
    ripple.style.left = x + 'px';
    ripple.style.top = y + 'px';
    ripple.classList.add('ripple-effect');
    
    button.appendChild(ripple);
    
    setTimeout(() => ripple.remove(), 600);
}

// Copiar enlace con feedback mejorado
function copiarEnlace(token) {
    const url = window.location.origin + window.location.pathname.replace('certificados.php', '') + 'ver_certificado.php?token=' + token;
    
    navigator.clipboard.writeText(url).then(() => {
        mostrarNotificacion('¡Enlace copiado al portapapeles! 📋', 'success');
    }).catch(err => {
        // Fallback para navegadores antiguos
        const input = document.createElement('input');
        input.value = url;
        input.style.position = 'fixed';
        input.style.opacity = '0';
        document.body.appendChild(input);
        input.select();
        document.execCommand('copy');
        document.body.removeChild(input);
        mostrarNotificacion('¡Enlace copiado al portapapeles! 📋', 'success');
    });
}

// Sistema de notificaciones mejorado
function mostrarNotificacion(mensaje, tipo = 'info') {
    // Crear contenedor de notificaciones si no existe
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
        background: linear-gradient(135deg, ${tipo === 'success' ? '#10b981' : '#3b82f6'} 0%, ${tipo === 'success' ? '#059669' : '#2563eb'} 100%);
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
    icono.textContent = tipo === 'success' ? '✓' : 'ℹ';
    icono.style.cssText = `
        font-size: 24px;
        font-weight: bold;
    `;
    
    const texto = document.createElement('span');
    texto.textContent = mensaje;
    
    notificacion.appendChild(icono);
    notificacion.appendChild(texto);
    contenedor.appendChild(notificacion);
    
    // Animación de salida
    setTimeout(() => {
        notificacion.style.animation = 'slideOutRight 0.4s cubic-bezier(0.4, 0, 0.2, 1)';
        setTimeout(() => notificacion.remove(), 400);
    }, 3000);
}

// Crear loading spinner mejorado
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
                Consultando certificados
            </p>
        </div>
    `;
}

// Animar entrada de elementos
function animarEntrada(elementos) {
    elementos.forEach((elemento, index) => {
        elemento.style.animationDelay = `${index * 0.1}s`;
        elemento.classList.add('fade-in-up');
    });
}

const certificadosForm = document.getElementById('certificadosForm');
if (certificadosForm) {
    certificadosForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const cedula = document.getElementById('cedulaCert').value;
        if (cedula.length !== 10 || isNaN(cedula)) {
            mostrarNotificacion('La cédula debe tener 10 dígitos numéricos', 'error');
            return;
        }

        const resultsDiv = document.getElementById('resultsCert');
        resultsDiv.classList.remove('visible');
        
        // Mostrar loading spinner
        resultsDiv.innerHTML = crearLoadingSpinner();
        resultsDiv.classList.add('visible');

        fetch('query_certificados.php', {
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
                                <td>${escapeHTML(data.user.cedula)}</td>
                            </tr>
                            <tr style="animation: fadeInUp 0.6s ease-out 0.2s both;">
                                <th>Nombre Completo</th>
                                <td>${escapeHTML(data.user.nombre_completo)}</td>
                            </tr>
                        </table>
                    </div>
                    <h2 style="animation: fadeInUp 0.6s ease-out 0.3s both;">Certificados Obtenidos (${data.certificados.length})</h2>
                `;
                
                data.certificados.forEach((cert, index) => {
                    const urlCompartible = window.location.origin + window.location.pathname.replace('certificados.php', '') + 'ver_certificado.php?token=' + cert.token;
                    
                    html += `
                        <div class="certificado-card" style="animation-delay: ${0.4 + (index * 0.1)}s;">
                            <div class="certificado-header">
                                <span class="tipo-badge ${cert.tipo_certificado.toLowerCase()}">${escapeHTML(cert.tipo_certificado)}</span>
                                <h3>${escapeHTML(cert.nombre_certificacion)}</h3>
                            </div>
                            <table class="certificado-detalles">
                                <tr>
                                    <th>Código de Certificado</th>
                                    <td><strong style="font-family: 'Courier New', monospace; color: #023A60;">${escapeHTML(cert.codigo_certificado)}</strong></td>
                                </tr>
                                <tr>
                                    <th>Empresa Capacitadora</th>
                                    <td>${escapeHTML(cert.empresa_capacitadora)}</td>
                                </tr>
                                <tr>
                                    <th>Vigencia Desde</th>
                                    <td>${formatearFecha(cert.fecha_inicio_vigencia)}</td>
                                </tr>
                                <tr>
                                    <th>Vigencia Hasta</th>
                                    <td>${formatearFecha(cert.fecha_fin_vigencia)}</td>
                                </tr>
                                ${cert.horas_totales ? `
                                <tr>
                                    <th>Horas Totales</th>
                                    <td><strong>${escapeHTML(cert.horas_totales)} horas</strong></td>
                                </tr>
                                ` : ''}
                            </table>
                            <div class="acciones-certificado">
                                <button onclick="copiarEnlace('${cert.token}')" class="btn-copiar" onmousedown="createRipple(event)">
                                    📋 Copiar Enlace Compartible
                                </button>
                                <a href="ver_certificado.php?token=${cert.token}" target="_blank" class="btn-ver" onmousedown="createRipple(event)">
                                    🔗 Ver Certificado
                                </a>
                            </div>
                        </div>
                    `;
                });
                
                mostrarNotificacion(`Se encontraron ${data.certificados.length} certificado(s)`, 'success');
            } else {
                html = `
                    <div style="text-align: center; padding: 60px 20px; animation: scaleIn 0.6s ease-out;">
                        <div style="font-size: 80px; margin-bottom: 20px; animation: float 3s ease-in-out infinite;">🔍</div>
                        <p style="font-size: 20px; color: #023A60; font-weight: 600; margin-bottom: 10px;">${escapeHTML(data.message)}</p>
                        <p style="color: #666; font-size: 16px;">Intenta con otra cédula</p>
                    </div>
                `;
                mostrarNotificacion('No se encontraron certificados', 'info');
            }
            
            resultsDiv.innerHTML = html;
            resultsDiv.classList.remove('visible');
            
            // Pequeño delay para la animación
            setTimeout(() => {
                resultsDiv.classList.add('visible');
            }, 50);
            
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

// Agregar estilos para las animaciones al cargar la página
document.addEventListener('DOMContentLoaded', function() {
    // Agregar estilos adicionales para animaciones
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
        
        .fade-in-up {
            animation: fadeInUp 0.6s ease-out;
            animation-fill-mode: both;
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
        
        .ripple-effect {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.6);
            transform: scale(0);
            animation: ripple 0.6s ease-out;
            pointer-events: none;
        }
        
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        button, .btn-copiar, .btn-ver {
            position: relative;
            overflow: hidden;
        }
        
        .notification.notification-error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%) !important;
        }
    `;
    document.head.appendChild(style);
    
    // Animar elementos existentes al cargar
    const cards = document.querySelectorAll('.certificado-card');
    animarEntrada(Array.from(cards));
});