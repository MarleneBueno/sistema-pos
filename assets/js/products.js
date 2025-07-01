/**
 * JavaScript para Gestión de Productos - Sistema POS
 * Funcionalidades: cálculo de márgenes, modal de stock, validaciones
 */

document.addEventListener('DOMContentLoaded', function() {
    initializeProductForm();
    initializeStockModal();
    initializeBarcodeScanner();
    initializeTableSorting();
    autoCalculateMargin();
});

/**
 * Inicializar formulario de productos
 */
function initializeProductForm() {
    const purchasePriceInput = document.getElementById('purchase_price');
    const salePriceInput = document.getElementById('sale_price');
    const marginInfo = document.getElementById('marginInfo');
    const barcodeInput = document.getElementById('barcode');
    
    // Calcular margen en tiempo real
    function calculateMargin() {
        const purchasePrice = parseFloat(purchasePriceInput.value) || 0;
        const salePrice = parseFloat(salePriceInput.value) || 0;
        
        if (purchasePrice > 0 && salePrice > 0) {
            const margin = ((salePrice - purchasePrice) / purchasePrice) * 100;
            const profit = salePrice - purchasePrice;
            
            marginInfo.innerHTML = `
                Margen: <span class="${getMarginClass(margin)}">${margin.toFixed(1)}%</span><br>
                Ganancia: $${profit.toFixed(2)}
            `;
            
            // Validar que el precio de venta sea mayor al de compra
            if (salePrice <= purchasePrice) {
                marginInfo.innerHTML += '<br><span style="color: #dc3545;">⚠️ Precio de venta debe ser mayor al de compra</span>';
            }
        } else {
            marginInfo.innerHTML = '';
        }
    }
    
    // Eventos de cálculo de margen
    if (purchasePriceInput && salePriceInput) {
        purchasePriceInput.addEventListener('input', calculateMargin);
        salePriceInput.addEventListener('input', calculateMargin);
        
        // Calcular al cargar si hay valores
        calculateMargin();
    }
    
    // Validación de código de barras
    if (barcodeInput) {
        barcodeInput.addEventListener('input', function() {
            const barcode = this.value;
            const isValid = /^\d{8,13}$/.test(barcode);
            
            this.style.borderColor = isValid || barcode === '' ? '#e9ecef' : '#dc3545';
            
            if (barcode.length > 0 && !isValid) {
                this.setCustomValidity('El código de barras debe tener entre 8 y 13 dígitos');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Solo permitir números
        barcodeInput.addEventListener('keypress', function(e) {
            if (!/\d/.test(e.key) && !['Backspace', 'Delete', 'Tab', 'Enter'].includes(e.key)) {
                e.preventDefault();
            }
        });
    }
    
    // Validación del formulario antes de enviar
    const productForm = document.getElementById('productForm');
    if (productForm) {
        productForm.addEventListener('submit', function(e) {
            const purchasePrice = parseFloat(purchasePriceInput.value) || 0;
            const salePrice = parseFloat(salePriceInput.value) || 0;
            
            if (salePrice <= purchasePrice) {
                e.preventDefault();
                alert('El precio de venta debe ser mayor al precio de compra');
                salePriceInput.focus();
                return false;
            }
        });
    }
}

/**
 * Determinar clase CSS según el margen
 */
function getMarginClass(margin) {
    if (margin >= 50) return 'margin-positive';
    if (margin >= 20) return 'margin-positive';
    if (margin >= 10) return 'margin-warning';
    return 'margin-negative';
}

/**
 * Inicializar modal de ajuste de stock
 */
function initializeStockModal() {
    const modal = document.getElementById('stockModal');
    const stockButtons = document.querySelectorAll('.btn-stock-adjust');
    const closeButtons = document.querySelectorAll('.modal-close');
    const stockForm = document.getElementById('stockForm');
    
    // Abrir modal
    stockButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.id;
            const productName = this.dataset.name;
            
            document.getElementById('stockProductId').value = productId;
            document.getElementById('stockProductName').textContent = productName;
            document.getElementById('adjustment').value = '';
            document.getElementById('reason').value = '';
            
            modal.style.display = 'block';
            document.getElementById('adjustment').focus();
        });
    });
    
    // Cerrar modal
    closeButtons.forEach(button => {
        button.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    });
    
    // Cerrar modal al hacer clic fuera
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
    
    // Cerrar con ESC
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'block') {
            modal.style.display = 'none';
        }
    });
    
    // Validación del formulario de stock
    if (stockForm) {
        stockForm.addEventListener('submit', function(e) {
            const adjustment = parseInt(document.getElementById('adjustment').value);
            const reason = document.getElementById('reason').value;
            
            if (adjustment === 0) {
                e.preventDefault();
                alert('El ajuste no puede ser cero');
                return false;
            }
            
            if (!reason) {
                e.preventDefault();
                alert('Debe especificar la razón del ajuste');
                return false;
            }
            
            // Confirmación para ajustes grandes
            if (Math.abs(adjustment) >= 100) {
                if (!confirm(`¿Está seguro de ${adjustment > 0 ? 'aumentar' : 'reducir'} ${Math.abs(adjustment)} unidades?`)) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    }
}

/**
 * Inicializar escáner de código de barras (simulado)
 */
function initializeBarcodeScanner() {
    // Simular escáner con entrada rápida de teclado
    let barcodeBuffer = '';
    let lastKeypressTime = 0;
    
    document.addEventListener('keypress', function(e) {
        const currentTime = new Date().getTime();
        
        // Si hay más de 100ms entre teclas, reiniciar buffer
        if (currentTime - lastKeypressTime > 100) {
            barcodeBuffer = '';
        }
        
        lastKeypressTime = currentTime;
        
        // Solo procesar si el foco no está en un input
        if (document.activeElement.tagName !== 'INPUT' && document.activeElement.tagName !== 'TEXTAREA') {
            barcodeBuffer += e.key;
            
            // Si buffer tiene longitud de código de barras
            if (barcodeBuffer.length >= 8 && /^\d+$/.test(barcodeBuffer)) {
                processBarcodeScanned(barcodeBuffer);
                barcodeBuffer = '';
            }
        }
    });
}

/**
 * Procesar código de barras escaneado
 */
function processBarcodeScanned(barcode) {
    // Buscar el producto en la tabla
    const rows = document.querySelectorAll('.products-table tbody tr');
    let productFound = false;
    
    rows.forEach(row => {
        const barcodeCell = row.querySelector('.barcode');
        if (barcodeCell && barcodeCell.textContent.trim() === barcode) {
            // Resaltar producto encontrado
            row.style.backgroundColor = '#fff3cd';
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            setTimeout(() => {
                row.style.backgroundColor = '';
            }, 2000);
            
            productFound = true;
        }
    });
    
    if (!productFound) {
        // Si no se encuentra, llenar el campo de código de barras en el formulario
        const barcodeInput = document.getElementById('barcode');
        if (barcodeInput) {
            barcodeInput.value = barcode;
            barcodeInput.focus();
            showNotification('Producto no encontrado. Código cargado en formulario.', 'warning');
        }
    } else {
        showNotification('Producto encontrado', 'success');
    }
}

/**
 * Inicializar ordenamiento de tabla
 */
function initializeTableSorting() {
    const table = document.querySelector('.products-table');
    if (!table) return;
    
    const headers = table.querySelectorAll('th');
    
    headers.forEach((header, index) => {
        if (index < headers.length - 1) { // No hacer clickeable la columna de acciones
            header.style.cursor = 'pointer';
            header.title = 'Hacer clic para ordenar';
            
            header.addEventListener('click', function() {
                sortTable(table, index);
            });
        }
    });
}

/**
 * Ordenar tabla por columna
 */
function sortTable(table, columnIndex) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const isNumeric = columnIndex >= 3 && columnIndex <= 6; // Precios, margen, stock
    
    // Determinar dirección de ordenamiento
    const currentSort = table.dataset.sortColumn;
    const currentDirection = table.dataset.sortDirection || 'asc';
    const newDirection = (currentSort == columnIndex && currentDirection === 'asc') ? 'desc' : 'asc';
    
    // Ordenar filas
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        let comparison = 0;
        
        if (isNumeric) {
            const aNum = parseFloat(aValue.replace(/[$,%]/g, '')) || 0;
            const bNum = parseFloat(bValue.replace(/[$,%]/g, '')) || 0;
            comparison = aNum - bNum;
        } else {
            comparison = aValue.localeCompare(bValue);
        }
        
        return newDirection === 'asc' ? comparison : -comparison;
    });
    
    // Actualizar tabla
    rows.forEach(row => tbody.appendChild(row));
    
    // Actualizar indicadores de ordenamiento
    table.dataset.sortColumn = columnIndex;
    table.dataset.sortDirection = newDirection;
    
    // Actualizar headers
    table.querySelectorAll('th').forEach((th, index) => {
        th.classList.remove('sort-asc', 'sort-desc');
        if (index === columnIndex) {
            th.classList.add(`sort-${newDirection}`);
        }
    });
}

