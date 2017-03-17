<?php
  return array(
    'shop_shoplogistics_orders' => array(
        'id' => array('int', 11, 'null' => 0, 'autoincrement' => 1),
        'order_id' => array('int', 11, 'null' => 0),
        'code' => array('varchar', 255, 'null' => 0),
        'type' => array('varchar', 255, 'null' => 0),
        'status' => array('varchar', 255, 'null' => 0),
        'post_status' => array('varchar', 255, 'null' => 0),
        'current_filial' => array('varchar', 255, 'null' => 0),
        'reciver_filial' => array('varchar', 255, 'null' => 0),
        'payment_status' => array('text', 'null' => 0),
        'errors' => array('varchar', 255, 'null' => 0),
        'datetime' => array('datetime', 'null' => 0),
        'updated_datetime' => array('datetime', 'null' => 0),
        'delivery_date' => array('date', 'null' => 0),
        'delivery_time_from' => array('time', 'null' => 0),
        'delivery_time_to' => array('time', 'null' => 0),
        ':keys' => array(
            'PRIMARY' => 'id',
        ),
    ),
);
