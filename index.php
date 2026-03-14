<?php
header('Content-Type: text/html; charset=UTF-8');

$db_user = 'u82414';
$db_pass = '7011793'; 
$db_name = 'u82414';
$db_host = 'localhost';

try {
    $db = new PDO(
        "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass,
        [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

$langs_stmt = $db->query("SELECT id, name FROM programming_languages ORDER BY name");
$all_languages = $langs_stmt->fetchAll(PDO::FETCH_ASSOC);

$errors = [];
$form_data = [];
$success_message = '';

if (isset($_COOKIE['saved_data']) && empty($_POST)) {
    $form_data = json_decode($_COOKIE['saved_data'], true);
}


if (isset($_COOKIE['form_errors'])) {
    $errors = json_decode($_COOKIE['form_errors'], true);
    setcookie('form_errors', '', time() - 3600); 
}

if (isset($_COOKIE['form_data'])) {
    $form_data = json_decode($_COOKIE['form_data'], true);
    setcookie('form_data', '', time() - 3600); 
}

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
        $errors['email'] = 'Введите корректный email (пример: name@domain.com)';
    }
    

    $phone = trim($_POST['phone'] ?? '');
    if (!empty($phone) && !preg_match('/^[\+\d\s\-\(\)]{10,20}$/', $phone)) {
        $errors['phone'] = 'Телефон может содержать только цифры, пробелы, дефисы, скобки и знак +';
    }

    $birth = $_POST['birth'] ?? '';
    if (!empty($birth)) {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth)) {
            $errors['birth'] = 'Неверный формат даты';
        } else {
            $date_parts = explode('-', $birth);
            if (!checkdate($date_parts[1], $date_parts[2], $date_parts[0])) {
                $errors['birth'] = 'Введите корректную дату рождения';
            }
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
                $errors['langs'] = 'Выбран недопустимый язык программирования';
                break;
            }
        }
    }
    
    $bio = trim($_POST['bio'] ?? '');
    
    if (!isset($_POST['contract'])) {
        $errors['contract'] = 'Необходимо подтвердить ознакомление с контрактом';
    }
    
    if (!isset($_POST['consent'])) {
        $errors['consent'] = 'Необходимо согласие на обработку персональных данных';
    }
    

    if (!empty($errors)) {
        setcookie('form_errors', json_encode($errors), 0); 
        setcookie('form_data', json_encode($_POST), 0);
        
        header('Location: index.php');
        exit();
    }
    
    try {
        $db->beginTransaction();
        
        $login = 'user_' . rand(1000, 9999);
        $password = bin2hex(random_bytes(4)); 
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("
            INSERT INTO applications 
            (full_name, email, phone, birth_date, gender, bio, contract_agreed, data_consent, login, password_hash)
            VALUES 
            (:full_name, :email, :phone, :birth_date, :gender, :bio, :contract, :consent, :login, :password_hash)
        ");
        
        $stmt->execute([
            ':full_name' => $fullName,
            ':email' => $email,
            ':phone' => $phone ?: null,
            ':birth_date' => $birth ?: null,
            ':gender' => $gender,
            ':bio' => $bio ?: null,
            ':contract' => 1,
            ':consent' => 1,
            ':login' => $login,
            ':password_hash' => $password_hash
        ]);
        
        $application_id = $db->lastInsertId();
        $lang_stmt = $db->prepare("
            INSERT INTO application_languages (application_id, language_id)
            VALUES (:app_id, :lang_id)
        ");
        
        foreach ($selected_langs as $lang_id) {
            $lang_stmt->execute([
                ':app_id' => $application_id,
                ':lang_id' => $lang_id
            ]);
        }
        
        $db->commit();
        
        $saved_data = [
            'fullName' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'birth' => $birth,
            'gender' => $gender,
            'bio' => $bio
        ];
        setcookie('saved_data', json_encode($saved_data), time() + 365*24*60*60); // на год
        
        header('Location: index.php?save=1&login=' . urlencode($login) . '&password=' . urlencode($password));
        exit();
        
    } catch (PDOException $e) {
        $db->rollBack();
        $errors['database'] = 'Ошибка при сохранении в БД';
        setcookie('form_errors', json_encode(['database' => 'Ошибка базы данных']), 0);
        header('Location: index.php');
        exit();
    }
}

if (isset($_GET['save']) && $_GET['save'] == 1) {
    if (isset($_GET['login']) && isset($_GET['password'])) {
        $login = htmlspecialchars($_GET['login']);
        $password = htmlspecialchars($_GET['password']);
        $success_message = "✅ Данные успешно сохранены!
                           <div class='login-info'>
                               <strong>🔑 Логин:</strong> $login<br>
                               <strong>🔐 Пароль:</strong> $password<br>
                               <small>Сохраните эти данные для входа в личный кабинет</small>
                           </div>";
    } else {
        $success_message = '✅ Данные успешно сохранены!';
    }
}

$edit_mode = false; 
include('form.php');
?>