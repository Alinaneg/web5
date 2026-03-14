<?php
$is_edit = isset($edit_mode) && $edit_mode === true;
$action_url = $is_edit ? 'edit.php' : 'index.php';
$button_text = $is_edit ? 'Сохранить изменения' : 'Отправить';
$title_prefix = $is_edit ? 'Редактирование' : ' Анкета';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $is_edit ? 'Редактирование' : 'Анкета' ?> </title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php if ($success_message): ?>
        <div class="message success" style="display: block; max-width: 750px; margin: 20px auto;">
            <?= $success_message ?>
        </div>
    <?php endif; ?>
    
    <?php if ($is_edit): ?>
    <div style="max-width: 750px; margin: 0 auto 20px; display: flex; justify-content: flex-end;">
        <a href="logout.php" style="background: #f56565; color: white; padding: 8px 16px; border-radius: 50px; text-decoration: none;">Выйти</a>
    </div>
    <?php endif; ?>
    
    <div class="form-card">
        <h1 class="form-title"><?= $title_prefix ?></h1>
        
        <form method="POST" action="<?= $action_url ?>" novalidate>
            <div class="form-row">
                <div class="form-group">
                    <label for="fullName">ФИО *</label>
                    <input type="text" id="fullName" name="fullName" required
                           value="<?= htmlspecialchars($form_data['fullName'] ?? $user_data['full_name'] ?? '') ?>"
                           class="<?= isset($errors['fullName']) ? 'error-field' : '' ?>">
                    <?php if (isset($errors['fullName'])): ?>
                        <small class="error-hint">❌ <?= $errors['fullName'] ?></small>
                    <?php else: ?>
                        <small class="hint">Допустимы: буквы, пробелы, дефис</small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required
                           value="<?= htmlspecialchars($form_data['email'] ?? $user_data['email'] ?? '') ?>"
                           class="<?= isset($errors['email']) ? 'error-field' : '' ?>">
                    <?php if (isset($errors['email'])): ?>
                        <small class="error-hint">❌ <?= $errors['email'] ?></small>
                    <?php else: ?>
                        <small class="hint">Формат: name@domain.com</small>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input type="tel" id="phone" name="phone" placeholder="+7 (999) 123-45-67"
                           value="<?= htmlspecialchars($form_data['phone'] ?? $user_data['phone'] ?? '') ?>"
                           class="<?= isset($errors['phone']) ? 'error-field' : '' ?>">
                    <?php if (isset($errors['phone'])): ?>
                        <small class="error-hint">❌ <?= $errors['phone'] ?></small>
                    <?php else: ?>
                        <small class="hint">Допустимы: цифры, +, пробелы, скобки, дефис</small>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="birth">Дата рождения</label>
                    <input type="date" id="birth" name="birth"
                           value="<?= htmlspecialchars($form_data['birth'] ?? $user_data['birth_date'] ?? '') ?>"
                           class="<?= isset($errors['birth']) ? 'error-field' : '' ?>">
                    <?php if (isset($errors['birth'])): ?>
                        <small class="error-hint">❌ <?= $errors['birth'] ?></small>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-group">
                <label>Пол</label>
                <div class="radio-group">
                    <label class="radio-label">
                        <input type="radio" name="gender" value="male" 
                               <?= 
                                   (isset($form_data['gender']) && $form_data['gender'] == 'male') ||
                                   (!isset($form_data['gender']) && (!isset($user_data['gender']) || $user_data['gender'] == 'male')) 
                                   ? 'checked' : '' 
                               ?>>
                        Мужской
                    </label>
                    <label class="radio-label">
                        <input type="radio" name="gender" value="female"
                               <?= 
                                   (isset($form_data['gender']) && $form_data['gender'] == 'female') ||
                                   (isset($user_data['gender']) && $user_data['gender'] == 'female' && !isset($form_data['gender']))
                                   ? 'checked' : '' 
                               ?>>
                        Женский
                    </label>
                </div>
                <?php if (isset($errors['gender'])): ?>
                    <small class="error-hint">❌ <?= $errors['gender'] ?></small>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="langs">Любимые языки программирования *</label>
                <select id="langs" name="langs[]" multiple size="4"
                        class="<?= isset($errors['langs']) ? 'error-field' : '' ?>">
                    <?php 
                    if ($is_edit && !isset($form_data['langs'])) {
                        $selected_langs = $user_langs ?? [];
                    } else {
                        $selected_langs = $form_data['langs'] ?? [];
                    }
                    
                    foreach ($all_languages as $lang): 
                    ?>
                        <option value="<?= $lang['id'] ?>" 
                            <?= in_array($lang['id'], $selected_langs) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($lang['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="hint">Зажмите Ctrl/Cmd для выбора нескольких</small>
                <?php if (isset($errors['langs'])): ?>
                    <small class="error-hint">❌ <?= $errors['langs'] ?></small>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="bio">Биография</label>
                <textarea id="bio" name="bio" rows="3" placeholder="Расскажите о себе..."><?= htmlspecialchars($form_data['bio'] ?? $user_data['bio'] ?? '') ?></textarea>
            </div>
            
            <?php if (!$is_edit): ?>
            <div class="checkbox-group">
                <input type="checkbox" id="contract" name="contract" required
                       <?= isset($form_data['contract']) ? 'checked' : '' ?>
                       class="<?= isset($errors['contract']) ? 'error-field' : '' ?>">
                <label for="contract">С контрактом ознакомлен(а) *</label>
            </div>
            <?php if (isset($errors['contract'])): ?>
                <small class="error-hint" style="display: block; margin-top: -10px; margin-bottom: 10px;">❌ <?= $errors['contract'] ?></small>
            <?php endif; ?>
            
            <div class="checkbox-group">
                <input type="checkbox" id="consent" name="consent" required
                       <?= isset($form_data['consent']) ? 'checked' : '' ?>
                       class="<?= isset($errors['consent']) ? 'error-field' : '' ?>">
                <label for="consent">Я согласен на обработку персональных данных *</label>
            </div>
            <?php if (isset($errors['consent'])): ?>
                <small class="error-hint" style="display: block; margin-top: -10px; margin-bottom: 10px;">❌ <?= $errors['consent'] ?></small>
            <?php endif; ?>
            <?php endif; ?>
            
            <button type="submit" class="btn-submit"><?= $button_text ?></button>
        </form>
        
        <?php if (!$is_edit): ?>
        <div style="text-align: center; margin-top: 20px; padding-top: 20px; border-top: 2px dashed #e2e8f0;">
            <p>Уже есть логин и пароль? <a href="login.php" style="color: #38a169; font-weight: 600; text-decoration: none;">Войти в личный кабинет</a></p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
