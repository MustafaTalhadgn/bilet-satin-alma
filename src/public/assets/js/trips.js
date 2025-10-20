document.addEventListener('DOMContentLoaded', function () {
    // Sayfadaki tüm sefer kartlarını seç
    const tripCards = document.querySelectorAll('.trip-card');

    tripCards.forEach(card => {
        const availableSeats = card.querySelectorAll('.seat.available');
        const selectionSummary = card.querySelector('.selection-summary');
        const selectedSeatNumberDisplay = card.querySelector('.selected-seat-number');
        const selectedSeatInput = card.querySelector('.selected-seat-input'); // Hidden input
        const occupiedSeats = card.querySelectorAll('.seat.occupied');

        // Boş koltuklara tıklama olayı
        availableSeats.forEach(seat => {
            seat.addEventListener('click', () => {
                // Bu kart içindeki diğer 'seçili' koltukları temizle
                const currentlySelected = card.querySelector('.seat.selected');
                if (currentlySelected) {
                    currentlySelected.classList.remove('selected');
                }

                // Tıklanan koltuğu 'seçili' yap
                seat.classList.add('selected');

                const seatNumber = seat.dataset.seatNumber;

                // Sağdaki özet panelini güncelle ve göster
                selectedSeatNumberDisplay.textContent = seatNumber;
                selectedSeatInput.value = seatNumber; // Formdaki gizli input'u doldur
                selectionSummary.style.display = 'block';
            });
        });

        // Dolu koltuklara tıklama olayı
        occupiedSeats.forEach(seat => {
            seat.addEventListener('click', () => {
                alert('Bu koltuk dolu, lütfen başka bir koltuk seçin.');
            });
        });
    });



const selectSeatButtons = document.querySelectorAll('.select-seat-btn');
selectSeatButtons.forEach(button => {
    button.addEventListener('click', function(e) {
        e.preventDefault();

        // Butonun hedeflediği panelin ID'sini al (Örn: #seats-xyz)
        const targetPaneId = this.getAttribute('data-bs-target');

        // O panele ait olan navigasyon linkini bul
        // href'i, butonun data-bs-target'i ile aynı olan <a> etiketini seçiyoruz.
        const targetNavLink = document.querySelector(`.nav-link[href="${targetPaneId}"]`);

        // Eğer böyle bir link varsa, Bootstrap'in Tab nesnesini onun üzerinden oluştur
        if (targetNavLink) {
            const tab = new bootstrap.Tab(targetNavLink);
            tab.show(); // .show() metodu hem paneli hem de linki aktif eder.
        }
    });
});
});