Описание:
Написать простой сокращатель ссылок и gate для редиректа.

Функциональные требования к ужиматору:
— на вход подается ссылка, которую нужно ужать;
— на выход нужно получить сокращенную ссылку с длиной параметров урла <=6 символов;
Функциональные требования к гейту редиректа:
— при заходе на гейт должен происходить редирект на начальную ссылку.

Требования к реализации:
— использовать silex;
— ужиматор должен принимать/возвращать данные в json формате;
— для ужимания желательно использовать какую-нибудь либу с гитхаба, подключенную через композер;
— для хранения мапы полная/краткая ссылка использовать любую реляционную СУБД
— запросы на гейт должны кешироваться с TTL 3600 секунд, для кеша использовать redis.

P.S
Для реализации не надо поднимать инстансы бд или редиса и выкладывать
это на какой-то домен нам нужно только посмотреть код.