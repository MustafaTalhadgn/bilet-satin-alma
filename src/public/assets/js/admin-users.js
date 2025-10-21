document.addEventListener('DOMContentLoaded', function () {
    const editUserModal = document.getElementById('editUserModal');
    const roleSelect = editUserModal.querySelector('#edit_role');
    const companyAssignmentWrapper = editUserModal.querySelector('#company_assignment_wrapper');

    // Rol değiştiğinde Firma seçimi alanını göster/gizle
    function toggleCompanyAssignment() {
        if (roleSelect.value === 'company') {
            companyAssignmentWrapper.style.display = 'block';
            companyAssignmentWrapper.querySelector('select').setAttribute('required', 'required');
        } else {
            companyAssignmentwrapper.style.display = 'none';
            companyAssignmentWrapper.querySelector('select').removeAttribute('required');
        }
    }

    // Modal açıldığında tetiklenir
    editUserModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;

        const userId = button.getAttribute('data-id');
        const userName = button.getAttribute('data-name');
        const userEmail = button.getAttribute('data-email');
        const userRole = button.getAttribute('data-role');
        const companyId = button.getAttribute('data-company-id');

        const modalForm = editUserModal.querySelector('#editUserForm');
        const modalTitle = editUserModal.querySelector('.modal-title');
        const userIdInput = modalForm.querySelector('#edit_user_id');
        const userNameInput = modalForm.querySelector('#edit_full_name');
        const userEmailInput = modalForm.querySelector('#edit_email');
        const companySelect = modalForm.querySelector('#edit_company_id');

        // Formu doldur
        modalTitle.textContent = `Kullanıcıyı Düzenle: ${userName}`;
        userIdInput.value = userId;
        userNameInput.value = userName;
        userEmailInput.value = userEmail;
        roleSelect.value = userRole;
        companySelect.value = companyId || '';

        // Rol'e göre firma alanını ayarla
        toggleCompanyAssignment();
    });

    // Modal içindeki Rol dropdown'ı değiştiğinde de kontrol et
    roleSelect.addEventListener('change', toggleCompanyAssignment);
});