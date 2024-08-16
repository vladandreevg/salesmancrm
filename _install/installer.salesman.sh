#!/bin/bash

# Установщик пакета streaman
# chmod 770 installer.salesman.sh

# Скачиваем файл
echo "Скачиваем дистрибутив из репозитория..."
curl -L https://salesman.pro/download/getfile.php -o install.zip

# Проверяю статус скачивания
if [ $? -ne 0 ]; then
  echo "Ошибка при скачивании файла!"
  exit 1
fi

# Распаковываю zip-архив
echo "Распаковываем zip-архив..."
unzip -o -q install.zip
if [ $? -ne 0 ]; then
  echo "Ошибка при распаковке zip-архива!"
  exit 1
fi

# меняем права у папок и файлов на дефолтные
find . -type d -exec chmod 755 -R {} \;
find . -type f -exec chmod 644 -R {} \;


# Очистка
echo "Удаляем скачанный файл..."
rm install.zip

echo "Готово! Файл распакован"
echo "Открой хост в браузере и заверши установку"