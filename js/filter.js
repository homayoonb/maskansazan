function filterUsers() {
    const searchTerm = document.getElementById('searchUsers').value.toLowerCase();
    const filterColumn = document.getElementById('filterColumn').value; // انتخاب ستون
    const rows = document.querySelectorAll('#usersTable tbody tr');
    
    rows.forEach(row => {
        let cellText = '';
        
        if (filterColumn === 'all') {
            // جستجو در همه ستون‌ها
            const cells = row.querySelectorAll('td');
            cells.forEach(cell => {
                cellText += cell.textContent.toLowerCase() + ' ';
            });
        } else {
            // جستجو در ستون خاص
            const cellIndex = parseInt(filterColumn);
            const cell = row.querySelector(`td:nth-child(${cellIndex + 1})`);
            if (cell) {
                cellText = cell.textContent.toLowerCase();
            }
        }
        
        if (searchTerm === '' || cellText.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
}
// در فایل filter.js - اضافه کردن این تابع
function searchUsers() {
    console.log('searchUsers called - redirecting to filterUsers');
    filterUsers(); // فراخوانی همان تابع اصلی
}

// یا تعریف مستقل
function searchUsers() {
    const searchInput = document.getElementById('searchUsers') || 
                       document.getElementById('searchInput') ||
                       document.querySelector('input[type="search"]');
    
    if (!searchInput) {
        console.error('فیلد جستجو پیدا نشد');
        return;
    }
    
    const searchTerm = searchInput.value.toLowerCase();
    const table = document.querySelector('table');
    
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
}

// در فایل جدید members.js یا در انتهای صفحه
function sortMembers(column, type = 'string') {
    console.log(`مرتب‌سازی بر اساس ستون: ${column}, نوع: ${type}`);
    
    const table = document.getElementById('membersTable') || 
                  document.querySelector('table');
    
    if (!table) {
        console.error('❌ جدول اعضا پیدا نشد');
        return;
    }
    
    const tbody = table.querySelector('tbody');
    if (!tbody) {
        console.error('❌ بدنه جدول پیدا نشد');
        return;
    }
    
    const rows = Array.from(tbody.querySelectorAll('tr'));
    if (rows.length === 0) {
        console.warn('⚠️ هیچ داده‌ای برای مرتب‌سازی وجود ندارد');
        return;
    }
    
    // دریافت جهت مرتب‌سازی فعلی
    const currentHeader = table.querySelector(`th[data-column="${column}"]`);
    let direction = 'asc';
    
    if (currentHeader) {
        direction = currentHeader.getAttribute('data-sort') === 'asc' ? 'desc' : 'asc';
        currentHeader.setAttribute('data-sort', direction);
        
        // حذف نشانگر از سایر هدرها
        table.querySelectorAll('th').forEach(th => {
            if (th !== currentHeader) {
                th.removeAttribute('data-sort');
                th.classList.remove('sort-asc', 'sort-desc');
            }
        });
        
        // اضافه کردن کلاس جهت
        currentHeader.classList.remove('sort-asc', 'sort-desc');
        currentHeader.classList.add(`sort-${direction}`);
    }
    
    // مرتب‌سازی ردیف‌ها
    rows.sort((a, b) => {
        const aCell = a.querySelector(`td:nth-child(${getColumnIndex(table, column)})`);
        const bCell = b.querySelector(`td:nth-child(${getColumnIndex(table, column)})`);
        
        let aValue = aCell ? aCell.textContent.trim() : '';
        let bValue = bCell ? bCell.textContent.trim() : '';
        
        // تبدیل به نوع مناسب
        if (type === 'number' || type === 'int') {
            aValue = parseFloat(aValue) || 0;
            bValue = parseFloat(bValue) || 0;
        } else if (type === 'date') {
            aValue = parseDate(aValue);
            bValue = parseDate(bValue);
        }
        
        // مقایسه
        if (direction === 'asc') {
            return aValue > bValue ? 1 : -1;
        } else {
            return aValue < bValue ? 1 : -1;
        }
    });
    
    // حذف ردیف‌های قدیمی و اضافه کردن مرتب شده
    rows.forEach(row => tbody.removeChild(row));
    rows.forEach(row => tbody.appendChild(row));
    
    console.log(`✅ جدول بر اساس ستون "${column}" مرتب‌سازی شد (${direction})`);
}

// تابع کمکی برای پیدا کردن index ستون
function getColumnIndex(table, column) {
    const headers = table.querySelectorAll('th');
    for (let i = 0; i < headers.length; i++) {
        if (headers[i].getAttribute('data-column') === column || 
            headers[i].textContent.toLowerCase().includes(column.toLowerCase())) {
            return i + 1; // +1 چون nth-child از 1 شروع می‌شود
        }
    }
    return 1; // پیش‌فرض ستون اول
}

// تابع کمکی برای پارس تاریخ
function parseDate(dateString) {
    // تاریخ شمسی
    if (dateString.includes('/')) {
        const parts = dateString.split('/');
        if (parts.length === 3) {
            return new Date(parseInt(parts[0]) + 621, parseInt(parts[1]) - 1, parseInt(parts[2]));
        }
    }
    
    // تاریخ میلادی
    const date = new Date(dateString);
    return isNaN(date.getTime()) ? new Date(0) : date;
}

// تابع مرتب‌سازی سریع برای جدول‌های خاص
function quickSortTable(tableId, columnIndex) {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    const direction = table.getAttribute('data-sort-dir') === 'asc' ? 'desc' : 'asc';
    table.setAttribute('data-sort-dir', direction);
    
    rows.sort((a, b) => {
        const aVal = a.cells[columnIndex].textContent.trim();
        const bVal = b.cells[columnIndex].textContent.trim();
        
        if (direction === 'asc') {
            return aVal.localeCompare(bVal, 'fa');
        } else {
            return bVal.localeCompare(aVal, 'fa');
        }
    });
    
    rows.forEach(row => tbody.appendChild(row));
}

/**
 * فیلتر کاربران بر اساس وضعیت
 * @param {string} status - وضعیت مورد نظر: 'all', 'active', 'inactive', 'pending', 'blocked'
 */
function filterByStatus(status) {
    console.log(`🎯 فیلتر بر اساس وضعیت: ${status}`);
    
    // پیدا کردن جدول کاربران
    const table = document.getElementById('usersTable') || 
                  document.getElementById('membersTable') || 
                  document.querySelector('table');
    
    if (!table) {
        console.error('❌ جدول پیدا نشد');
        showMessage('جدولی برای فیلتر کردن پیدا نشد!', 'error');
        return;
    }
    
    const tbody = table.querySelector('tbody');
    if (!tbody) {
        console.error('❌ بدنه جدول (tbody) پیدا نشد');
        return;
    }
    
    const rows = tbody.querySelectorAll('tr');
    if (rows.length === 0) {
        console.warn('⚠️ هیچ کاربری برای فیلتر کردن وجود ندارد');
        showMessage('هیچ کاربری برای فیلتر کردن وجود ندارد', 'warning');
        return;
    }
    
    console.log(`📊 تعداد کل کاربران: ${rows.length}`);
    
    let visibleCount = 0;
    let hiddenCount = 0;
    
    // فیلتر کردن ردیف‌ها
    rows.forEach(row => {
        // پیدا کردن سلول وضعیت
        let statusCell = row.querySelector('.status-cell, [data-status]');
        
        // اگر سلول وضعیت خاصی نداشت، آخرین سلول badge را جستجو کن
        if (!statusCell) {
            statusCell = row.querySelector('.badge, .status-badge');
        }
        
        // اگر هنوز پیدا نشد، سلول‌های ردیف را بررسی کن
        if (!statusCell) {
            const cells = row.querySelectorAll('td');
            statusCell = cells[cells.length - 1]; // آخرین سلول
        }
        
        const rowStatus = extractStatusFromCell(statusCell);
        const shouldShow = shouldDisplayRow(rowStatus, status);
        
        if (shouldShow) {
            row.style.display = '';
            row.classList.remove('filtered-out');
            visibleCount++;
        } else {
            row.style.display = 'none';
            row.classList.add('filtered-out');
            hiddenCount++;
        }
    });
    
    // به‌روزرسانی شمارنده
    updateFilterCounter(status, visibleCount, rows.length);
    
    // ذخیره فیلتر
    saveCurrentFilter(status);
    
    // به‌روزرسانی دکمه‌های فیلتر
    updateFilterButtons(status);
    
    console.log(`✅ فیلتر اعمال شد: ${visibleCount} کاربر نمایش داده می‌شود، ${hiddenCount} کاربر مخفی شد`);
    showMessage(`فیلتر "${getStatusLabel(status)}" اعمال شد (${visibleCount} مورد)`, 'success');
}

/**
 * استخراج وضعیت از سلول
 */
function extractStatusFromCell(cell) {
    if (!cell) return '';
    
    // اگر داده‌ای در data-status ذخیره شده
    if (cell.dataset.status) {
        return cell.dataset.status.toLowerCase();
    }
    
    // بررسی متن سلول
    const text = cell.textContent.toLowerCase().trim();
    
    // تشخیص وضعیت از متن
    if (text.includes('فعال') || text.includes('active') || 
        text.includes('✓') || text.includes('success') || 
        cell.classList.contains('bg-success') || 
        cell.classList.contains('text-success')) {
        return 'active';
    }
    
    if (text.includes('غیرفعال') || text.includes('inactive') || 
        text.includes('✗') || text.includes('secondary') || 
        cell.classList.contains('bg-secondary') || 
        cell.classList.contains('text-secondary')) {
        return 'inactive';
    }
    
    if (text.includes('معلق') || text.includes('pending') || 
        text.includes('در انتظار') || text.includes('warning') || 
        cell.classList.contains('bg-warning') || 
        cell.classList.contains('text-warning')) {
        return 'pending';
    }
    
    if (text.includes('مسدود') || text.includes('blocked') || 
        text.includes('رد شده') || text.includes('danger') || 
        cell.classList.contains('bg-danger') || 
        cell.classList.contains('text-danger')) {
        return 'blocked';
    }
    
    // اگر هیچکدام، متن اصلی را برگردان
    return text;
}

/**
 * تصمیم‌گیری برای نمایش ردیف
 */
function shouldDisplayRow(rowStatus, filterStatus) {
    if (filterStatus === 'all') return true;
    
    const statusMap = {
        'active': ['فعال', 'active', 'success', 'بررسی شده', 'تایید شده'],
        'inactive': ['غیرفعال', 'inactive', 'secondary', 'رد شده', 'حذف شده'],
        'pending': ['معلق', 'pending', 'warning', 'در انتظار', 'در حال بررسی', 'تایید نشده'],
        'blocked': ['مسدود', 'blocked', 'danger', 'بن شده', 'تعلیق شده']
    };
    
    // اگر وضعیت دقیق مطابقت دارد
    if (rowStatus === filterStatus) return true;
    
    // بررسی وضعیت‌های معادل
    const equivalentStatuses = statusMap[filterStatus] || [];
    for (const status of equivalentStatuses) {
        if (rowStatus.includes(status.toLowerCase())) {
            return true;
        }
    }
    
    // بررسی مستقیم متن
    const filterText = getStatusLabel(filterStatus).toLowerCase();
    return rowStatus.includes(filterText);
}

/**
 * دریافت label وضعیت
 */
function getStatusLabel(status) {
    const labels = {
        'all': 'همه',
        'active': 'فعال',
        'inactive': 'غیرفعال',
        'pending': 'در انتظار',
        'blocked': 'مسدود'
    };
    return labels[status] || status;
}

/**
 * به‌روزرسانی شمارنده فیلتر
 */
function updateFilterCounter(status, visible, total) {
    // حذف شمارنده قبلی
    const oldCounter = document.getElementById('filterCounter');
    if (oldCounter) oldCounter.remove();
    
    // اگر همه را نشان می‌دهیم، نیازی به شمارنده نیست
    if (status === 'all' || visible === total) return;
    
    // ایجاد شمارنده جدید
    const counter = document.createElement('div');
    counter.id = 'filterCounter';
    counter.className = 'alert alert-info alert-dismissible fade show mb-3 p-2';
    counter.innerHTML = `
        <button type="button" class="btn-close btn-sm" onclick="clearStatusFilter()"></button>
        <i class="bi bi-funnel me-1"></i>
        <span class="fw-bold">${getStatusLabel(status)}:</span>
        نمایش <strong>${visible}</strong> از <strong>${total}</strong> کاربر
        <button class="btn btn-link btn-sm p-0 ms-2" onclick="clearStatusFilter()">
            <i class="bi bi-x-circle"></i> حذف فیلتر
        </button>
    `;
    
    // پیدا کردن محل مناسب برای قرار دادن
    const container = document.querySelector('.card-body') || 
                     document.querySelector('.table-responsive') || 
                     document.querySelector('table').parentElement;
    
    if (container) {
        // اگر پیام دیگری وجود دارد، بعد از آن قرار بده
        const existingAlert = container.querySelector('.alert');
        if (existingAlert) {
            existingAlert.insertAdjacentElement('afterend', counter);
        } else {
            container.insertBefore(counter, container.firstChild);
        }
    }
}

/**
 * به‌روزرسانی وضعیت دکمه‌های فیلتر
 */
function updateFilterButtons(selectedStatus) {
    const buttons = document.querySelectorAll('[data-filter-status], .filter-btn');
    
    buttons.forEach(button => {
        const buttonStatus = button.dataset.filterStatus || 
                           button.getAttribute('onclick')?.match(/filterByStatus\('(.+?)'\)/)?.[1];
        
        if (buttonStatus === selectedStatus) {
            // فعال کردن دکمه انتخاب شده
            button.classList.remove('btn-outline-secondary');
            button.classList.add('btn-primary');
            button.classList.add('active');
        } else {
            // غیرفعال کردن سایر دکمه‌ها
            button.classList.remove('btn-primary', 'active');
            button.classList.add('btn-outline-secondary');
        }
    });
}

/**
 * ذخیره فیلتر فعلی
 */
function saveCurrentFilter(status) {
    try {
        localStorage.setItem('lastStatusFilter', status);
        sessionStorage.setItem('currentFilter', status);
    } catch (e) {
        console.warn('ذخیره فیلتر ممکن نشد:', e);
    }
}

/**
 * بارگذاری فیلتر ذخیره شده
 */
function loadSavedFilter() {
    try {
        const savedFilter = localStorage.getItem('lastStatusFilter') || 'all';
        if (savedFilter !== 'all') {
            // کمی تاخیر برای اطمینان از لود کامل DOM
            setTimeout(() => {
                filterByStatus(savedFilter);
            }, 300);
        }
    } catch (e) {
        console.warn('بارگذاری فیلتر ذخیره شده ممکن نشد:', e);
    }
}

/**
 * حذف فیلتر (نمایش همه)
 */
function clearStatusFilter() {
    filterByStatus('all');
    
    // حذف شمارنده
    const counter = document.getElementById('filterCounter');
    if (counter) counter.remove();
    
    // پاک کردن فیلتر ذخیره شده
    try {
        localStorage.removeItem('lastStatusFilter');
        sessionStorage.removeItem('currentFilter');
    } catch (e) {
        // ignore
    }
    
    showMessage('فیلتر حذف شد. همه کاربران نمایش داده می‌شوند.', 'info');
}

/**
 * نمایش پیغام
 */
function showMessage(message, type = 'info') {
    // حذف پیغام قبلی
    const oldMessage = document.getElementById('filterMessage');
    if (oldMessage) oldMessage.remove();
    
    const alertClass = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    }[type] || 'alert-info';
    
    const messageDiv = document.createElement('div');
    messageDiv.id = 'filterMessage';
    messageDiv.className = `alert ${alertClass} alert-dismissible fade show position-fixed`;
    messageDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    `;
    messageDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;
    
    document.body.appendChild(messageDiv);
    
    // حذف خودکار بعد از 4 ثانیه
    setTimeout(() => {
        if (messageDiv.parentNode) {
            messageDiv.remove();
        }
    }, 4000);
}

/**
 * راه‌اندازی اولیه
 */
function initStatusFilter() {
    console.log('🚀 سیستم فیلتر وضعیت راه‌اندازی شد');
    
    // بارگذاری فیلتر ذخیره شده
    loadSavedFilter();
    
    // اضافه کردن event listener به دکمه‌ها
    document.querySelectorAll('[onclick*="filterByStatus"]').forEach(button => {
        const onclick = button.getAttribute('onclick');
        const match = onclick.match(/filterByStatus\('(.+?)'\)/);
        if (match) {
            button.dataset.filterStatus = match[1];
        }
    });
}

// تعریف توابع global
window.filterByStatus = filterByStatus;
window.clearStatusFilter = clearStatusFilter;

// راه‌اندازی وقتی DOM آماده شد
document.addEventListener('DOMContentLoaded', initStatusFilter);
/**
 * مرتب‌سازی جدول کاربران با ارسال درخواست به سرور
 * @param {string} field - نام فیلد برای مرتب‌سازی
 */
function sortUserTable(field) {
    console.log(`🔄 درخواست مرتب‌سازی: ${field}`);
    
    // دریافت پارامترهای فعلی از URL
    const urlParams = new URLSearchParams(window.location.search);
    
    // بررسی فیلد مرتب‌سازی فعلی
    const currentSort = urlParams.get('user_sort') || 'id';
    const currentOrder = urlParams.get('user_order') || 'DESC';
    
    // تعیین جهت مرتب‌سازی جدید
    let newOrder = 'ASC';
    if (currentSort === field) {
        newOrder = currentOrder === 'ASC' ? 'DESC' : 'ASC';
    }
    
    // به‌روزرسانی پارامترها
    urlParams.set('user_sort', field);
    urlParams.set('user_order', newOrder);
    
    // رفتن به URL جدید
    const newUrl = `dashboardAdmin.php?${urlParams.toString()}`;
    window.location.href = newUrl;
}

/**
 * مرتب‌سازی کلاینت ساید (فقط برای نمایش موقت)
 */
function clientSideSort(field, order = 'asc') {
    console.log(`🧪 مرتب‌سازی موقت کلاینت: ${field} (${order})`);
    
    const table = document.getElementById('usersTable');
    if (!table) {
        console.error('جدول پیدا نشد');
        return;
    }
    
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    
    if (rows.length < 2) {
        console.log('تعداد ردیف‌ها کمتر از ۲ است');
        return;
    }
    
    // مرتب‌سازی
    rows.sort((a, b) => {
        let valueA, valueB;
        
        // گرفتن مقادیر بر اساس فیلد
        switch(field) {
            case 'id':
                valueA = parseInt(a.dataset.id) || 0;
                valueB = parseInt(b.dataset.id) || 0;
                break;
                
            case 'display_name':
                valueA = a.dataset.name || '';
                valueB = b.dataset.name || '';
                break;
                
            case 'national_code':
                valueA = a.dataset.nationalCode || '';
                valueB = b.dataset.nationalCode || '';
                break;
                
            case 'phone':
                valueA = a.dataset.phone || '';
                valueB = b.dataset.phone || '';
                break;
                
            case 'main_member':
                valueA = a.dataset.memberType || '';
                valueB = b.dataset.memberType || '';
                // مرتب‌سازی: اصلی اول، سپس عادی
                const priority = { 'اصلی': 1, 'main': 1, 'عادی': 2, 'normal': 2 };
                valueA = priority[valueA] || 3;
                valueB = priority[valueB] || 3;
                break;
                
            case 'document_count':
                valueA = parseInt(a.dataset.docCount) || 0;
                valueB = parseInt(b.dataset.docCount) || 0;
                break;
                
            case 'last_document_date':
                const dateA = a.dataset.lastDoc ? new Date(a.dataset.lastDoc).getTime() : 0;
                const dateB = b.dataset.lastDoc ? new Date(b.dataset.lastDoc).getTime() : 0;
                valueA = dateA;
                valueB = dateB;
                break;
                
            default:
                return 0;
        }
        
        // مقایسه
        if (order === 'asc') {
            if (typeof valueA === 'string') {
                return valueA.localeCompare(valueB, 'fa-IR');
            }
            return valueA > valueB ? 1 : valueA < valueB ? -1 : 0;
        } else {
            if (typeof valueA === 'string') {
                return valueB.localeCompare(valueA, 'fa-IR');
            }
            return valueA < valueB ? 1 : valueA > valueB ? -1 : 0;
        }
    });
    
    // اعمال مرتب‌سازی
    tbody.innerHTML = '';
    rows.forEach(row => tbody.appendChild(row));
    
    // به‌روزرسانی هدرها
    updateSortHeaders(field, order);
    
    // نمایش پیغام
    showSortMessage(field, order);
}

/**
 * به‌روزرسانی هدرهای مرتب‌سازی
 */
function updateSortHeaders(field, order) {
    const table = document.getElementById('usersTable');
    if (!table) return;
    
    const headers = table.querySelectorAll('th');
    
    // حذف کلاس‌های مرتب‌سازی از همه هدرها
    headers.forEach(header => {
        header.classList.remove('sorted', 'asc', 'desc');
        const icon = header.querySelector('.sort-icon');
        if (icon) icon.remove();
    });
    
    // پیدا کردن هدر مربوطه
    const targetHeader = Array.from(headers).find(header => 
        header.dataset.sortField === field
    );
    
    if (targetHeader) {
        // اضافه کردن کلاس
        targetHeader.classList.add('sorted', order);
        
        // اضافه کردن آیکون
        const icon = document.createElement('span');
        icon.className = 'sort-icon';
        icon.textContent = order === 'asc' ? '↑' : '↓';
        targetHeader.appendChild(icon);
    }
}

/**
 * نمایش پیغام مرتب‌سازی
 */
function showSortMessage(field, order) {
    const fieldNames = {
        'id': 'ID',
        'display_name': 'نام کاربر',
        'national_code': 'کد ملی',
        'phone': 'تلفن',
        'main_member': 'نوع عضویت',
        'document_count': 'تعداد مدارک',
        'last_document_date': 'آخرین مدرک'
    };
    
    const orderNames = {
        'asc': 'صعودی',
        'desc': 'نزولی'
    };
    
   
}

/**
 * راه‌اندازی سیستم مرتب‌سازی
 */
function initSorting() {
    console.log('🚀 سیستم مرتب‌سازی راه‌اندازی شد');
    
    // اضافه کردن event listener به هدرها
    const table = document.getElementById('usersTable');
    if (!table) return;
    
    const headers = table.querySelectorAll('th[data-sort-field]');
    
    headers.forEach(header => {
        const field = header.dataset.sortField;
        
        // حذف کلیک‌های قبلی
        header.onclick = null;
        
        // اضافه کردن event listener جدید
        header.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // گزینه ۱: مرتب‌سازی سرورساید (رفرش صفحه)
            // sortUserTable(field);
            
            // گزینه ۲: مرتب‌سازی کلاینت ساید (بدون رفرش)
            const currentClass = Array.from(this.classList).find(cls => 
                cls === 'asc' || cls === 'desc'
            );
            const newOrder = currentClass === 'asc' ? 'desc' : 'asc';
            
            clientSideSort(field, newOrder);
        });
    });
    
    // تعریف توابع global
    window.sortUserTable = sortUserTable;
    window.clientSideSort = clientSideSort;
    
    console.log(`✅ ${headers.length} ستون قابل مرتب‌سازی فعال شد`);
}

// اجرا وقتی DOM لود شد
document.addEventListener('DOMContentLoaded', initSorting);
/**
 * تغییر تعداد آیتم‌های نمایش داده شده در هر صفحه
 * @param {HTMLSelectElement} selectElement - المنت select
 */
function changeUserItemsPerPage(selectElement) {
    const itemsPerPage = selectElement.value;
    console.log(`📊 تغییر تعداد نمایش به: ${itemsPerPage} آیتم در صفحه`);
    
    // دریافت پارامترهای فعلی از URL
    const urlParams = new URLSearchParams(window.location.search);
    
    // تنظیم تعداد آیتم در صفحه
    urlParams.set('user_per_page', itemsPerPage);
    
    // بازگشت به صفحه اول
    urlParams.delete('user_page');
    urlParams.set('user_page', 1);
    
    // رفتن به URL جدید
    const newUrl = `dashboardAdmin.php?${urlParams.toString()}`;
    window.location.href = newUrl;
}

/**
 * تغییر تعداد آیتم‌های نمایش (نسخه با پارامتر)
 * @param {number|string} itemsPerPage - تعداد آیتم‌ها
 */
function setUserItemsPerPage(itemsPerPage) {
    console.log(`📊 تنظیم تعداد نمایش: ${itemsPerPage} آیتم`);
    
    const urlParams = new URLSearchParams(window.location.search);
    
    // تنظیم پارامترها
    urlParams.set('user_per_page', itemsPerPage);
    urlParams.set('user_page', 1); // همیشه به صفحه اول برو
    
    // رفتن به URL جدید
    window.location.href = `dashboardAdmin.php?${urlParams.toString()}`;
}

/**
 * ریست کردن فیلترها و مرتب‌سازی
 */
function resetUserFilters() {
    console.log('🔄 ریست کردن فیلترهای کاربران');
    
    const urlParams = new URLSearchParams(window.location.search);
    
    // حذف پارامترهای مرتب‌سازی و فیلتر
    urlParams.delete('user_sort');
    urlParams.delete('user_order');
    urlParams.delete('search');
    urlParams.delete('user_main');
    urlParams.delete('user_page');
    urlParams.set('user_per_page', 20); // پیش‌فرض
    
    // رفتن به صفحه اول
    window.location.href = `dashboardAdmin.php?${urlParams.toString()}`;
}
// filter.js - نسخه اصلاح شده
function filterUsers() {
    console.log('🎯 اجرای فیلتر کاربران...');
    
    const searchInput = document.getElementById('userSearch');
    if (!searchInput) {
        console.error('❌ فیلد جستجو پیدا نشد');
        alert('لطفا صفحه را رفرش کنید');
        return;
    }
    
    const searchTerm = searchInput.value.toLowerCase().trim();
    console.log('عبارت جستجو:', searchTerm || '(خالی)');
    
    // پیدا کردن جدول
    const table = document.getElementById('usersTable');
    if (!table) {
        console.error('❌ جدول usersTable پیدا نشد');
        return;
    }
    
    const tbody = table.querySelector('tbody');
    if (!tbody) {
        console.error('❌ بدنه جدول پیدا نشد');
        return;
    }
    
    const rows = tbody.querySelectorAll('tr');
    console.log('تعداد ردیف‌ها:', rows.length);
    
    if (rows.length === 0) {
        console.warn('⚠️ هیچ داده‌ای برای فیلتر کردن وجود ندارد');
        return;
    }
    
    let visibleCount = 0;
    
    // روش ساده‌تر: جستجو در کل متن ردیف
    rows.forEach(row => {
        const rowText = row.textContent.toLowerCase();
        
        if (searchTerm === '' || rowText.includes(searchTerm)) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // نمایش نتیجه
    updateFilterResult(searchTerm, visibleCount, rows.length);
    
    console.log(`✅ ${visibleCount} از ${rows.length} ردیف نمایش داده می‌شود`);
}

/**
 * روش پیشرفته‌تر با جستجو در ستون‌های خاص
 */
function filterUsersAdvanced(columnIndex = null) {
    console.log('🔍 جستجوی پیشرفته...');
    
    const searchInput = document.getElementById('userSearch');
    if (!searchInput) return;
    
    const searchTerm = searchInput.value.toLowerCase().trim();
    const table = document.getElementById('usersTable');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        let shouldShow = false;
        
        if (columnIndex === null) {
            // جستجو در همه ستون‌ها
            shouldShow = searchTerm === '' || row.textContent.toLowerCase().includes(searchTerm);
        } else {
            // جستجو در ستون خاص
            const cell = row.cells[columnIndex];
            if (cell) {
                const cellText = cell.textContent.toLowerCase();
                shouldShow = searchTerm === '' || cellText.includes(searchTerm);
            } else {
                shouldShow = true; // اگر سلول وجود ندارد، نمایش بده
            }
        }
        
        if (shouldShow) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    updateFilterResult(searchTerm, visibleCount, rows.length);
}

/**
 * جستجو در ستون خاص
 */
function searchInColumn(columnName) {
    console.log(`🔍 جستجو در ستون: ${columnName}`);
    
    const searchInput = document.getElementById('userSearch');
    if (!searchInput) return;
    
    const searchTerm = searchInput.value.toLowerCase().trim();
    const table = document.getElementById('usersTable');
    if (!table) return;
    
    // پیدا کردن index ستون
    const headers = table.querySelectorAll('th');
    let columnIndex = -1;
    
    headers.forEach((th, index) => {
        if (th.textContent.toLowerCase().includes(columnName.toLowerCase())) {
            columnIndex = index;
        }
    });
    
    if (columnIndex === -1) {
        console.error(`❌ ستون "${columnName}" پیدا نشد`);
        return;
    }
    
    console.log(`📊 جستجو در ستون ${columnIndex} (${columnName})`);
    filterUsersAdvanced(columnIndex);
}

/**
 * به‌روزرسانی نتیجه فیلتر
 */
function updateFilterResult(searchTerm, visible, total) {
    // حذپ نتیجه قبلی
    const oldResult = document.getElementById('filterResult');
    if (oldResult) oldResult.remove();
    
    // اگر جستجوی خالی یا همه موارد نمایش داده می‌شوند
    if (searchTerm === '' || visible === total) return;
    
    // ایجاد نتیجه جدید
    const resultDiv = document.createElement('div');
    resultDiv.id = 'filterResult';
    resultDiv.className = 'alert alert-info alert-dismissible fade show mt-3';
    resultDiv.innerHTML = `
        <button type="button" class="btn-close" onclick="clearSearch()"></button>
        <i class="bi bi-search me-1"></i>
        <strong>${visible}</strong> از <strong>${total}</strong> مورد برای "${searchTerm}" یافت شد.
        <button class="btn btn-link btn-sm p-0 ms-2" onclick="clearSearch()">
            نمایش همه
        </button>
    `;
    
    // قرار دادن نتیجه قبل از جدول
    const table = document.getElementById('usersTable');
    if (table) {
        table.parentNode.insertBefore(resultDiv, table);
    }
}

/**
 * پاک کردن جستجو
 */
function clearSearch() {
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
        searchInput.value = '';
        filterUsers();
    }
    
    // حذف نتیجه
    const resultDiv = document.getElementById('filterResult');
    if (resultDiv) resultDiv.remove();
}

/**
 * فیلتر بر اساس تعداد مدارک
 */
function filterByDocumentCount(minCount = 0) {
    console.log(`📊 فیلتر مدارک: حداقل ${minCount} مدرک`);
    
    const table = document.getElementById('usersTable');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        // پیدا کردن سلول تعداد مدارک (فرضاً ستون ۵)
        const docCell = row.cells[5]; // ستون ششم (ایندکس ۵)
        let docCount = 0;
        
        if (docCell) {
            const match = docCell.textContent.match(/\d+/);
            if (match) {
                docCount = parseInt(match[0]);
            }
        }
        
        const shouldShow = docCount >= minCount;
        
        if (shouldShow) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // نمایش نتیجه
    showDocumentFilterResult(minCount, visibleCount, rows.length);
}

/**
 * نمایش نتیجه فیلتر مدارک
 */
function showDocumentFilterResult(minCount, visible, total) {
    const oldResult = document.getElementById('docFilterResult');
    if (oldResult) oldResult.remove();
    
    if (visible === total) return;
    
    const resultDiv = document.createElement('div');
    resultDiv.id = 'docFilterResult';
    resultDiv.className = 'alert alert-warning alert-dismissible fade show mt-3';
    resultDiv.innerHTML = `
        <button type="button" class="btn-close" onclick="clearDocumentFilter()"></button>
        <i class="bi bi-file-earmark-text me-1"></i>
        کاربران با حداقل <strong>${minCount}</strong> مدرک:
        <strong>${visible}</strong> از <strong>${total}</strong> مورد
        <button class="btn btn-link btn-sm p-0 ms-2" onclick="clearDocumentFilter()">
            نمایش همه
        </button>
    `;
    
    const table = document.getElementById('usersTable');
    if (table) {
        table.parentNode.insertBefore(resultDiv, table);
    }
}

/**
 * حذف فیلتر مدارک
 */
function clearDocumentFilter() {
    const table = document.getElementById('usersTable');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        row.style.display = '';
    });
    
    const resultDiv = document.getElementById('docFilterResult');
    if (resultDiv) resultDiv.remove();
}

/**
 * فیلتر وضعیت عضویت
 */
function filterByMembership(status) {
    console.log(`👥 فیلتر عضویت: ${status}`);
    
    const table = document.getElementById('usersTable');
    if (!table) return;
    
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    let visibleCount = 0;
    
    rows.forEach(row => {
        // پیدا کردن سلول وضعیت عضویت (فرضاً ستون ۴)
        const statusCell = row.cells[4]; // ستون پنجم (ایندکس ۴)
        let shouldShow = false;
        
        if (statusCell) {
            const statusText = statusCell.textContent.toLowerCase();
            
            if (status === 'all') {
                shouldShow = true;
            } else if (status === 'main') {
                shouldShow = statusText.includes('اصلی') || statusText.includes('main');
            } else if (status === 'member') {
                shouldShow = statusText.includes('عادی') || statusText.includes('member');
            }
        } else {
            shouldShow = true; // اگر سلول وجود ندارد
        }
        
        if (shouldShow) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });
    
    // نمایش نتیجه
    showMembershipFilterResult(status, visibleCount, rows.length);
}

/**
 * نمایش نتیجه فیلتر عضویت
 */
function showMembershipFilterResult(status, visible, total) {
    const oldResult = document.getElementById('membershipFilterResult');
    if (oldResult) oldResult.remove();
    
    if (visible === total) return;
    
    const statusNames = {
        'all': 'همه',
        'main': 'اعضای اصلی',
        'member': 'اعضای عادی'
    };
    
    const resultDiv = document.createElement('div');
    resultDiv.id = 'membershipFilterResult';
    resultDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
    resultDiv.innerHTML = `
        <button type="button" class="btn-close" onclick="clearMembershipFilter()"></button>
        <i class="bi bi-people me-1"></i>
        ${statusNames[status] || status}:
        <strong>${visible}</strong> از <strong>${total}</strong> مورد
        <button class="btn btn-link btn-sm p-0 ms-2" onclick="clearMembershipFilter()">
            نمایش همه
        </button>
    `;
    
    const table = document.getElementById('usersTable');
    if (table) {
        table.parentNode.insertBefore(resultDiv, table);
    }
}

/**
 * حذف فیلتر عضویت
 */
function clearMembershipFilter() {
    const table = document.getElementById('usersTable');
    if (!table) return;
    
    const rows = table.querySelectorAll('tbody tr');
    rows.forEach(row => {
        row.style.display = '';
    });
    
    const resultDiv = document.getElementById('membershipFilterResult');
    if (resultDiv) resultDiv.remove();
}

/**
 * راه‌اندازی سیستم فیلتر
 */
function initFilterSystem() {
    console.log('🚀 سیستم فیلتر راه‌اندازی شد');
    
    // اضافه کردن event listener برای Enter
    const searchInput = document.getElementById('userSearch');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                filterUsers();
            }
        });
    }
    
    // تعریف توابع global
    window.filterUsers = filterUsers;
    window.clearSearch = clearSearch;
    window.filterByDocumentCount = filterByDocumentCount;
    window.clearDocumentFilter = clearDocumentFilter;
    window.filterByMembership = filterByMembership;
    window.clearMembershipFilter = clearMembershipFilter;
    window.searchInColumn = searchInColumn;
    
    console.log('✅ سیستم فیلتر آماده است');
}

// اجرا وقتی DOM لود شد
document.addEventListener('DOMContentLoaded', initFilterSystem);
function showImageModal(imageSrc, title) {
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    const modalImage = document.getElementById('modalImage');
    const modalTitle = document.getElementById('imageModalTitle');
    const downloadLink = document.getElementById('downloadImage');
    const loadingSpinner = document.getElementById('imageLoading');
    
    // تنظیم عنوان
    modalTitle.textContent = title || 'تصویر';
    
    // پنهان کردن تصویر و نمایش اسپینر
    modalImage.classList.add('d-none');
    loadingSpinner.style.display = 'block';
    
    // تنظیم تصویر
    modalImage.onload = function() {
        loadingSpinner.style.display = 'none';
        modalImage.classList.remove('d-none');
        
        // فعال کردن دکمه دانلود
        downloadLink.href = imageSrc;
        downloadLink.classList.remove('disabled');
        downloadLink.download = title + '.jpg';
    };
    
    modalImage.onerror = function() {
        loadingSpinner.style.display = 'none';
        modalImage.alt = 'خطا در بارگذاری تصویر';
    };
    
    modalImage.src = imageSrc;
    
    // نمایش مودال
    modal.show();
}

// توابع عملیات
function updateMemberStatus(memberId, status) {
    if(confirm('آیا از تغییر وضعیت عضو اطمینان دارید؟')) {
        fetch('update_member_status.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: memberId, status: status})
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            } else {
                alert('خطا در بروزرسانی: ' + data.message);
            }
        });
    }
}

function editMember(memberId) {
    window.location.href = 'edit_member.php?id=' + memberId;
}

function viewMemberDetails(memberId) {
    window.location.href = 'member_details.php?id=' + memberId;
}

function clearFilters() {
    window.location.href = 'dashboardAdmin.php?tab=members';
}