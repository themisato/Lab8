<?php
// api.php - Веб-сервис
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once 'config.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

function sendResponse($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

function validateData($data) {
    $errors = [];
    
    $full_name = trim($data['full_name'] ?? '');
    if (empty($full_name)) {
        $errors['full_name'] = 'ФИО обязательно для заполнения';
    } elseif (strlen($full_name) > 150) {
        $errors['full_name'] = 'ФИО не должно превышать 150 символов';
    } elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $full_name)) {
        $errors['full_name'] = 'ФИО может содержать только буквы, пробелы и дефис';
    }
    
    // Очищаем телефон от всего, кроме цифр и +
    $phone = preg_replace('/[^0-9+]/', '', $data['phone'] ?? '');
    if (empty($phone)) {
        $errors['phone'] = 'Телефон обязателен для заполнения';
    } elseif (!preg_match('/^(\+7|8)[0-9]{10}$/', $phone)) {
        $errors['phone'] = 'Введите номер в формате +7XXXXXXXXXX (11 цифр) или 8XXXXXXXXXX (11 цифр)';
    }
    
    $email = trim($data['email'] ?? '');
    if (empty($email)) {
        $errors['email'] = 'E-mail обязателен для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный E-mail';
    }
    
    $birth_date = trim($data['birth_date'] ?? '');
    if (empty($birth_date)) {
        $errors['birth_date'] = 'Дата рождения обязательна для заполнения';
    } else {
        $date_obj = DateTime::createFromFormat('Y-m-d', $birth_date);
        if (!$date_obj || $date_obj->format('Y-m-d') !== $birth_date) {
            $errors['birth_date'] = 'Неверный формат даты';
        }
    }
    
    $gender = $data['gender'] ?? '';
    if (empty($gender)) {
        $errors['gender'] = 'Выберите пол';
    } elseif (!in_array($gender, ['male', 'female'])) {
        $errors['gender'] = 'Некорректное значение пола';
    }
    
    if (empty($data['languages'] ?? [])) {
        $errors['languages'] = 'Выберите хотя бы один язык';
    }
    
    if (($data['contract_accepted'] ?? 0) != 1) {
        $errors['contract_accepted'] = 'Вы должны согласиться с контрактом';
    }
    
    return $errors;
}

$method = $_SERVER['REQUEST_METHOD'];

$input_data = [];
if ($method === 'POST' || $method === 'PUT') {
    $input_data = json_decode(file_get_contents('php://input'), true) ?? [];
    if (empty($input_data)) {
        $input_data = $_POST;
    }
}

try {
    if ($method === 'POST') {
        // ВСЕГДА СОЗДАЕМ НОВУЮ АНКЕТУ
        $errors = validateData($input_data);
        if (!empty($errors)) {
            sendResponse(['success' => false, 'errors' => $errors]);
        }
        
        $pdo->beginTransaction();
        $sql = "INSERT INTO " . table('applications') . " (full_name, phone, email, birth_date, gender, biography, contract_accepted) 
                VALUES (:full_name, :phone, :email, :birth_date, :gender, :biography, :contract_accepted)";
        $stmt = $pdo->prepare($sql);
        
        // Очищаем телефон перед сохранением
        $phone_clean = preg_replace('/[^0-9+]/', '', $input_data['phone']);
        
        $stmt->execute([
            ':full_name' => $input_data['full_name'],
            ':phone' => $phone_clean,
            ':email' => $input_data['email'],
            ':birth_date' => $input_data['birth_date'],
            ':gender' => $input_data['gender'],
            ':biography' => $input_data['biography'] ?? '',
            ':contract_accepted' => $input_data['contract_accepted'] ?? 0
        ]);
        $user_id = $pdo->lastInsertId();
        
        if (!empty($input_data['languages'])) {
            $langStmt = $pdo->prepare("SELECT id FROM " . table('programming_languages') . " WHERE name = ?");
            $linkStmt = $pdo->prepare("INSERT INTO " . table('application_languages') . " (application_id, language_id) VALUES (?, ?)");
            foreach ($input_data['languages'] as $lang_name) {
                $langStmt->execute([$lang_name]);
                $lang = $langStmt->fetch();
                if ($lang) {
                    $linkStmt->execute([$user_id, $lang['id']]);
                }
            }
        }
        
        $login = strtolower(preg_replace('/[^a-zA-Z]/', '', $input_data['full_name']));
        $login = substr($login, 0, 8) . '_' . rand(100, 999);
        $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%'), 0, 12);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $updateStmt = $pdo->prepare("UPDATE " . table('applications') . " SET login = ?, password_hash = ? WHERE id = ?");
        $updateStmt->execute([$login, $password_hash, $user_id]);
        $pdo->commit();
        
        // ОБНОВЛЯЕМ СЕССИЮ
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $input_data['full_name'];
        
        sendResponse([
            'success' => true,
            'message' => 'Пользователь создан',
            'data' => [
                'id' => $user_id,
                'login' => $login,
                'password' => $password,
                'profile_url' => "success.php?id=" . $user_id . "&login=" . urlencode($login) . "&password=" . urlencode($password)
            ]
        ]);
    }
    elseif ($method === 'PUT') {
        $user_id = $_SESSION['user_id'] ?? null;
        if (!$user_id) {
            sendResponse(['error' => 'Требуется авторизация']);
        }
        $errors = validateData($input_data);
        if (!empty($errors)) {
            sendResponse(['success' => false, 'errors' => $errors]);
        }
        $pdo->beginTransaction();
        $sql = "UPDATE " . table('applications') . " SET 
                full_name = :full_name, phone = :phone, email = :email, 
                birth_date = :birth_date, gender = :gender, 
                biography = :biography, contract_accepted = :contract_accepted
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        
        $phone_clean = preg_replace('/[^0-9+]/', '', $input_data['phone']);
        
        $stmt->execute([
            ':full_name' => $input_data['full_name'],
            ':phone' => $phone_clean,
            ':email' => $input_data['email'],
            ':birth_date' => $input_data['birth_date'],
            ':gender' => $input_data['gender'],
            ':biography' => $input_data['biography'] ?? '',
            ':contract_accepted' => $input_data['contract_accepted'] ?? 0,
            ':id' => $user_id
        ]);
        $pdo->prepare("DELETE FROM " . table('application_languages') . " WHERE application_id = ?")->execute([$user_id]);
        if (!empty($input_data['languages'])) {
            $langStmt = $pdo->prepare("SELECT id FROM " . table('programming_languages') . " WHERE name = ?");
            $linkStmt = $pdo->prepare("INSERT INTO " . table('application_languages') . " (application_id, language_id) VALUES (?, ?)");
            foreach ($input_data['languages'] as $lang_name) {
                $langStmt->execute([$lang_name]);
                $lang = $langStmt->fetch();
                if ($lang) {
                    $linkStmt->execute([$user_id, $lang['id']]);
                }
            }
        }
        $pdo->commit();
        sendResponse([
            'success' => true,
            'message' => 'Данные обновлены',
            'data' => ['id' => $user_id]
        ]);
    }
    else {
        sendResponse(['error' => 'Метод не поддерживается']);
    }
} catch (PDOException $e) {
    if (isset($pdo)) $pdo->rollBack();
    error_log("API Error: " . $e->getMessage());
    sendResponse(['error' => 'Ошибка базы данных']);
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    sendResponse(['error' => 'Ошибка сервера']);
}
?>