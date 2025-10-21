document.addEventListener('DOMContentLoaded', function () {
    // Düzenleme modalını tetikleyen butonu seç
    const editCompanyModal = document.getElementById('editCompanyModal');

    editCompanyModal.addEventListener('show.bs.modal', function (event) {
        // Modalı tetikleyen butonu al (Düzenle butonu)
        const button = event.relatedTarget;

        // Butonun data-* attribute'larından verileri çek
        const companyId = button.getAttribute('data-id');
        const companyName = button.getAttribute('data-name');
        const companyLogo = button.getAttribute('data-logo');

        // Modal içindeki form elemanlarını seç
        const modalForm = editCompanyModal.querySelector('#editCompanyForm');
        const modalTitle = editCompanyModal.querySelector('.modal-title');
        const companyIdInput = modalForm.querySelector('#edit_company_id');
        const companyNameInput = modalForm.querySelector('#edit_company_name');
        const currentLogoImg = modalForm.querySelector('#current_logo');

        // Form elemanlarını gelen verilerle doldur
        modalTitle.textContent = `Firma Düzenle: ${companyName}`;
        companyIdInput.value = companyId;
        companyNameInput.value = companyName;
        currentLogoImg.src = companyLogo; // Mevcut logoyu göster
    });
});
