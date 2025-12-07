<?php
class Base64ImageProcessor
{
    private $uploadDir = 'uploaded/images/';
    private $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    private $baseUrl = '/uploaded/images/'; // Базовый URL для доступа к изображениям

    public function __construct()
    {
        // Создаем директорию, если она не существует
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Обработка HTML-текста с base64 изображениями
     *
     * @param string $html Входной HTML текст
     * @return string Обработанный HTML с ссылками на изображения
     */
    public function processHtml($html)
    {
        // Ищем все base64 изображения в тексте
        $pattern = '/<img[^>]+src="data:image\/([^;]+);base64,([^">]+)"[^>]*>/i';

        return preg_replace_callback($pattern, function($matches) {
            $imageType = $matches[1];
            $base64Data = $matches[2];

            // Полный mime-type
            $mimeType = 'image/' . $imageType;

            // Проверяем тип изображения
            if (!in_array($mimeType, $this->allowedTypes)) {
                return $matches[0]; // Возвращаем оригинальный тег
            }

            // Декодируем base64
            $imageData = base64_decode($base64Data);

            if ($imageData === false) {
                return $matches[0]; // Возвращаем оригинальный тег при ошибке декодирования
            }

            // Генерируем уникальное имя файла
            $fileName = $this->generateFileName($imageType);
            $filePath = $this->uploadDir . $fileName;

            // Сохраняем изображение
            if (file_put_contents($filePath, $imageData)) {
                // Возвращаем тег с ссылкой на файл
                return '<img src="' . $this->baseUrl . $fileName . '" alt="" class="quill-image">';
            }

            return $matches[0]; // Возвращаем оригинальный тег при ошибке сохранения
        }, $html);
    }

    /**
     * Генерация уникального имени файла
     */
    private function generateFileName( $extension)
    {
        // Заменяем возможные "/" в расширении
        $extension = str_replace('/', '.', $extension);

        // Генерируем имя: timestamp_рандомноечисло.расширение
        return time() . '_' . uniqid() . '.' . $extension;
    }

    /**
     * Очистка старых файлов (опционально)
     */
    public function cleanOldFiles($daysOld = 30)
    {
        $files = glob($this->uploadDir . '*');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file)) {
                // Удаляем файлы старше $daysOld дней
                if ($now - filemtime($file) >= $daysOld * 86400) {
                    unlink($file);
                }
            }
        }
    }
}