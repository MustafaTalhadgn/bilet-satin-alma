document.addEventListener('DOMContentLoaded', function () {
    const editCouponModal = document.getElementById('editCouponModal');

    editCouponModal.addEventListener('show.bs.modal', function (event) {
        
        const button = event.relatedTarget;

        
        const couponId = button.getAttribute('data-id');
        const couponCode = button.getAttribute('data-code');
        const couponDiscount = button.getAttribute('data-discount');
        const couponLimit = button.getAttribute('data-limit');
        const couponExpiry = button.getAttribute('data-expiry');
        const companyName = button.getAttribute('data-company'); 

       
        const modalForm = editCouponModal.querySelector('#editCouponForm');
        const modalTitle = editCouponModal.querySelector('.modal-title');
        const couponIdInput = modalForm.querySelector('#edit_coupon_id');
        const couponCodeDisplay = modalForm.querySelector('#edit_coupon_code');
        const companyNameDisplay = modalForm.querySelector('#edit_company_name'); 
        const couponDiscountInput = modalForm.querySelector('#edit_discount');
        const couponLimitInput = modalForm.querySelector('#edit_usage_limit');
        const couponExpiryInput = modalForm.querySelector('#edit_expire_date');

       
        modalTitle.textContent = `Kupon Düzenle: ${couponCode}`;
        couponIdInput.value = couponId;
        couponCodeDisplay.textContent = couponCode;
        companyNameDisplay.textContent = companyName || 'Tüm Firmalar '; 
        couponDiscountInput.value = couponDiscount;
        couponLimitInput.value = couponLimit;
     
        couponExpiryInput.value = couponExpiry.split(' ')[0];
    });
});