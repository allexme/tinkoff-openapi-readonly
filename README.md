# Тинькофф Инвестиции OpenAPI - Proxy & Readonly Token
В API Тинькофф Инвестиций на данный момент отсутствует возможность разграничивать права доступа для токенов. Это является проблемой для тех, кто хотел бы пользоваться различными сервисами, например сервисами статистики. Данный скрипт является решением данной проблемы, поскольку при его использовании <b>торговый токен находится на вашем сервере и вы контролируете все операции</b>.<br>
<br>
По запросу сервиса скрипт подгружает данные из вашего аккаунта API Тинькофф, после чего отправляет их в сервис статистики. Общение между вашим сервером и сервисом статистики будет защищено отдельным токеном.<br>
<br>
## Как использовать для сервиса статистики?
1. Нужно скачать файл <b>invest_proxy.php</b> и разместить его на своем хостинге в любой папке. По желанию файл можно переименовать;
2. Получить в [настройках сервиса статистики]((https://allex.me/invest/settings)) сервисный токен;
3. Указать в блоке настроек файла [торговый API токен](https://tinkoffcreditsystems.github.io/invest-openapi/auth/#_2) и сервисный токен для взаимодествия сервиса статистики и вашего сервера;
4. В [настройках сервиса статистики]((https://allex.me/invest/settings)) указать полный URL до данного скрипта.

После этого вы сможете безопасно пользоваться [сервисом статистики](https://allex.me/invest).
## Реализованы методы
<b>/user/accounts</b> - список доступных аккаунтов<br>
<b>/portfolio/currencies</b> - получение баланса<br>
<b>/portfolio</b> - получение портфолио<br>
<b>/market/orderbook</b> - получение стакана по FIGI<br>
<b>/orders</b> - получение списка заявок<br>
<b>/orders/limit-order</b> - выставление лимитной заявки<br>
<b>/orders/market-order</b> - выставление рыночной заявки<br>
<b>/orders/cancel</b> - отмена заявок<br>
<b>/operations</b> - получение списка операций за период<br>
## Пример
<b>Запрос к OpenAPI</b>
```
https://api-invest.tinkoff.ru/openapi/user/accounts
https://api-invest.tinkoff.ru/openapi/portfolio?brokerAccountId=1234567890
```
<b>Запрос к Proxy</b>
```
https://[ваш домен].ru/invest_proxy.php?action=/user/accounts
https://[ваш домен].ru/invest_proxy.php?action=/portfolio&brokerAccountId=1234567890
```
Набор параметров для методов соответствует [официальной документации к OpenAPI](https://tinkoffcreditsystems.github.io/invest-openapi/swagger-ui/) за исключением того, что название метода нужно передавать отдельным параметром <b>action</b>.<br>
<br>
## API Тинькофф Инвестиции
Тинькофф Инвестиции OpenAPI: https://github.com/TinkoffCreditSystems/invest-openapi<br>
Ветка с просьбой к разработчикам о [добавлении токенов с разграниченными правами](https://github.com/TinkoffCreditSystems/invest-openapi/issues/12).<br>
<br>
## Создано для сервиса статистики Тинькофф Инвестиций
📌 О сервисе: https://allex.me/invest/help<br>
📌 О боте: https://allex.me/invest/help_bot<br>
📌 Обсуждение в Telegram: https://t.me/TinkoffInvestStatChat
## Лицензия
MIT
