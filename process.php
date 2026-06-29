<?php
// process.php - Обработчик формы (если JavaScript выключен)
require_once 'config.php';
session_start();

// ===== ПРИНИМАЕМ POST =====
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST') {
    $input_data = $_POST;
} else {
    $input_data = $_GET;
}

$full_name = trim($input_data['full_name'] ?? '');
$phone = trim($input_data['phone'] ?? '');
$email = trim($input_data['email'] ?? '');
$birth_date = trim($input_data['birth_date'] ?? '');
$gender = $input_data['gender'] ?? '';
$languages = $input_data['languages'] ?? [];
$biography = trim($input_data['biography'] ?? '');
$contract_accepted = isset($input_data['contract_accepted']) ? 1 : 0;
$edit_id = isset($input_data['edit_id']) && is_numeric($input_data['edit_id']) ? (int)$input_data['edit_id'] : 0;

$errors = [];

// ===== ВАЛИДАЦИЯ =====
if (empty($full_name)) {
    $errors['full_name'] = "ФИО обязательно для заполнения";
} elseif (strlen($full_name) > 150) {
    $errors['full_name'] = "ФИО не должно превышать 150 символов";
} elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $full_name)) {
    $errors['full_name'] = "ФИО может содержать только буквы, пробелы и дефис";
}

// Очищаем телефон от всего, кроме цифр и +
$phone_clean = preg_replace('/[^0-9+]/', '', $phone);
if (empty($phone_clean)) {
    $errors['phone'] = "Телефон обязателен для заполнения";
} elseif (!preg_match('/^(\+7|8)[0-9]{10}$/', $phone_clean)) {
    $errors['phone'] = "Введите номер в формате +7XXXXXXXXXX (11 цифр) или 8XXXXXXXXXX (11 цифр)";
}

if (empty($email)) {
    $errors['email'] = "E-mail обязателен для заполнения";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = "Введите корректный E-mail";
}

if (empty($birth_date)) {
    $errors['birth_date'] = "Дата рождения обязательна для заполнения";
} else {
    $date_obj = DateTime::createFromFormat('Y-m-d', $birth_date);
    if (!$date_obj || $date_obj->format('Y-m-d') !== $birth_date) {
        $errors['birth_date'] = "Неверный формат даты";
    }
}

if (empty($gender)) {
    $errors['gender'] = "Выберите пол";
} elseif (!in_array($gender, ['male', 'female'])) {
    $errors['gender'] = "Некорректное значение пола";
}

if (empty($languages)) {
    $errors['languages'] = "Выберите хотя бы один язык";
}

if (!$contract_accepted) {
    $errors['contract_accepted'] = "Вы должны согласиться с контрактом";
}

// ===== ЕСЛИ ЕСТЬ ОШИБКИ =====
if (!empty($errors)) {
    // Сохраняем ошибки и данные в Cookies
    foreach ($errors as $field => $message) {
        setcookie("error_$field", $message, 0, '/');
    }
    foreach ($input_data as $key => $value) {
        if (is_array($value)) {
            setcookie("form_$key", implode(',', $value), 0, '/');
        } else {
            setcookie("form_$key", $value, 0, '/');
        }
    }
    header("Location: index.php");
    exit;
}

// ===== СОХРАНЕНИЕ В БД =====
try {
    $check = $pdo->query("SHOW TABLES LIKE '" . table('applications') . "'");
    if ($check->rowCount() == 0) {
        throw new Exception("Таблица '" . table('applications') . "' не существует!");
    }
    
    $pdo->beginTransaction();
    
    $isEdit = ($edit_id > 0 && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $edit_id);
    
    if ($isEdit) {
        $sql = "UPDATE " . table('applications') . " SET 
                full_name = :full_name,
                phone = :phone,
                email = :email,
                birth_date = :birth_date,
                gender = :gender,
                biography = :biography,
                contract_accepted = :contract_accepted
                WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':full_name' => $full_name,
            ':phone' => $phone_clean,
            ':email' => $email,
            ':birth_date' => $birth_date,
            ':gender' => $gender,
            ':biography' => $biography,
            ':contract_accepted' => $contract_accepted,
            ':id' => $edit_id
        ]);
        $application_id = $edit_id;
        $pdo->prepare("DELETE FROM " . table('application_languages') . " WHERE application_id = :id")->execute([':id' => $edit_id]);
    } else {
        $sql = "INSERT INTO " . table('applications') . " (full_name, phone, email, birth_date, gender, biography, contract_accepted) 
                VALUES (:full_name, :phone, :email, :birth_date, :gender, :biography, :contract_accepted)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':full_name' => $full_name,
            ':phone' => $phone_clean,
            ':email' => $email,
            ':birth_date' => $birth_date,
            ':gender' => $gender,
            ':biography' => $biography,
            ':contract_accepted' => $contract_accepted
        ]);
        $application_id = $pdo->lastInsertId();
        
        $login = strtolower(preg_replace('/[^a-zA-Z]/', '', $full_name));
        $login = substr($login, 0, 8) . '_' . rand(100, 999);
        $password = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%'), 0, 12);
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $updateStmt = $pdo->prepare("UPDATE " . table('applications') . " SET login = :login, password_hash = :hash WHERE id = :id");
        $updateStmt->execute([
            ':login' => $login,
            ':hash' => $password_hash,
            ':id' => $application_id
        ]);
        
        $_SESSION['user_id'] = $application_id;
        $_SESSION['user_name'] = $full_name;
    }
    
    if (!empty($languages)) {
        $langStmt = $pdo->prepare("SELECT id FROM " . table('programming_languages') . " WHERE name = :name");
        $linkStmt = $pdo->prepare("INSERT INTO " . table('application_languages') . " (application_id, language_id) VALUES (:app_id, :lang_id)");
        foreach ($languages as $lang_name) {
            $langStmt->execute([':name' => $lang_name]);
            $langRow = $langStmt->fetch();
            if ($langRow) {
                $linkStmt->execute([
                    ':app_id' => $application_id,
                    ':lang_id' => $langRow['id']
                ]);
            }
        }
    }
    
    $pdo->commit();
    
    foreach (['full_name', 'phone', 'email', 'birth_date', 'gender', 'languages', 'biography', 'contract_accepted'] as $field) {
        setcookie("error_$field", "", time() - 3600, '/');
    }
    
    header("Location: success.php?id=" . $application_id . "&login=" . urlencode($login ?? '') . "&password=" . urlencode($password ?? ''));
    exit;
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Database error: " . $e->getMessage());
    header("Location: index.php?error=db");
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("General error: " . $e->getMessage());
    header("Location: index.php?error=general");
    exit;
}
?>