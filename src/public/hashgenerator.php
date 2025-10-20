<?php
// Firma admini için belirlemek istediğin şifreyi buraya yaz
$plain_password = 'kamilkoc123';

// Şifreyi en güncel ve güvenli algoritma ile hash'le
$hashed_password = password_hash($plain_password, PASSWORD_ARGON2ID);

// Ekrana hash'lenmiş şifreyi yazdır
echo $hashed_password;
?>