<?php
if (!function_exists('translateDate')) {
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

        $timestamp = strtotime($date);
        $day = date('d', $timestamp);
        $month = $months[date('F', $timestamp)];
        $year = date('Y', $timestamp);

        
        $hasTime = preg_match('/\d{1,2}:\d{2}/', $date);

        if ($hasTime) {
            $time = date('H:i', $timestamp);
            return "$day $month $year - $time";
        } else {
            return "$day $month $year";
        }
    }
}
?>
