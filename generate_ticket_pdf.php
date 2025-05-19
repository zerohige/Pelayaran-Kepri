<?php
// generate_ticket_pdf.php - Generate PDF tiket reservasi
require_once 'controller/db_connection.php';
require_once 'fpdf/fpdf.php'; // Pastikan library FPDF sudah diinstal

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$reservation_id = (int)$_GET['id'];

// Ambil data reservasi
$query = "SELECT r.*, r.kode AS kode_reservasi,
          o.name AS origin_name, d.name AS destination_name, 
          s.name AS ship_name, s.price AS ship_price
          FROM reservations r
          JOIN locations o ON r.origin_id = o.id
          JOIN locations d ON r.destination_id = d.id
          JOIN ships s ON r.ship_id = s.id
          WHERE r.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: index.php');
    exit;
}

$reservation = $result->fetch_assoc();

// Get passenger details
$query = "SELECT * FROM passengers WHERE reservation_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $reservation_id);
$stmt->execute();
$passengers_result = $stmt->get_result();

$passengers = [];
while ($row = $passengers_result->fetch_assoc()) {
    $passengers[] = $row;
}

// Hitung batas waktu pembayaran (1 hari sebelum keberangkatan)
$departure_date = new DateTime($reservation['departure_date']);
$payment_deadline = clone $departure_date;
$payment_deadline->modify('-1 day');

// Generate PDF
$pdf = new FPDF();
$pdf->AddPage();

// Header
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'TIKET RESERVASI PELAYARAN KEPRI', 0, 1, 'C');
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'KODE RESERVASI: ' . $reservation['kode_reservasi'], 0, 1, 'C');
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(5);

// Informasi Perjalanan
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'INFORMASI PERJALANAN', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(50, 7, 'Asal:', 0);
$pdf->Cell(0, 7, $reservation['origin_name'], 0, 1);
$pdf->Cell(50, 7, 'Tujuan:', 0);
$pdf->Cell(0, 7, $reservation['destination_name'], 0, 1);
$pdf->Cell(50, 7, 'Kapal:', 0);
$pdf->Cell(0, 7, $reservation['ship_name'], 0, 1);
$pdf->Cell(50, 7, 'Tanggal Keberangkatan:', 0);
$pdf->Cell(0, 7, date('d F Y', strtotime($reservation['departure_date'])), 0, 1);
$pdf->Cell(50, 7, 'Batas Waktu Pembayaran:', 0);
$pdf->Cell(0, 7, $payment_deadline->format('d F Y') . ' (1 hari sebelum keberangkatan)', 0, 1);
$pdf->Ln(5);

// Informasi Penumpang
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'DAFTAR PENUMPANG', 0, 1);
$pdf->SetFont('Arial', '', 10);
foreach ($passengers as $index => $passenger) {
    $pdf->Cell(10, 7, ($index + 1) . '.', 0);
    $pdf->Cell(80, 7, $passenger['name'], 0);
    $pdf->Cell(40, 7, 'KTP: ' . $passenger['ktp_number'], 0);
    $pdf->Cell(0, 7, 'HP: ' . $passenger['phone_number'], 0, 1);
}
$pdf->Ln(5);

// Informasi Pembayaran
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'INFORMASI PEMBAYARAN', 0, 1);
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(50, 7, 'Harga per Tiket:', 0);
$pdf->Cell(0, 7, formatRupiah($reservation['ship_price']), 0, 1);
$pdf->Cell(50, 7, 'Jumlah Penumpang:', 0);
$pdf->Cell(0, 7, count($passengers) . ' orang', 0, 1);
$pdf->Cell(50, 7, 'Total Harga:', 0);
$pdf->Cell(0, 7, formatRupiah($reservation['total_price']), 0, 1);
$pdf->Cell(50, 7, 'Status Pembayaran:', 0);
$status_text = [
    'pending' => 'Menunggu Pembayaran',
    'paid' => 'Sudah Dibayar',
    'expired' => 'Expired/Hangus'
];
$pdf->Cell(0, 7, $status_text[$reservation['status']] ?? 'Tidak Diketahui', 0, 1);
$pdf->Ln(5);

// Perhatian
$pdf->SetFillColor(255, 240, 240);
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'PERHATIAN:', 0, 1, 'L', true);
$pdf->SetFont('Arial', '', 10);
$pdf->MultiCell(0, 5, '1. Harap lakukan pembayaran di outlet pelabuhan sebelum tanggal ' . $payment_deadline->format('d F Y') . '.
2. Reservasi akan HANGUS secara otomatis jika tidak dibayar sebelum batas waktu tersebut.
3. Tunjukkan tiket ini saat melakukan pembayaran.
4. Datang minimal 30 menit sebelum jadwal keberangkatan.', 1);

// Output PDF
$pdf->Output('D', 'Tiket_Pelayaran_' . $reservation['kode_reservasi'] . '.pdf');
?>