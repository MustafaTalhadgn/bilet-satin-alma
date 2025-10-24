document.addEventListener('DOMContentLoaded', function () {
    // --- SEFER DÜZENLEME MODALI ---
    const editTripModal = document.getElementById('editTripModal');
    if (editTripModal) {
        const editTripForm = editTripModal.querySelector('#editTripForm');
        editTripModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            // Sadece .edit-trip-btn butonlarından gelirse çalış
            if (button && button.classList.contains('edit-trip-btn')) {
                const tripData = button.dataset;
                editTripForm.querySelector('#edit_trip_id').value = tripData.id;
                editTripForm.querySelector('#edit_departure_city').value = tripData.from;
                editTripForm.querySelector('#edit_destination_city').value = tripData.to;
                editTripForm.querySelector('#edit_departure_time').value = tripData.departure.replace(' ', 'T');
                editTripForm.querySelector('#edit_arrival_time').value = tripData.arrival.replace(' ', 'T');
                editTripForm.querySelector('#edit_price').value = tripData.price;
                editTripForm.querySelector('#edit_capacity').value = tripData.capacity;
            }
        });
    }

    // --- KUPON DÜZENLEME MODALI (GÜÇLENDİRİLMİŞ) ---
    const editCouponModal = document.getElementById('editCouponModal');
    if (editCouponModal) {
        const editCouponForm = editCouponModal.querySelector('#editCouponForm');
        editCouponModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;

            // GÜVENLİK KONTROLÜ: Butonun var olduğundan ve doğru sınıfa sahip olduğundan emin ol
            if (button && button.classList.contains('edit-coupon-btn')) {
                const couponData = button.dataset;
                console.log(couponData.id)

                // Hata ayıklama için konsola yazdır
                console.log("Kupon Verisi Yükleniyor:", couponData);

                editCouponForm.querySelector('#edit_coupon_id').value = couponData.id;
                editCouponForm.querySelector('#edit_coupon_code').textContent = couponData.code;
                editCouponForm.querySelector('#edit_coupon_discount').value = couponData.discount;
                editCouponForm.querySelector('#edit_coupon_limit').value = couponData.limit;
                // Tarih formatını 'YYYY-MM-DD' olarak ayarla
                editCouponForm.querySelector('#edit_coupon_expiry').value = couponData.expiry.split(' ')[0];
            }
        });
    }
});