# bitrix-geobase
Модуль для 1C-Битрикс «Определение города по IP адресу пользователя»: определяет город посетителя вашего сайта по его IP-адресу.

Данный модуль использует сервис MaxMind. При первом обновлении базы она загружается в Highload-блоки, после чего работает с данными непосредственно из них. Если удаленная база не обновлялась после последней загрузки, то на вкладке База городов кнопки с обновлением не будет - это значит, у вас уже установлена актуальная версия базы.

Особенности модуля:

1. Применяются локальные базы

Преимущество локальной базы данных IP-адресов в том, что сайт не зависит от внешних сервисов геопозиционирования, и их функционирование не влияет на работу модуля. Недостаток — эти БД нужно периодически обновлять, для чего в настройках предусмотрен специальный интерфейс.

Локальные базы хранятся в Highload-блоках, что позволяет гибко настраивать города и привязку к ним.

2. Автоматическое определение местоположения

Решение автоматически определит город посетителя по его IP и может выводить окна с подтверждением города либо выбором другого, из списка или в строке поиска.

3. Поставляются готовые компоненты

В модуле присутствует компонент, необходимый для отображения выбранного местоположения и возможности его изменения пользователем.

Модуль «Определение города по IP адресу пользователя» работает на любой редакции «1С-Битрикс: Управление сайтом».
Страница модуля на [маркетплейсе](https://marketplace.1c-bitrix.ru/solutions/reaspekt.geobase/).
