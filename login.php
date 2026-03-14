<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

// Если уже авторизован, редирект на редактирование
if (isset($_SESSION['user_id'])) {
    header('Location: edit.php');
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

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($login) || empty($password)) {
        $error = 'Введите логин и пароль';
    } else {
        // Ищем пользователя по логину
        $stmt = $db->prepare("SELECT * FROM applications WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password_hash'])) {
            // Успешный вход
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_login'] = $user['login'];
            $_SESSION['user_name'] = $user['full_name'];
            
            // Перенаправляем на редактирование
            header('Location: edit.php');
            exit();
        } else {
            $error = 'Неверный логин или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в личный кабинет</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 0 auto;
        }
        .register-link {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #e2e8f0;
        }
        .register-link a {
            color: #38a169;
            font-weight: 600;
            text-decoration: none;
        }
        .register-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="form-card login-container">
        <h1 class="form-title">🔐 Вход в личный кабинет</h1>
        
        <?php if ($error): ?>
            <div class="message error" style="display: block; margin-bottom: 20px;">
                ❌ <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="login">Логин</label>
                <input type="text" id="login" name="login" required 
                       value="<?= htmlspecialchars($_POST['login'] ?? '') ?>"
                       placeholder="Введите логин">
            </div>
            
            <div class="form-group">
                <label for="password">Пароль</label>
                <input type="password" id="password" name="password" required 
                       placeholder="Введите пароль">
            </div>
            
            <button type="submit" class="btn-submit">Войти</button>
        </form>
        
        <div class="register-link">
            <p>Ещё нет логина и пароля? <a href="index.php">Заполните анкету</a></p>
            <p style="font-size: 0.9rem; color: #718096; margin-top: 10px;">
                После отправки анкеты вы получите логин и пароль
            </p>
        </div>
    </div>
</body>
</html>