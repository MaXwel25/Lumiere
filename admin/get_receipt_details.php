<?php
// get_receipt_details.php
$receiptId = $_GET['id'] ?? 0;

echo '<div class="receipt-details-modal">';
echo '<h3>Детали чека #' . $receiptId . '</h3>';
echo '<p>Это тестовые данные. В реальном приложении здесь будет информация из базы данных.</p>';
echo '<button onclick="window.print()" class="btn btn-primary">Распечатать</button>';
echo '</div>';
?>