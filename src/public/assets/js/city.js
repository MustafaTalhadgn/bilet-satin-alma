document.addEventListener('DOMContentLoaded', function () {

    // Gerekli HTML elemanlarını seçelim
    const dateInput = document.getElementById('departure-date');
    const todayRadio = document.getElementById('radio-today');
    const tomorrowRadio = document.getElementById('radio-tomorrow');

    // Tarih formatlama fonksiyonu (YYYY-MM-DD)
    const formatDate = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    };

    // Bugün'ün tarihini alalım
    const today = new Date();
    // Yarın'ın tarihini alalım
    const tomorrow = new Date();
    tomorrow.setDate(today.getDate() + 1);

    // "Bugün" radyosuna tıklandığında
    todayRadio.addEventListener('change', () => {
        if (todayRadio.checked) {
            dateInput.value = formatDate(today);
        }
    });

    // "Yarın" radyosuna tıklandığında
    tomorrowRadio.addEventListener('change', () => {
        if (tomorrowRadio.checked) {
            dateInput.value = formatDate(tomorrow);
        }
    });

    // Eğer tarih manuel olarak değiştirilirse, radyoların seçimini kaldır
    dateInput.addEventListener('input', () => {
        const selectedDate = dateInput.value;
        if (selectedDate === formatDate(today)) {
            todayRadio.checked = true;
        } else if (selectedDate === formatDate(tomorrow)) {
            tomorrowRadio.checked = true;
        } else {
            todayRadio.checked = false;
            tomorrowRadio.checked = false;
        }
    });
});