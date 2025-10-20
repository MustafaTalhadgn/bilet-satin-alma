<?php
function translateDate($date) {
    $months = [
        'January' => 'Ocak',
        'February' => 'Şubat',
        'March' => 'Mart',
        'April' => 'Nisan',
        'May' => 'Mayıs',
        'June' => 'Haziran',
        'July' => 'Temmuz',
        'August' => 'Ağustos',
        'September' => 'Eylül',
        'October' => 'Ekim',
        'November' => 'Kasım',
        'December' => 'Aralık'
    ];

    $day = date('d', strtotime($date));
    $month = $months[date('F', strtotime($date))];
    $year = date('Y', strtotime($date));

    return "$day $month $year";
}
?>