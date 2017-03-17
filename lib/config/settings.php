<?php
return array(
    'api_id'              => array(
        'value'        => '',
        'title'        => 'API ID',
        'description'  => 'Его нужно взять в личном кабинете Shop-logistics в разделе "Ваши данные"',
        'control_type' => 'text',
        'subject'      => 'shoplogistics',
    ),
    'from_city_code'              => array(
        'value'        => '405065',
        'title'        => 'Код города отправителя',
        'description'  => '',
        'control_type' => 'text',
        'subject'      => 'shoplogistics',
    ),
    'site_name'              => array(
        'value'        => '',
        'title'        => 'Название сайта',
        'description'  => 'Это название будет отображатся на этикетках и подставлятся в смс (оно должно быть добавленно в разделе Настройки ЛК ShopLogistics)',
        'control_type' => 'text',
        'subject'      => 'shoplogistics',
    ),
    'zabor_places_code'              => array(
        'value'        => '',
        'title'        => 'Код места забора (склада)',
        'description'  => 'Его нужно взять в разделе Вызов курьера (если не указать берет основной)',
        'control_type' => 'text',
        'subject'      => 'shoplogistics',
    ),
    'products_list'              => array(
        'value'        => '1',
        'title'        => 'Указывать список товаров в заявке',
        'description'  => '',
        'control_type' => 'checkbox',
        'subject'      => 'shoplogistics',
    ),
    'get_products_from_store'              => array(
        'value'        => '0',
        'title'        => 'Брать товар со склада хранения',
        'description'  => '',
        'control_type' => 'checkbox',
        'subject'      => 'shoplogistics',
    ),
    'request_url'              => array(
        'value'        => 'http://client-shop-logistics.ru/index.php?route=deliveries/api',
        'title'        => 'URL на который будет отправляться запрос',
        'description'  => '',
        'control_type' => 'text',
        'subject'      => 'shoplogistics',
    ),
);
