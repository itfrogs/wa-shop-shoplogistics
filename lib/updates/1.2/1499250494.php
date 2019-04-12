<?php

$model = new waModel();

//попытка получить содержимое потенциально отсутствующего поля таблицы
try {
    $model->query('SELECT partial_ransom FROM shop_shoplogistics_orders WHERE 0');

//в случае неудачи — если поле отсутствует — добавляем его в таблицу
} catch (waDbException $e) {
    $sql = 'ALTER TABLE shop_shoplogistics_orders ADD partial_ransom INT(11) NOT NULL DEFAULT 0';
    $model->exec($sql);
}

//попытка получить содержимое потенциально отсутствующего поля таблицы
try {
    $model->query('SELECT delivery_price_for_customer FROM shop_shoplogistics_orders WHERE 0');

//в случае неудачи — если поле отсутствует — добавляем его в таблицу
} catch (waDbException $e) {
    $sql = 'ALTER TABLE shop_shoplogistics_orders ADD delivery_price_for_customer VARCHAR(255) NOT NULL';
    $model->exec($sql);
}

//попытка получить содержимое потенциально отсутствующего поля таблицы
try {
    $model->query('SELECT delivery_price_porog_for_customer FROM shop_shoplogistics_orders WHERE 0');

//в случае неудачи — если поле отсутствует — добавляем его в таблицу
} catch (waDbException $e) {
    $sql = 'ALTER TABLE shop_shoplogistics_orders ADD delivery_price_porog_for_customer VARCHAR(255) NOT NULL';
    $model->exec($sql);
}

//попытка получить содержимое потенциально отсутствующего поля таблицы
try {
    $model->query('SELECT delivery_discount_for_customer FROM shop_shoplogistics_orders WHERE 0');

//в случае неудачи — если поле отсутствует — добавляем его в таблицу
} catch (waDbException $e) {
    $sql = 'ALTER TABLE shop_shoplogistics_orders ADD delivery_discount_for_customer VARCHAR(255) NOT NULL';
    $model->exec($sql);
}

//попытка получить содержимое потенциально отсутствующего поля таблицы
try {
    $model->query('SELECT delivery_discount_porog_for_customer FROM shop_shoplogistics_orders WHERE 0');

//в случае неудачи — если поле отсутствует — добавляем его в таблицу
} catch (waDbException $e) {
    $sql = 'ALTER TABLE shop_shoplogistics_orders ADD delivery_discount_porog_for_customer VARCHAR(255) NOT NULL';
    $model->exec($sql);
}
