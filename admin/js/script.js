/**
 * Filezone Admin Panel - Main JavaScript
 * Бүх хуудасны интерактив үйлдлүүдийг агуулсан файл.
 */

document.addEventListener('DOMContentLoaded', function() {
    
    // ============================================================
    // 1. GLOBAL: SIDEBAR TOGGLE
    // ============================================================
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const sidebar = document.getElementById('sidebar');
    
    if (mobileMenuBtn && sidebar) {
        mobileMenuBtn.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
            sidebar.classList.toggle('absolute');
            sidebar.classList.toggle('inset-y-0');
            sidebar.classList.toggle('left-0');
            sidebar.classList.toggle('shadow-2xl');
        });
    }

    // ============================================================
    // 2. DASHBOARD: CHART.JS INITIALIZATION
    // ============================================================
    const chartCanvas = document.getElementById('trafficChart');
    if (chartCanvas) {
        const ctx = chartCanvas.getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Даваа', 'Мягмар', 'Лхагва', 'Пүрэв', 'Баасан', 'Бямба', 'Ням'],
                datasets: [{
                    label: 'Хандалт',
                    data: [150, 230, 180, 320, 290, 450, 410],
                    borderColor: '#4F46E5',
                    backgroundColor: 'rgba(79, 70, 229, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2, 4] } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    // ============================================================
    // 3. CATEGORIES: AUTO SLUG GENERATOR
    // ============================================================
    const catNameInput = document.getElementById('catName');
    const catSlugInput = document.getElementById('catSlug');
    
    if (catNameInput && catSlugInput) {
        catNameInput.addEventListener('input', function(e) {
            // Зөвхөн "Шинэ ангилал нэмэх" үед автоматаар бөглөнө (ID хоосон үед)
            const catId = document.getElementById('catId').value;
            if(!catId) {
                const slug = e.target.value.toLowerCase().replace(/ /g, '-').replace(/[^\w-]+/g, '');
                catSlugInput.value = slug;
            }
        });
    }
});


// ============================================================
// 4. FILES PAGE LOGIC
// ============================================================
function openEditModal(id, name, category, status) {
    const modal = document.getElementById('editModal');
    if(!modal) return;

    document.getElementById('editFileId').value = id;
    document.getElementById('editFileName').value = name;
    document.getElementById('editFileCategory').value = category;
    document.getElementById('editFileStatus').value = status;
    modal.classList.remove('hidden');
}

function closeEditModal() {
    const modal = document.getElementById('editModal');
    if(modal) modal.classList.add('hidden');
}

function saveFileChanges() {
    const id = document.getElementById('editFileId').value;
    const newName = document.getElementById('editFileName').value;
    const newCategory = document.getElementById('editFileCategory').value;
    const newStatus = document.getElementById('editFileStatus').value;

    const row = document.getElementById('row-' + id);
    if(row) {
        const nameEl = row.querySelector('.file-name');
        if(nameEl) {
            nameEl.textContent = newName;
            nameEl.title = newName;
        }

        const catEl = row.querySelector('.file-category');
        if(catEl) catEl.textContent = newCategory;

        const statusEl = row.querySelector('.file-status');
        if(statusEl) {
            let statusHtml = '';
            if(newStatus === 'active') {
                statusHtml = '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Идэвхтэй</span>';
            } else if(newStatus === 'pending') {
                statusHtml = '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700 border border-yellow-200"><span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> Хүлээгдэж буй</span>';
            } else {
                statusHtml = '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Устгагдсан</span>';
            }
            statusEl.innerHTML = statusHtml;
        }
    }
    alert('Өөрчлөлтийг амжилттай хадгаллаа! (Demo)');
    closeEditModal();
}


// ============================================================
// 5. USERS PAGE LOGIC
// ============================================================
function openEditUserModal(id, name, email, role, status) {
    const modal = document.getElementById('editUserModal');
    if(!modal) return;

    document.getElementById('editUserId').value = id;
    document.getElementById('editUserName').value = name;
    document.getElementById('editUserEmail').value = email;
    document.getElementById('editUserRole').value = role;
    document.getElementById('editUserStatus').value = status;
    modal.classList.remove('hidden');
}

function closeEditUserModal() {
    const modal = document.getElementById('editUserModal');
    if(modal) modal.classList.add('hidden');
}

function saveUserChanges() {
    const id = document.getElementById('editUserId').value;
    const row = document.getElementById('user-row-' + id);
    
    if (row) {
        row.querySelector('.user-name').textContent = document.getElementById('editUserName').value;
        row.querySelector('.user-email').textContent = document.getElementById('editUserEmail').value;
        
        const role = document.getElementById('editUserRole').value;
        const roleEl = row.querySelector('.user-role');
        roleEl.textContent = role.charAt(0).toUpperCase() + role.slice(1);
        roleEl.className = 'user-role inline-flex items-center px-2 py-0.5 rounded text-xs font-medium ' + (role === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-slate-100 text-slate-800');

        const status = document.getElementById('editUserStatus').value;
        const statusCell = row.querySelector('.user-status-cell');
        if(status === 'active') {
            statusCell.innerHTML = '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Идэвхтэй</span>';
        } else if(status === 'banned') {
            statusCell.innerHTML = '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Бандуулсан</span>';
        } else {
            statusCell.innerHTML = '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-50 text-yellow-700 border border-yellow-200"><span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> Хүлээгдэж буй</span>';
        }
    }
    alert('Хэрэглэгчийн мэдээлэл шинэчлэгдлээ! (Demo)');
    closeEditUserModal();
}

function banUser(id) {
    if(confirm('Та энэ хэрэглэгчийг бандахдаа итгэлтэй байна уу?')) {
        const row = document.getElementById('user-row-' + id);
        if(row) {
            const statusCell = row.querySelector('.user-status-cell');
            statusCell.innerHTML = '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-200"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Бандуулсан</span>';
        }
        alert('Хэрэглэгч бандууллаа!');
    }
}

function unbanUser(id) {
    if(confirm('Бан цуцлах уу?')) {
        const row = document.getElementById('user-row-' + id);
        if(row) {
            const statusCell = row.querySelector('.user-status-cell');
            statusCell.innerHTML = '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700 border border-green-200"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Идэвхтэй</span>';
        }
        alert('Хэрэглэгчийн эрх сэргээгдлээ!');
    }
}

function deleteUser(id) {
    if(confirm('АНХААР: Хэрэглэгчийг устгах уу?')) {
        const row = document.getElementById('user-row-' + id);
        if(row) row.remove();
        alert('Хэрэглэгч устгагдлаа!');
    }
}


// ============================================================
// 6. CATEGORIES PAGE LOGIC
// ============================================================
function openModal(mode, data = null) {
    const modal = document.getElementById('categoryModal');
    if(!modal) return;
    
    const title = document.getElementById('modalTitle');
    
    // Reset fields
    document.getElementById('catId').value = '';
    document.getElementById('catName').value = '';
    document.getElementById('catSlug').value = '';
    document.getElementById('catParent').value = '';
    document.getElementById('catIcon').value = '';
    document.getElementById('catStatus').value = 'active';

    if (mode === 'edit' && data) {
        title.textContent = 'Ангилал засах';
        document.getElementById('catId').value = data.id;
        document.getElementById('catName').value = data.name;
        document.getElementById('catSlug').value = data.slug;
        document.getElementById('catParent').value = data.parent_id || '';
        document.getElementById('catIcon').value = data.icon || '';
        document.getElementById('catStatus').value = data.status;
    } else {
        title.textContent = 'Ангилал нэмэх';
    }

    modal.classList.remove('hidden');
}

function closeModal() {
    const modal = document.getElementById('categoryModal');
    if(modal) modal.classList.add('hidden');
}

function saveCategory() {
    alert('Ангилал амжилттай хадгалагдлаа! (Demo)');
    closeModal();
}


// ============================================================
// 7. SERVICES MODERATION LOGIC
// ============================================================
function approveService(id) {
    if(confirm('Та энэ үйлчилгээг нийтлэхийг зөвшөөрөх үү?')) {
        const row = document.getElementById('service-row-' + id);
        if(row) {
            const statusCell = row.querySelector('.service-status-cell');
            statusCell.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Зөвшөөрсөн</span>';
            alert('Үйлчилгээг амжилттай нийтэллээ! (Active)');
        }
    }
}

function rejectService(id) {
    const reason = prompt("Татгалзах шалтгаанаа бичнэ үү:", "Шаардлага хангахгүй байна.");
    if(reason) {
        const row = document.getElementById('service-row-' + id);
        if(row) {
            const statusCell = row.querySelector('.service-status-cell');
            statusCell.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Татгалзсан</span>';
            alert('Үйлчилгээг түдгэлзүүллээ. Шалтгаан: ' + reason);
        }
    }
}

function deleteService(id) {
    if(confirm('АНХААР: Энэ үйлчилгээг бүрмөсөн устгах уу?')) {
        const row = document.getElementById('service-row-' + id);
        if(row) row.remove();
        alert('Үйлчилгээ устгагдлаа!');
    }
}


// ============================================================
// 8. COMMENTS PAGE LOGIC
// ============================================================
function updateStatus(id, status) {
    const comment = document.getElementById('comment-' + id);
    if(comment) {
        if (status === 'approved') {
            comment.classList.remove('border-l-4', 'border-l-yellow-400', 'border-l-red-500', 'opacity-75');
            const badges = comment.querySelectorAll('.bg-yellow-100, .bg-red-100');
            badges.forEach(b => b.remove());
            alert('Сэтгэгдлийг зөвшөөрлөө!');
        } else if (status === 'spam') {
            comment.classList.add('border-l-4', 'border-l-red-500', 'opacity-75');
            alert('Сэтгэгдлийг спам гэж тэмдэглэлээ!');
        }
    }
}

function deleteComment(id) {
    if(confirm('Сэтгэгдлийг устгах уу?')) {
        const comment = document.getElementById('comment-' + id);
        if(comment) {
            comment.style.opacity = '0';
            setTimeout(() => comment.remove(), 300);
        }
    }
}

function replyComment(id) {
    const reply = prompt("Хариу бичих:");
    if (reply) {
        alert("Хариулт илгээгдлээ: " + reply);
    }
}


// ============================================================
// 9. FINANCE PAGE LOGIC (Tabs & Actions)
// ============================================================
function switchTab(tabName) {
    const withdrawalsContent = document.getElementById('content-withdrawals');
    const transactionsContent = document.getElementById('content-transactions');
    const tabWithdrawals = document.getElementById('tab-withdrawals');
    const tabTransactions = document.getElementById('tab-transactions');

    if (withdrawalsContent && transactionsContent) {
        // Hide All
        withdrawalsContent.classList.add('hidden');
        transactionsContent.classList.add('hidden');
        
        // Reset Tab Styles
        tabWithdrawals.classList.remove('active', 'text-indigo-600', 'border-indigo-500');
        tabWithdrawals.classList.add('text-slate-500', 'border-transparent');
        
        tabTransactions.classList.remove('active', 'text-indigo-600', 'border-indigo-500');
        tabTransactions.classList.add('text-slate-500', 'border-transparent');

        // Show Active
        document.getElementById('content-' + tabName).classList.remove('hidden');
        const activeTab = document.getElementById('tab-' + tabName);
        activeTab.classList.add('active', 'text-indigo-600', 'border-indigo-500');
        activeTab.classList.remove('text-slate-500', 'border-transparent');
    }
}

function approveWithdrawal(id) {
    if(confirm('Та энэ гүйлгээг хийсэн үү? Зөвшөөрөх үү?')) {
        const row = document.getElementById('withdrawal-' + id);
        if(row) {
            const statusCell = row.querySelector('.status-cell');
            statusCell.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Шилжүүлсэн</span>';
            const actionCell = row.querySelector('td:last-child');
            actionCell.innerHTML = '<span class="text-xs text-slate-400 italic">Үйлдэл хийгдсэн</span>';
            alert('Хүсэлтийг амжилттай баталгаажууллаа!');
        }
    }
}

function rejectWithdrawal(id) {
    const reason = prompt("Татгалзах шалтгаан:");
    if(reason) {
        const row = document.getElementById('withdrawal-' + id);
        if(row) {
            const statusCell = row.querySelector('.status-cell');
            statusCell.innerHTML = '<span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800"><span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Татгалзсан</span>';
            const actionCell = row.querySelector('td:last-child');
            actionCell.innerHTML = '<span class="text-xs text-slate-400 italic">Үйлдэл хийгдсэн</span>';
            alert('Хүсэлтийг татгалзлаа.');
        }
    }
}


// ============================================================
// 10. SETTINGS PAGE LOGIC (Tabs & Save)
// ============================================================
function switchSettingTab(tabName) {
    const contents = document.querySelectorAll('.settings-content');
    contents.forEach(el => el.classList.add('hidden'));
    
    const buttons = document.querySelectorAll('.settings-tab-btn');
    buttons.forEach(el => {
        el.classList.remove('active', 'bg-indigo-50', 'text-indigo-600', 'border-indigo-500');
        el.classList.add('border-transparent');
    });

    const content = document.getElementById('content-' + tabName);
    if(content) content.classList.remove('hidden');
    
    const activeBtn = document.getElementById('st-' + tabName);
    if(activeBtn) {
        activeBtn.classList.add('active');
        activeBtn.classList.remove('border-transparent');
    }
}

function saveSettings() {
    const btn = document.querySelector('button[onclick="saveSettings()"]');
    if(btn) {
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Хадгалж байна...';
        btn.disabled = true;

        setTimeout(() => {
            btn.innerHTML = originalText;
            btn.disabled = false;
            alert('Тохиргоо амжилттай хадгалагдлаа! (Demo)');
        }, 800);
    }
}