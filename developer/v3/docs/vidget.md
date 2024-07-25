# Виджеты рабочего стола
Система позволяет добавлять собственные виджеты для рабочего стола.

## Хранилище настроек

Все настройки виджетов хранятся в файле "**/cash/map.vigets.json**", и имеют следующую структуру:
<pre><code class="json">
{
    "name":"Выполнение планов",
    "container":"analitic",
    "containerclass":"relativ",
    "url":"vigets/viget.plan.php",
    "icon":"icon-gauge",
    "active":"on",
    "tooltips":"Выполнение планов",
    "tooltips-position":"top",
    "actionPlus":"",
    "class":"relativ",
    "expressReport":"reports/ent-planDoByPayment.php",
    "expressReportTitle":"Выполнение планов",
    "settingsURL":"",
    "width":"30",
    "height":""
}
</code></pre>

Каждый параметр имеет следующее функциональное значение:

- **name** - Заголовок виджета <span style="color:#ff0000;"><sup>обязательно</sup></span>
- **container** - идентификатор контейнера виджета <span style="color:#ff0000;"><sup>обязательно</sup></span>
- **containerclass** - css-класс для контейнера <span style="color:#0066ff;"><sup>опционально</sup></span>
- **url** - адрес скрипта, загружаемого в блок виджета <span style="color:#ff0000;"><sup>обязательно</sup></span>
- **icon** - иконка виджета <span style="color:#ff0000;"><sup>обязательно</sup></span>
- **active** - активность виджета: on/off
- **tooltips** - всплывающая подсказка <span style="color:#0066ff;"><sup>опционально</sup></span>
- **tooltips-position** - позиция подсказки: top, bottom <span style="color:#0066ff;"><sup>опционально</sup></span>
- **actionPlus** - дополнительное действие <span style="color:#0066ff;"><sup>опционально</sup></span>
- **class** - css-класс блока данных, в который загружаются данные <span style="color:#0066ff;"><sup>опционально</sup></span>
- **expressReport** - адрес экспресс-отчета <span style="color:#0066ff;"><sup>опционально</sup></span>
- **expressReportTitle** - заголовок экспресс-отчета <span style="color:#0066ff;"><sup>опционально</sup></span>
- **settingsURL** - адрес настроек виджета <span style="color:#0066ff;"><sup>опционально</sup></span>
- **width** и **height** - не активные параметры


> ### Примечание
> 
> Для подключения собственных виджетов рекомендуется использовать файл **cash/map.vigets.castom.json**. Это позволит сохранить настройки при обновлении, т.к. файл **cash/map.vigets.json** перезаписывается.

## Схема виджета

![](https://salesman.pro/docs.img/docs/wiget_set.png)

**Примечания:**

*     Рекомендуемое расположение виджетов - папка "vigets"
*     Подключение виджетов осуществляется каждым сотрудником в "Мои настройки"
*     Все виджеты поставляются с открытым исходным кодом
