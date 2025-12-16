<?php
// print_receipt.php
$receiptId = $_GET['id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Чек #<?php echo $receiptId; ?></title>
    <style>
        body { font-family: Arial, sans-serif; }
        .receipt { max-width: 300px; margin: 0 auto; }
        .header { text-align: center; }
        .items { width: 100%; border-collapse: collapse; }
        .total { font-weight: bold; text-align: right; }
    </style>
</head>
<body onload="window.print()">
    <div class="receipt">
        <div class="header">
            <h2>Lumiere Парикмахерская</h2>
            <p>Чек #<?php echo $receiptId; ?></p>
            <p>Дата: <?php echo date('d.m.Y H:i'); ?></p>
        </div>
        <table class="items">
            <tr><td>Услуга</td><td>1200 ₽</td></tr>
            <tr><td>Итого:</td><td class="total">1200 ₽</td></tr>
        </table>
        <p>Спасибо за посещение!</p>
    </div>
</body>
</html>