/**
 * Cálculo automático de margen al cargar
 */
function autoCalculateMargin() {
    // Calcular márgenes en la tabla si no están calculados
    const rows = document.querySelectorAll('.products-table tbody tr');
    
    rows.forEach(row => {
        const purchaseCell = row.querySelector('td:nth-child(4)');
        const saleCell = row.querySelector('td:nth-child(5)');
        const marginCell = row.querySelector('td:nth-child(6)');
        
        if (purchaseCell && saleCell && marginCell) {
            const purchasePrice = parseFloat(purchaseCell.textContent.replace(/[$,]/g, ''));
            const salePrice = parseFloat(saleCell.textContent.replace(/[$,]/g, ''));
            
            if (purchasePrice > 0 && salePrice > 0) {
                const margin = ((salePrice - purchasePrice) / purchasePrice) * 100;
                marginCell.className = `margin ${getMarginClass(margin)}`;
            }
        }
    });
}

/**
 * Mostrar notificación
 */
function showNotification(message, type = 'info') {
    // Crear elemento de notificación
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <span>${message}</span>
        <button onclick="this.parentElement.remove()">&times;</button>
    `;
    
    // Estilos inline para la notificación
    notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 15px 20px;
        border-radius: 8px;
        color: white;
        font-weight: 600;
        z-index: 1001;
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideInRight 0.3s ease-out;
        max-width: 300px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.15);
    `;
    
    // Colores según tipo
    const colors = {
        success: '#28a745',
        warning: '#ffc107',
        error: '#dc3545',
        info: '#17a2b8'
    };
    
    notification.style.backgroundColor = colors[type] || colors.info;
    
    // Agregar al DOM
    document.body.appendChild(notification);
    
    // Auto-remover después de 4 segundos
    setTimeout(() => {
        if (notification.parentElement) {
            notification.style.animation = 'slideOutRight 0.3s ease-out';
            setTimeout(() => notification.remove(), 300);
        }
    }, 4000);
}

