<?php
session_start();
require_once __DIR__ . '/../app/config.php';
// DÜZELTME: Artık tFPDF kütüphanesini çağırıyoruz
require_once __DIR__ . '/../fpdf/tfpdf.php';

// --- GÜVENLİK GÖREVLİSİ (GUARD) ---
if (!isset($_SESSION['user_id']) || !isset($_GET['ticket_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$ticket_id = $_GET['ticket_id'];

// --- BİLET BİLGİLERİNİ ÇEKME ---
try {
    $stmt = $pdo->prepare("
        SELECT
            t.id AS ticket_id, t.total_price,
            u.full_name AS user_name,
            tr.departure_city, tr.destination_city, tr.departure_time, tr.arrival_time,
            bc.name AS company_name, bc.logo_path,
            bs.seat_number
        FROM Tickets t
        JOIN User u ON t.user_id = u.id
        JOIN Trips tr ON t.trip_id = tr.id
        JOIN Bus_Company bc ON tr.company_id = bc.id
        JOIN Booked_Seats bs ON bs.ticket_id = t.id
        WHERE t.id = :ticket_id AND t.user_id = :user_id
    ");
    $stmt->execute([':ticket_id' => $ticket_id, ':user_id' => $user_id]);
    $ticket_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ticket_data) {
        die("Bilet bulunamadı veya bu bileti görüntüleme yetkiniz yok.");
    }
} catch (PDOException $e) {
    die("Veritabanı hatası: Bilet bilgileri alınamadı.");
}

// --- ARTIK GEREKLİ DEĞİL: tr() fonksiyonunu sildik ---

// --- PDF OLUŞTURMA SÜRECİ ---
class PDF extends tFPDF { // DÜZELTME: FPDF yerine tFPDF'i extend ediyoruz
    function Header() {
        global $ticket_data;
        if (file_exists($ticket_data['logo_path'])) {
            $this->Image($ticket_data['logo_path'], 10, 6, 30);
        }
        $this->SetFont('DejaVu', 'B', 15);
        $this->Cell(80);
        $this->Cell(30, 10, $ticket_data['company_name'], 0, 0, 'C');
        $this->Ln(20);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('DejaVu', 'I', 8);
        $this->Cell(0, 10, 'İyi yolculuklar dileriz! - BiletSatinAlma Sistemi', 0, 0, 'C');
    }
}

// PDF nesnesini oluştur
$pdf = new PDF();

// DÜZELTME: UTF-8 destekli fontu ekle ve seç
$pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
$pdf->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);
$pdf->AddFont('DejaVu', 'I', 'DejaVuSans-Oblique.ttf', true);

$pdf->SetFont('DejaVu', '', 12);
$pdf->AddPage();
$pdf->SetFillColor(240, 240, 240);

// --- BİLET İÇERİĞİ (Tüm tr() fonksiyonları kaldırıldı) ---
$pdf->SetFont('DejaVu', 'B', 18);
$pdf->Cell(0, 10, 'YOLCU BİLETİ', 0, 1, 'C');
$pdf->Ln(10);

// Yolcu Bilgileri
$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(50, 10, 'Yolcu Adı Soyadı:', 0, 0);
$pdf->SetFont('DejaVu', '', 12);
$pdf->Cell(0, 10, $ticket_data['user_name'], 0, 1);

$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(50, 10, 'Koltuk Numarası:', 0, 0);
$pdf->SetFont('DejaVu', '', 12);
$pdf->Cell(0, 10, $ticket_data['seat_number'], 0, 1);
$pdf->Ln(5);

// Sefer Bilgileri
$pdf->SetFont('DejaVu', 'B', 14);
$pdf->Cell(0, 10, 'Sefer Bilgileri', 0, 1, 'L', true);
$pdf->Ln(2);

$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(50, 10, 'Güzergah:', 0, 0);
$pdf->SetFont('DejaVu', '', 12);
$pdf->Cell(0, 10, $ticket_data['departure_city'] . ' -> ' . $ticket_data['destination_city'], 0, 1);

$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(50, 10, 'Kalkış Tarihi:', 0, 0);
$pdf->SetFont('DejaVu', '', 12);
$pdf->Cell(0, 10, date("d.m.Y H:i", strtotime($ticket_data['departure_time'])), 0, 1); // Standart tarih formatı yeterli
$pdf->Ln(5);

// Fiyat Bilgileri
$pdf->SetFont('DejaVu', 'B', 14);
$pdf->Cell(0, 10, 'Ödeme Bilgileri', 0, 1, 'L', true);
$pdf->Ln(2);

$pdf->SetFont('DejaVu', 'B', 12);
$pdf->Cell(50, 10, 'Ödenen Tutar:', 0, 0);
$pdf->SetFont('DejaVu', 'B', 14);
$pdf->Cell(0, 10, number_format($ticket_data['total_price'], 2, ',', '.') . ' TL', 0, 1);

// --- PDF'İ ÇIKTI OLARAK GÖNDER ---
$pdf_filename = 'bilet-' . $ticket_id . '.pdf';
$pdf->Output('D', $pdf_filename);
exit();

