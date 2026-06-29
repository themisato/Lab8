<?php
// Настройки
$admin_email = "rain228win@icloud.com";  // Ваша почта
$subject = "Новая заявка с сайта Нод-край";
$from = "noreply@nodkrai.ru";  // Замените на ваш домен

// Включаем отладку (убрать на продакшене)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Проверка метода запроса
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не разрешен']);
    exit;
}

// Заголовки для JSON ответа
header('Content-Type: application/json; charset=utf-8');

// Проверка на спам (скрытое поле должно быть пустым)
if (!empty($_POST['antispam'])) {
    echo json_encode(['success' => false, 'message' => 'Обнаружен спам']);
    exit;
}

// Получение и очистка данных
$name = trim(htmlspecialchars($_POST['name'] ?? ''));
$phone = trim(htmlspecialchars($_POST['phone'] ?? ''));
$email = trim(htmlspecialchars($_POST['email'] ?? ''));
$comment = trim(htmlspecialchars($_POST['comment'] ?? ''));

// Массив для ошибок
$errors = [];

// Валидация имени
if (empty($name)) {
    $errors['name'] = 'Пожалуйста, введите ваше имя';
} elseif (strlen($name) < 2) {
    $errors['name'] = 'Имя должно содержать минимум 2 символа';
} elseif (strlen($name) > 50) {
    $errors['name'] = 'Имя не должно превышать 50 символов';
} elseif (!preg_match("/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u", $name)) {
    $errors['name'] = 'Имя может содержать только буквы, пробелы и дефисы';
}

// Валидация телефона
if (empty($phone)) {
    $errors['phone'] = 'Пожалуйста, введите номер телефона';
} elseif (!preg_match("/^[\+]?[0-9\s\-\(\)]{10,20}$/", $phone)) {
    $errors['phone'] = 'Введите корректный номер телефона';
}

// Валидация email
if (empty($email)) {
    $errors['email'] = 'Пожалуйста, введите email';
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Введите корректный email адрес';
} elseif (strlen($email) > 100) {
    $errors['email'] = 'Email не должен превышать 100 символов';
}

// Валидация комментария
if (empty($comment)) {
    $errors['comment'] = 'Пожалуйста, введите ваш комментарий';
} elseif (strlen($comment) < 10) {
    $errors['comment'] = 'Комментарий должен содержать минимум 10 символов';
} elseif (strlen($comment) > 1000) {
    $errors['comment'] = 'Комментарий не должен превышать 1000 символов';
}

// Если есть ошибки - возвращаем их
if (!empty($errors)) {
    echo json_encode([
        'success' => false,
        'message' => 'Пожалуйста, исправьте ошибки в форме',
        'errors' => $errors
    ]);
    exit;
}

// Формирование тела письма
$message = "
<html>
<head>
    <title>$subject</title>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #0a2d4e; color: white; padding: 20px; text-align: center; }
        .content { background-color: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .field { margin-bottom: 15px; }
        .label { font-weight: bold; color: #333; }
        .value { color: #666; }
        .footer { margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; color: #777; font-size: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>$subject</h1>
        </div>
        <div class='content'>
            <div class='field'>
                <div class='label'>Имя:</div>
                <div class='value'>$name</div>
            </div>
            <div class='field'>
                <div class='label'>Телефон:</div>
                <div class='value'>$phone</div>
            </div>
            <div class='field'>
                <div class='label'>Email:</div>
                <div class='value'>$email</div>
            </div>
            <div class='field'>
                <div class='label'>Комментарий:</div>
                <div class='value'>$comment</div>
            </div>
            <div class='field'>
                <div class='label'>Дата и время:</div>
                <div class='value'>" . date('d.m.Y H:i:s') . "</div>
            </div>
            <div class='field'>
                <div class='label'>IP адрес:</div>
                <div class='value'>" . $_SERVER['REMOTE_ADDR'] . "</div>
            </div>
        </div>
        <div class='footer'>
            Это письмо было отправлено с формы обратной связи сайта Нод-край
        </div>
    </div>
</body>
</html>
";

// Заголовки письма
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
$headers .= "From: Сайт Нод-край <$from>\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();
$headers .= "X-Priority: 1\r\n"; // Высокий приоритет

// Дополнительные настройки для улучшения доставки
$headers .= "Return-Path: $from\r\n";

// Отправка письма
try {
    // Проверяем, доступна ли функция mail
    if (!function_exists('mail')) {
        throw new Exception('Функция mail не доступна на сервере');
    }
    
    // Отправляем письмо
    $mail_sent = mail($admin_email, $subject, $message, $headers);
    
    if ($mail_sent) {
        // Логирование успешной отправки (опционально)
        $log_message = date('Y-m-d H:i:s') . " - Успешно отправлено: $name, $email\n";
        file_put_contents('mail_log.txt', $log_message, FILE_APPEND);
        
        // Ответ об успехе
        echo json_encode([
            'success' => true,
            'message' => 'Сообщение успешно отправлено! Мы свяжемся с вами в ближайшее время.'
        ]);
    } else {
        throw new Exception('Не удалось отправить письмо. Пожалуйста, попробуйте позже.');
    }
    
} catch (Exception $e) {
    // Логирование ошибки
    $error_log = date('Y-m-d H:i:s') . " - Ошибка: " . $e->getMessage() . "\n";
    file_put_contents('mail_errors.txt', $error_log, FILE_APPEND);
    
    echo json_encode([
        'success' => false,
        'message' => 'Произошла ошибка при отправке. Пожалуйста, попробуйте позже или свяжитесь другим способом.'
    ]);
}
?>