# Плагин автопостинга статей с биржи контента [Text.ru](https://text.ru)

> **Внимание!** 
>
> Поскольку конфигурации сайтов и их состав плагинов сильно различаются, по каким-то параметрам данный плагин 
> может быть несовместим именно с вашим сайтом. Поэтому перед установкой и использованием плагина обязательно 
> сделайте резервную копию файлов сайта, его базы данных и иные действия, характерные для вашего сайта, для 
> того чтобы можно было восстановить его работоспособность при возникновении любых непредвиденных ситуаций.
 

## Технические требования
Для успешной работы плагина необходимо:
- версия PHP 7.2 и выше;
- версия Wordpress 4.7 и выше;
- разрешение на создание таблиц БД для пользователя, от имени которого происходит подключение к базе данных сайта.

## Изменения, вносимые плагином
При установке плагина создаётся новая таблица базы данных ```wp_textru_orders``` для хранения временных данных. 
Её структуру можно посмотреть в файле ```text.ru.posting.php```.

Также добавляются три опции:
- textru_token: токен пользователя, полученный со страницы [https://text.ru/api-check]();
- textru_site_id: идентификатор сайта в системе [text.ru](https://text.ru);
- textru_customer_id: идентификатор владельца токена в системе [text.ru](https://text.ru);

## Принцип работы плагина
При работе плагин взаимодействует посредством открытого API с программной платформой [text.ru](https://text.ru).
И в процессе работы отправляет следующие данные:
- токен пользователя, полученный со страницы [https://text.ru/api-check]();
- url сайта;
- идентификатор сайта в системе [text.ru](https://text.ru);
- идентификатор владельца токена в системе [text.ru](https://text.ru);
- IP адрес сервера, на котором располагается ваш сайт;
- другие ранее полученные от text.ru данные - например, идентификаторы заказов, документов;

Плагин получает от программной платформы [text.ru](https://text.ru):
- данные по вашим заказам, для которых при создании заказа указан сайт;
- данные прикреплённых к заказам документов, включая их тексты;
- токен пользователя и идентификатор владельца токена (при указании нового токена или его изменении).

Плагин публикует выбранные документы заказа в черновики Wordpress.

## Разработка плагина
После внесения изменений в код плагина необходимо пересобрать файлы скриптов и стилей для фронтенда.
Для этого:
 - установите dev-зависимости командой ```npm install```
 - осуществите сборку файлов командой ```npm run build```
 - для отслеживания изменений в коде в режиме реального времени используйте команду ```npm run watch```

## Лицензия
Плагин распространяется по лицензии [MIT](LICENSE).
