document.addEventListener('DOMContentLoaded', function () {
    // Düzenleme modalını ve formunu seç
    const editModal = new bootstrap.Modal(document.getElementById('editTripModal'));
    const editForm = document.getElementById('editTripForm');
    const editTripIdInput = document.getElementById('edit_trip_id');

    // Sayfadaki tüm "Düzenle" butonlarını seç
    const editButtons = document.querySelectorAll('.edit-trip-btn');

    // Her bir butona tıklama olayı d
    editButtons.forEach(button => {
        button.addEventListener('click', function () {
            // Butonun data-* özelliklerinden verileri al
            const tripData = this.dataset;

            // Formdaki ilgili alanları bu verilerle doldur
            editTripIdInput.value = tripData.id;
            editForm.querySelector('#edit_departure_city').value = tripData.from;
            editForm.querySelector('#edit_destination_city').value = tripData.to;
            // Not: datetime-local input'u 'Y-m-d\TH:i' formatını bekler
            editForm.querySelector('#edit_departure_time').value = tripData.departure.replace(' ', 'T');
            editForm.querySelector('#edit_arrival_time').value = tripData.arrival.replace(' ', 'T');
            editForm.querySelector('#edit_price').value = tripData.price;
            editForm.querySelector('#edit_capacity').value = tripData.capacity;
            
            // Modalı göster
            editModal.show();
        });
    });
});
