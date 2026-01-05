<?php
// api/brevo_email.php

/**
 * Brevo API ашиглан ЗӨРЧИЛ МЭДЭЭЛЭХ мэйл илгээх функц
 * @param string $toEmail Хүлээн авагчийн мэйл
 * @param string $serviceTitle Үйлчилгээний гарчиг
 * @param string $reporterName Мэдээлэгчийн нэр
 * @param string $reason Зөрчлийн шалтгаан
 * @param string $description Дэлгэрэнгүй тайлбар
 * @param int $serviceId Үйлчилгээний ID
 * @return string API хариу
 */
function sendBrevoEmail($toEmail, $serviceTitle, $reporterName, $reason, $description, $serviceId) {
    // ------------------------------------------------------
    // ТОХИРГОО: Энд өөрийн Brevo API Key-ээ хийнэ үү
    // ------------------------------------------------------
    $apiKey = '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'; // Таны API KEY
    
    // Sender Email - Brevo дээр баталгаажсан байх ёстой
    $senderEmail = 'giamia999@gmail.com'; 
    $senderName = 'Filezone System';

    $url = 'https://api.brevo.com/v3/smtp/email';
    
    $data = [
        'sender' => [
            'name' => $senderName,
            'email' => $senderEmail
        ],
        'to' => [
            [
                'email' => $toEmail,
                'name' => 'Admin'
            ]
        ],
        'subject' => '[ЗӨРЧИЛ] Шинэ мэдээлэл: #' . $serviceId,
        'htmlContent' => "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <h2 style='color: #EF4444;'>Шинэ зөрчил бүртгэгдлээ</h2>
                <p>Сайн байна уу,</p>
                <p>Filezone.mn платформ дээр хэрэглэгч <strong>{$reporterName}</strong> зөрчил мэдээллээ.</p>
                <div style='background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 15px 0;'>
                    <p><strong>Үйлчилгээ:</strong> {$serviceTitle} (ID: {$serviceId})</p>
                    <p><strong>Шалтгаан:</strong> {$reason}</p>
                    <p><strong>Тайлбар:</strong><br>{$description}</p>
                </div>
                <br>
                <a href='https://filezone.mn/admin/reports.php' style='background-color: #EF4444; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Админ панел руу нэвтрэх</a>
                <p style='font-size: 12px; color: #888; margin-top: 20px;'>Энэ бол системээс автоматаар илгээсэн мэйл юм. Хариу бичих шаардлагагүй.</p>
            </body>
            </html>
        "
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'api-key: ' . $apiKey,
        'content-type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // SSL баталгаажуулалтыг түр алгасах (Хөгжүүлэлтийн үед)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    curl_close($ch);
    
    // Logging logic could be extracted to a helper if needed
    
    return $response;
}

/**
 * Brevo API ашиглан МӨНГӨ ТАТАХ ХҮСЭЛТ-ийн мэйл илгээх функц
 * @param string $adminEmail Админы мэйл
 * @param string $userName Хэрэглэгчийн нэр
 * @param float $amount Татах дүн
 * @param string $method Татах арга (bank, qpay)
 * @param string $bankName Банкны нэр
 * @param string $accountName Данс эзэмшигчийн нэр
 * @param string $accountNumber Дансны дугаар
 * @param string $reqNum Хүсэлтийн дугаар
 * @return string API хариу
 */
function sendWithdrawalNotificationEmail($adminEmail, $userName, $amount, $method, $bankName, $accountName, $accountNumber, $reqNum) {
    // ------------------------------------------------------
    // ТОХИРГОО: Brevo API Key
    // ------------------------------------------------------
    $apiKey = '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'; // Таны API KEY
    
    // Sender Email
    $senderEmail = 'giamia999@gmail.com'; 
    $senderName = 'Filezone System';

    $url = 'https://api.brevo.com/v3/smtp/email';
    
    $data = [
        'sender' => [
            'name' => $senderName,
            'email' => $senderEmail
        ],
        'to' => [
            [
                'email' => $adminEmail,
                'name' => 'Admin'
            ]
        ],
        'subject' => '[MӨНГӨ ТАТАХ] Шинэ хүсэлт: ' . $reqNum,
        'htmlContent' => "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <h2 style='color: #2563EB;'>Шинэ мөнгө татах хүсэлт ирлээ</h2>
                <p>Сайн байна уу,</p>
                <p>Хэрэглэгч <strong>{$userName}</strong> мөнгө татах хүсэлт илгээлээ.</p>
                
                <div style='background: #f0f9ff; padding: 15px; border-radius: 5px; margin: 15px 0; border: 1px solid #bae6fd;'>
                    <p><strong>Хүсэлтийн дугаар:</strong> {$reqNum}</p>
                    <p><strong>Татах дүн:</strong> " . number_format($amount) . "₮</p>
                    <hr style='border: 0; border-top: 1px solid #bae6fd; margin: 10px 0;'>
                    <h3>Дансны мэдээлэл:</h3>
                    <ul style='list-style: none; padding: 0;'>
                        <li><strong>Төрөл:</strong> {$method}</li>
                        <li><strong>Банк:</strong> {$bankName}</li>
                        <li><strong>Данс эзэмшигч:</strong> {$accountName}</li>
                        <li><strong>Дансны дугаар:</strong> {$accountNumber}</li>
                    </ul>
                </div>
                
                <br>
                <a href='https://filezone.mn/admin/withdrawals.php' style='background-color: #2563EB; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;'>Админ панел руу орох</a>
                <p style='font-size: 12px; color: #888; margin-top: 20px;'>Энэ бол системээс автоматаар илгээсэн мэйл юм.</p>
            </body>
            </html>
        "
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'accept: application/json',
        'api-key: ' . $apiKey,
        'content-type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    // SSL баталгаажуулалтыг түр алгасах (Хөгжүүлэлтийн үед)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // --- LOGGING ---
    $logFile = __DIR__ . '/../brevo_log.txt'; // api/ -> root
    
    $logMessage = "WITHDRAWAL REQUEST - DT: " . date('Y-m-d H:i:s') . "\n";
    $logMessage .= "To: $adminEmail\n";
    $logMessage .= "Response: $response\n";
    if ($curlError) {
        $logMessage .= "CURL Error: $curlError\n";
    }
    $logMessage .= "-----------------------------------\n";
    
    @file_put_contents($logFile, $logMessage, FILE_APPEND);
    
    return $response;
}
?>