/**
 * Funciones utilitarias
 */

// Formatear precio
function formatPrice(price) {
    return new Intl.NumberFormat('es-MX', {
        style: 'currency',
        currency: 'MXN'
    }).format(price);
}

// Validar código de barras
function isValidBarcode(barcode) {
    return /^\d{8,13}$/.test(barcode);
}

// Calcular margen de ganancia
function calculateProfitMargin(salePrice, purchasePrice) {
    if (purchasePrice <= 0) return 0;
    return ((salePrice - purchasePrice) / purchasePrice) * 100;
}

// Auto-limpiar alertas después de 5 segundos
setTimeout(() => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.opacity = '0';
        setTimeout(() => {
            if (alert.parentElement) {
                alert.remove();
            }
        }, 500);
    });
}, 5000);

// CSS para animaciones de notificaciones
const notificationStyles = document.createElement('style');
notificationStyles.textContent = `
    @keyframes slideInRight {
        from {
            opacity: 0;
            transform: translateX(100%);
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
            transform: translateX(100%);
        }
    }
    
    .notification button {
        background: none;
        border: none;
        color: white;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 0;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .sort-asc::after {
        content: " ↑";
        color: #007bff;
    }
    
    .sort-desc::after {
        content: " ↓";
        color: #007bff;
    }
`;
document.head.appendChild(notificationStyles);