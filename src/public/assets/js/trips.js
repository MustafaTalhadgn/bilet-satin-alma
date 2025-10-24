document.addEventListener('DOMContentLoaded', function () {

    const tripCards = document.querySelectorAll('.trip-card');

    tripCards.forEach(card => {
        const availableSeats = card.querySelectorAll('.seat.available');
        const selectionSummary = card.querySelector('.selection-summary');
        const selectedSeatNumberDisplay = card.querySelector('.selected-seat-number');
        const selectedSeatInput = card.querySelector('.selected-seat-input'); // Hidden input
        const occupiedSeats = card.querySelectorAll('.seat.occupied');

       availableSeats.forEach(seat => {
            seat.addEventListener('click', () => {
            
                const currentlySelected = card.querySelector('.seat.selected');
                if (currentlySelected) {
                    currentlySelected.classList.remove('selected');
                }

            
                seat.classList.add('selected');

                const seatNumber = seat.dataset.seatNumber;

                
                selectedSeatNumberDisplay.textContent = seatNumber;
                selectedSeatInput.value = seatNumber; 
                selectionSummary.style.display = 'block';
            });
        });

        
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

     
        const targetPaneId = this.getAttribute('data-bs-target');


        const targetNavLink = document.querySelector(`.nav-link[href="${targetPaneId}"]`);


        if (targetNavLink) {
            const tab = new bootstrap.Tab(targetNavLink);
            tab.show(); 
        }
    });
});
});