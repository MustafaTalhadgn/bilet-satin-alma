document.addEventListener('DOMContentLoaded', function () {

    const editCompanyModal = document.getElementById('editCompanyModal');

    editCompanyModal.addEventListener('show.bs.modal', function (event) {
        
        const button = event.relatedTarget;

      
        const companyId = button.getAttribute('data-id');
        const companyName = button.getAttribute('data-name');
        const companyLogo = button.getAttribute('data-logo');

        
        const modalForm = editCompanyModal.querySelector('#editCompanyForm');
        const modalTitle = editCompanyModal.querySelector('.modal-title');
        const companyIdInput = modalForm.querySelector('#edit_company_id');
        const companyNameInput = modalForm.querySelector('#edit_company_name');
        const currentLogoImg = modalForm.querySelector('#current_logo');

    
        modalTitle.textContent = `Firma Düzenle: ${companyName}`;
        companyIdInput.value = companyId;
        companyNameInput.value = companyName;
        currentLogoImg.src = companyLogo; 
    });
});
