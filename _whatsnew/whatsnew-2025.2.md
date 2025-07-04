##########################
##    Текущая версия    ##
##########################

<a id="20062025"></a>
# Версия - 2025.2 (Relise)
#### от 20.06.2025 (build 20062025)

## Исправления

- Плагины: CronManager, Экспорт сделок+ - коррекция
- Прайс: улучшен импорт файлов, созданных в LibreOffice
- Импорт Клиентов, Сделок - улучшен для поддержки файлов, созданных в LibreOffice
- Интеграция с Манго: исправлено прослушивание записи
- Бюджет: исправлена работа с расходами из карточки при обычной работе с поставщиками (если опция Расширенная работа с поставщиками не активна)
- Телефония: добавлена интеграция с Novafon (аналог Zadarma)
- Опция "Защита экспорта": корректировка работы
- Устранен баг, приводящий к отключению хуков плагинов при деактивации одного из них
- Карточка
  - клиента: восстановлена кнопку Удалить контакт в карточке клиента
  - клиента: убрана ссылка на печать карточки
  - контакта: корректировка поведения при редактировании контакта в части добавления писем в историю
- Прайс 
  - возможность задать максимальный уровень вложенности категорий через параметр $maxPriceFolderLevel (прописывается в /inc/config.php). По умолчанию $maxPriceFolderLevel = 3
- Файлы 
  - возможность задать максимальный уровень вложенности категорий через параметр $maxFileFolderLevel (прописывается в /inc/config.php). По умолчанию $maxFileFolderLevel = 3
- Напоминания
  - устранена проблема с редактированием напоминания с одним участником
- Загрузка файлов из карточки Клиента: исправлена привязка к клиенту
- Подключение плагинов: исправлено применение хуков плагина при установке
- Бюджет:
  - подробный лог по изменениям
  - исправлены уведомления в соответствие с подписками юзера
  - исправлена начальная сумма для расчета движения денег в отчете Денежный поток
  - просмотр расхода - отображение автора
- Проекты: устранена ошибка работы в разделе Диаграмма Гантта
- Добавление сделки вместе с созданием записи Клиента: исправление ошибки
- Прайс-лист:
  - возможность добавить любое кол-во уровней цен
  - возможность произвести пересчет всех позиций прайса при редактировании уровня цен
  - возможность указать, какие уровни цен будут отображены в колонках раздела прайс
  - дополнительные возможности для разработчиков с помощью Хуков и фильтров
- Редактор Контрольных точек - исправление при добавлении и упорядочивwании
- Корпоративный университет:
  - поддержка вложенности категорий (4 уровня)
  - поддержка прикрепления загруженных файлов к материалу
- Файлы:
  - добавлено групповое действие по переносу файлов по папкам
  - исправлено групповое удаление
- Субмодуль "Explorer":
  - панель выбора загруженных файлов
- Исправлено сохранение настроек интеграции с Дадата
- Отчеты
  - добавлены 2 отчета по источникам клиентов в новых сделках
  - исправлены найденные ошибки в отчетах

>
> <b class="red">Важно</b>
> - Рекомендуется выполнять обновление через терминал, если размер базы данных значителен, т.к. вносятся изменения в её структуру
> ```php
> php /path_to_crm/_install/update.php
> ```
>

## При наличии проблем

>
> <b class="red">Рекомендуем</b>
> - При проблемах с добавлением записей Клиентов, Контактов, Сделок рекомендуется выполнить скрипт
>   - из консоли:
> ```php
> php /path_to_crm/_install/fix_default_values.php
> ```
>    - или из браузера:
> ```html
> https://адрес_црм/_install/fix_default_values.php
> ```
>