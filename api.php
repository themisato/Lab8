<?php
// api.php - Веб-сервис
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// ===== ВАЖНО: СНАЧАЛА ПОДКЛЮЧАЕМ CONFIG, ПОТОМ СЕССИЮ =====
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
    
    $phone = preg_replace('/[^0-9+]/', '', $data['phone'] ?? '');
    if (empty($phone)) {
        $errors['phone'] = 'Телефон обязателен для заполнения';
    } elseif (!preg_match('/^(\+7|8)[0-9]{10}$/', $phone)) {
        $errors['phone'] = 'Телефон должен быть в формате +7XXXXXXXXXX или 8XXXXXXXXXX';
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
$user_id = $_SESSION['user_id'] ?? null;
$is_auth = isset($user_id);

$input_data = [];
if ($method === 'POST' || $method === 'PUT') {
    $input_data = json_decode(file_get_contents('php://input'), true) ?? [];
    if (empty($input_data)) {
        $input_data = $_POST;
    }
}

try {
    if ($method === 'GET') {
        if (!$is_auth) {
            sendResponse(['error' => 'Требуется авторизация']);
        }
        $stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        if (!$user) {
            sendResponse(['error' => 'Пользователь не найден']);
        }
        $langStmt = $pdo->prepare("SELECT pl.name FROM application_languages al JOIN programming_languages pl ON al.language_id = pl.id WHERE al.application_id = ?");
        $langStmt->execute([$user_id]);
        $languages = $langStmt->fetchAll(PDO::FETCH_COLUMN);
        sendResponse([
            'success' => true,
            'data' => [
                'id' => $user['id'],
                'full_name' => $user['full_name'],
                'phone' => $user['phone'],
                'email' => $user['email'],
                'birth_date' => $user['birth_date'],
                'gender' => $user['gender'],
                'biography' => $user['biography'],
                'languages' => $languages,
                'contract_accepted' => (bool)$user['contract_accepted']
            ]
        ]);
    }
    elseif ($method === 'POST') {
        if ($is_auth) {
            sendResponse(['error' => 'Вы уже авторизованы. Используйте PUT для обновления']);
        }
        $errors = validateData($input_data);
        if (!empty($errors)) {
            sendResponse(['success' => false, 'errors' => $errors]);
        }
        $pdo->beginTransaction();
        $sql = "INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, contract_accepted) 
                VALUES (:full_name, :phone, :email, :birth_date, :gender, :biography, :contract_accepted)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':full_name' => $input_data['full_name'],
            ':phone' => preg_replace('/[^0-9+]/', '', $input_data['phone']),
            ':email' => $input_data['email'],
            ':birth_date' => $input_data['birth_date'],
            ':gender' => $input_data['gender'],
            ':biography' => $input_data['biography'] ?? '',
            ':contract_accepted' => $input_data['contract_accepted'] ?? 0
        ]);
        $user_id = $pdo->lastInsertId();
        if (!empty($input_data['languages'])) {
            $langStmt = $pdo->prepare("SELECT id FROM programming_languages WHERE name = ?");
            $linkStmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
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
        $updateStmt = $pdo->prepare("UPDATE applications SET login = ?, password_hash = ? WHERE id = ?");
        $updateStmt->execute([$login, $password_hash, $user_id]);
        $pdo->commit();
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
        if (!$is_auth) {
            sendResponse(['error' => 'Требуется авторизация']);
        }
        $errors = validateData($input_data);
        if (!empty($errors)) {
            sendResponse(['success' => false, 'errors' => $errors]);
        }
        $pdo->beginTransaction();
        $sql = "UPDATE applications SET 
                full_name = :full_name, phone = :phone, email = :email, 
                birth_date = :birth_date, gender = :gender, 
                biography = :biography, contract_accepted = :contract_accepted
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':full_name' => $input_data['full_name'],
            ':phone' => preg_replace('/[^0-9+]/', '', $input_data['phone']),
            ':email' => $input_data['email'],
            ':birth_date' => $input_data['birth_date'],
            ':gender' => $input_data['gender'],
            ':biography' => $input_data['biography'] ?? '',
            ':contract_accepted' => $input_data['contract_accepted'] ?? 0,
            ':id' => $user_id
        ]);
        $pdo->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$user_id]);
        if (!empty($input_data['languages'])) {
            $langStmt = $pdo->prepare("SELECT id FROM programming_languages WHERE name = ?");
            $linkStmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
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
    sendResponse(['error' => 'Ошибка базы данных: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    sendResponse(['error' => 'Ошибка сервера: ' . $e->getMessage()]);
}
?>