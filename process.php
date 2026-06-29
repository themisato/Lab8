<?php
// process.php - Обработчик формы
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once 'config.php';
session_start();

function setError($field, $message) {
    setcookie("error_$field", $message, time() + 60, '/');
}

function saveFormData($data) {
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $clean_value = array_map(function($item) {
                return h($item);
            }, $value);
            setcookie("form_$key", implode(',', $clean_value), time() + 3600, '/');
        } else {
            setcookie("form_$key", h($value), time() + 3600, '/');
        }
    }
}

$full_name = trim($_GET['full_name'] ?? '');
$phone = trim($_GET['phone'] ?? '');
$email = trim($_GET['email'] ?? '');
$birth_date = trim($_GET['birth_date'] ?? '');
$gender = $_GET['gender'] ?? '';
$languages = $_GET['languages'] ?? [];
$biography = trim($_GET['biography'] ?? '');
$contract_accepted = isset($_GET['contract_accepted']) ? 1 : 0;
$edit_id = isset($_GET['edit_id']) && is_numeric($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;

$errors = [];

if (empty($full_name)) {
    $errors['full_name'] = "ФИО обязательно для заполнения";
} elseif (strlen($full_name) > 150) {
    $errors['full_name'] = "ФИО не должно превышать 150 символов";
} elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $full_name)) {
    $errors['full_name'] = "ФИО может содержать только буквы, пробелы и дефис";
}

$phone_clean = preg_replace('/[^0-9+]/', '', $phone);
if (empty($phone_clean)) {
    $errors['phone'] = "Телефон обязателен для заполнения";
} elseif (!preg_match('/^(\+7|8)[0-9]{10}$/', $phone_clean)) {
    $errors['phone'] = "Телефон должен быть в формате +7XXXXXXXXXX или 8XXXXXXXXXX";
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

if (!empty($errors)) {
    saveFormData($_GET);
    foreach ($errors as $field => $message) {
        setcookie("error_$field", $message, 0, '/');
    }
    header("Location: index.html");
    exit;
}

try {
    $check = $pdo->query("SHOW TABLES LIKE 'applications'");
    if ($check->rowCount() == 0) {
        throw new Exception("Таблица 'applications' не существует!");
    }
    
    $pdo->beginTransaction();
    
    $isEdit = ($edit_id > 0 && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $edit_id);
    
    if ($isEdit) {
        $sql = "UPDATE applications SET 
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
        $pdo->prepare("DELETE FROM application_languages WHERE application_id = :id")->execute([':id' => $edit_id]);
    } else {
        $sql = "INSERT INTO applications (full_name, phone, email, birth_date, gender, biography, contract_accepted) 
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
        
        $updateStmt = $pdo->prepare("UPDATE applications SET login = :login, password_hash = :hash WHERE id = :id");
        $updateStmt->execute([
            ':login' => $login,
            ':hash' => $password_hash,
            ':id' => $application_id
        ]);
        
        $_SESSION['user_id'] = $application_id;
        $_SESSION['user_name'] = $full_name;
    }
    
    if (!empty($languages)) {
        $langStmt = $pdo->prepare("SELECT id FROM programming_languages WHERE name = :name");
        $linkStmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (:app_id, :lang_id)");
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
    header("Location: index.html?error=db");
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("General error: " . $e->getMessage());
    header("Location: index.html?error=general");
    exit;
}
?>