document.addEventListener('DOMContentLoaded', function () {
    const editUserModal = document.getElementById('editUserModal');
    if (editUserModal) {
        const roleSelect = editUserModal.querySelector('#edit_role');
        const companyAssignmentWrapper = editUserModal.querySelector('#company_assignment_wrapper');
        const modalForm = editUserModal.querySelector('#editUserForm');


        function toggleCompanyAssignment() {
            if (roleSelect.value === 'company') {
                companyAssignmentWrapper.style.display = 'block';
                companyAssignmentWrapper.querySelector('select').setAttribute('required', 'required');
            } else {
            
                companyAssignmentWrapper.style.display = 'none';
                companyAssignmentWrapper.querySelector('select').removeAttribute('required');
            }
        }

      
        editUserModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            if (!button || !button.classList.contains('edit-user-btn')) return; // Buton değilse çık

           
            const userId = button.getAttribute('data-id');
            const userName = button.getAttribute('data-name');
            const userEmail = button.getAttribute('data-email');
            const userRole = button.getAttribute('data-role');
            const companyId = button.getAttribute('data-company-id');
            const userBalance = button.getAttribute('data-balance'); 

            
            const modalTitle = editUserModal.querySelector('.modal-title');
            const userIdInput = modalForm.querySelector('#edit_user_id');
            const userNameInput = modalForm.querySelector('#edit_full_name');
            const userEmailInput = modalForm.querySelector('#edit_email');
            const companySelect = modalForm.querySelector('#edit_company_id');
            const userBalanceInput = modalForm.querySelector('#edit_balance');

            
            modalTitle.textContent = `Kullanıcıyı Düzenle: ${userName || 'İsimsiz'}`;
            userIdInput.value = userId;
            userNameInput.value = userName;
            userEmailInput.value = userEmail;
            roleSelect.value = userRole;
            companySelect.value = companyId || '';
            userBalanceInput.value = userBalance || 0; 

          
            toggleCompanyAssignment();
        });

    
        if(roleSelect) {
            roleSelect.addEventListener('change', toggleCompanyAssignment);
        }
    }
});