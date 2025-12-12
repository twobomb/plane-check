<?php
class OneTimeToken {
    private $storagePath = __DIR__ . '/../tokens/';
    private $secret = 'k5o4phkpzkhpo4h54$54o54hjhjyh';

    public function __construct() {
        // Создаем директорию для хранения токенов, если её нет
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }

    public function generateToken($data = [], $maxUses = 1, $expiryMinutes = 60) {
        $token = bin2hex(random_bytes(16));
        $tokenId = hash_hmac('sha256', $token, $this->secret);

        $tokenData = [
            'id' => $tokenId,
            'token' => $token,
            'created' => time(),
            'expires' => time() + ($expiryMinutes * 60),
            'max_uses' => $maxUses,
            'used' => 0,
            'data' => $data,
            'ip_restriction' => $_SERVER['REMOTE_ADDR'] // Опционально
        ];

        // Сохраняем в файл (в реальном приложении используйте БД)
        file_put_contents(
            $this->storagePath . $tokenId . '.json',
            json_encode($tokenData)
        );

        return $token;
    }

    public function validateToken($token) {
        $tokenId = hash_hmac('sha256', $token, $this->secret);
        $filePath = $this->storagePath . $tokenId . '.json';

        if (!file_exists($filePath)) {
            return false;
        }

        $tokenData = json_decode(file_get_contents($filePath), true);

        // Проверяем время жизни
        if ($tokenData['expires'] < time()) {
            unlink($filePath); // Удаляем просроченный токен
            return false;
        }

        // Проверяем количество использований
        if ($tokenData['used'] >= $tokenData['max_uses']) {
            unlink($filePath);
            return false;
        }

        // Увеличиваем счетчик использований
        $tokenData['used']++;
        file_put_contents($filePath, json_encode($tokenData));

        return $tokenData['data'];
    }
}
/*
// Использование
$otp = new OneTimeToken();

// Генерация токена для однократного использования
$token = $otp->generateToken([
    'user_id' => 123,
    'action' => 'import_data'
], 1, 15); // Однократное использование, 15 минут жизни

echo "Одноразовый токен: " . $token . "\n";

// Валидация
$data = $otp->validateToken($token);
if ($data) {
    echo "Доступ разрешен. Данные: ";
    print_r($data);
} else {
    echo "Доступ запрещен";
}
*/?>