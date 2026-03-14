<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$db_user = 'u82414';
$db_pass = '7011793';
$db_name = 'u82414';
$db_host = 'localhost';

try {
    $db = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

$user_id = $_SESSION['user_id'];
$errors = [];
$success_message = '';

$langs_stmt = $db->query("SELECT id, name FROM programming_languages ORDER BY name");
$all_languages = $langs_stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user_data) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$langs_stmt = $db->prepare("SELECT language_id FROM application_languages WHERE application_id = ?");
$langs_stmt->execute([$user_id]);
$user_langs = $langs_stmt->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullName = trim($_POST['fullName'] ?? '');
    if (empty($fullName)) {
        $errors['fullName'] = 'ФИО обязательно для заполнения';
    } elseif (strlen($fullName) > 150) {
        $errors['fullName'] = 'ФИО не должно превышать 150 символов';
    } elseif (!preg_match('/^[а-яёА-ЯЁa-zA-Z\s\-]+$/u', $fullName)) {
        $errors['fullName'] = 'ФИО может содержать только буквы, пробелы и дефис';
    }
    
    $email = trim($_POST['email'] ?? '');
    if (empty($email)) {
        $errors['email'] = 'Email обязателен для заполнения';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Введите корректный email';
    }
    
    $phone = trim($_POST['phone'] ?? '');
    if (!empty($phone) && !preg_match('/^[\+\d\s\-\(\)]{10,20}$/', $phone)) {
        $errors['phone'] = 'Телефон может содержать только цифры, пробелы, дефисы, скобки и знак +';
    }
    
    $birth = $_POST['birth'] ?? '';
    if (!empty($birth)) {
        $date_parts = explode('-', $birth);
        if (!checkdate($date_parts[1] ?? 1, $date_parts[2] ?? 1, $date_parts[0] ?? 1)) {
            $errors['birth'] = 'Введите корректную дату рождения';
        }
    }
    
    $gender = $_POST['gender'] ?? '';
    if (!in_array($gender, ['male', 'female'])) {
        $errors['gender'] = 'Выберите корректное значение пола';
    }
    
    $selected_langs = $_POST['langs'] ?? [];
    if (!is_array($selected_langs) || empty($selected_langs)) {
        $errors['langs'] = 'Выберите хотя бы один язык программирования';
    } else {
        $valid_ids = array_column($all_languages, 'id');
        foreach ($selected_langs as $lang_id) {
            if (!in_array((int)$lang_id, $valid_ids)) {
                $errors['langs'] = 'Выбран недопустимый язык';
                break;
            }
        }
    }
    
    $bio = trim($_POST['bio'] ?? '');
    
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            $update = $db->prepare("
                UPDATE applications 
                SET full_name = ?, email = ?, phone = ?, birth_date = ?, 
                    gender = ?, bio = ?
                WHERE id = ?
            ");
            $update->execute([
                $fullName, $email, $phone ?: null, $birth ?: null,
                $gender, $bio ?: null, $user_id
            ]);
            
            $del = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
            $del->execute([$user_id]);
            
            $insert = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
            foreach ($selected_langs as $lang_id) {
                $insert->execute([$user_id, $lang_id]);
            }
            
            $db->commit();
            $success_message = '✅ Данные успешно обновлены!';
            
            $user_data['full_name'] = $fullName;
            $user_data['email'] = $email;
            $user_data['phone'] = $phone;
            $user_data['birth_date'] = $birth;
            $user_data['gender'] = $gender;
            $user_data['bio'] = $bio;
            $user_langs = $selected_langs;
            
        } catch (PDOException $e) {
            $db->rollBack();
            $errors['database'] = 'Ошибка при обновлении';
        }
    }
}

$edit_mode = true;
include('form.php');
?>