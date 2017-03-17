<?php

class shopShoplogisticsPluginBackendSendController extends waJsonController
  {
    public function execute()
      {
         if (!extension_loaded('curl')) {
          $this->response = array('fatalError' => 'PHP extension CURL not loaded',);
          return true;
         }

         if (!extension_loaded('SimpleXML')) {
          $this->response = array('fatalError' => 'PHP extension SimpleXML not loaded',);
          return true;
         }

         if (!extension_loaded('dom')) {
          $this->response = array('fatalError' => 'PHP extension DOM not loaded',);
          return true;
         }

         $task = waRequest::request('task');
         if ($task == 'send')
           {
           	 $this->response = $this->sendOrder();
           }
         else if ($task == 'update')
           {
           	 $this->response = $this->updateStatus();
           }
         else if ($task == 'post_send')
           {
           	 $this->response = $this->sendPostOrder();
           }



      }
    private function updateStatus() {         $plugin_id = 'shoplogistics';
         $plugin = waSystem::getInstance()->getPlugin($plugin_id);
         $settings = $plugin->getSettings();

         $order_id = waRequest::request('order_id', null, waRequest::TYPE_INT);

         $model = new shopShoplogisticsModel();
         $sl_order = $model->getByField('order_id', $order_id);

         $code = '';
         if (isset($sl_order['id']))
           {
           	 $code = $sl_order['code'];
           }
         else
           {           	 return array('fatalError' => 'Заказ еще не отправлен в ShopLogistics',);           }

         $dom = new domDocument("1.0", "utf-8");
         $root = $dom->createElement("request");
         $dom->appendChild($root);


         $function = ($sl_order['type'] == 'post') ? 'get_post_deliveries' : 'get_deliveries';
         $child = $dom->createElement("function",$function);
         $root->appendChild($child);

         $child = $dom->createElement("api_id",$settings['api_id']);
         $root->appendChild($child);

         $child = $dom->createElement("code",$code);
         $root->appendChild($child);

         $xml_content = $this->sendRequest($dom->saveXML());

         try {
           $xml = new SimpleXMLElement($xml_content);
         } catch (Exception $e) {
           return array('fatalError' => 'Ошибка в структуре xml или не правильно закодированно.',);
         }

         if ($sl_order['type'] != 'post') {
           $model->updateById($sl_order['id'], array(
                'status' => trim($xml->deliveries->delivery->status),
                'errors' => trim($xml->deliveries->delivery->errors),
                'current_filial' => trim($xml->deliveries->delivery->current_filial),
                'reciver_filial' => trim($xml->deliveries->delivery->reciver_filial),
                'updated_datetime ' => date('Y-m-d H:i:s')
           ));
         }
         else {           if ($xml->deliveries->delivery->post_operation != '')
             {               $post_status	= $xml->deliveries->delivery->post_operation .', '. $xml->deliveries->delivery->post_operation_attr;             }
           $model->updateById($sl_order['id'], array(
                'status' => trim($xml->deliveries->delivery->status),
                'errors' => trim($xml->deliveries->delivery->errors),
                'post_status' => $post_status,
                'updated_datetime ' => date('Y-m-d H:i:s')
           ));
         }

         $alert_msg = 'Статус обновлен';

    	 $sl_order = $model->getByField('order_id', $order_id);

         return array(
            'fatalError' => '',
            'alert_msg' => $alert_msg,
            'status' => trim($sl_order['status']),
            'post_status' => trim($sl_order['post_status']),
            'type' => trim($sl_order['type']),
            'errors' => trim($sl_order['errors']),
            'current_filial' => trim($sl_order['current_filial']),
            'reciver_filial' => trim($sl_order['reciver_filial']),
         );
    }

    private function sendOrder() {         $plugin_id = 'shoplogistics';
         $plugin = waSystem::getInstance()->getPlugin($plugin_id);
         $settings = $plugin->getSettings();

         $order_id = waRequest::request('order_id', null, waRequest::TYPE_INT);
         $delivery_date = waRequest::request('delivery_date');
         $time_from = waRequest::request('time_from');
         $time_to = waRequest::request('time_to');

         $order = shopPayment::getOrderData($order_id, $this);

         $contact = new waContact($order->contact_id);

         $model = new shopShoplogisticsModel();
         $sl_order = $model->getByField('order_id', $order_id);

         $code = '';
         if (isset($sl_order['id']))
           {
           	 $code = $sl_order['code'];
           }

         if (isset($sl_order['id']) && $sl_order['type'] == 'post')
           {             return array('fatalError' => 'Доставка уже отпрвлена как почтовая',);           }

          $dom = new domDocument("1.0", "utf-8");
          $root = $dom->createElement("request");
          $dom->appendChild($root);

          $child = $dom->createElement("function","add_delivery");
          $root->appendChild($child);

          $child = $dom->createElement("api_id",$settings['api_id']);
          $root->appendChild($child);

          $child = $dom->createElement("deliveries");
          $root->appendChild($child);

           $delivery = $dom->createElement("delivery");
           $child->appendChild($delivery);

            $child2 = $dom->createElement("delivery_date",$delivery_date);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("date_transfer_to_store",date("Y-m-d"));
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("picking_date",$delivery_date);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("from_city",$settings['from_city_code']);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("to_city",$order->shipping_address['city']);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("time_from",$time_from);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("time_to",$time_to);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("order_id",$order->id_str);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("address",$order->shipping_address['street']);
            $delivery->appendChild($child2);

            $contact_person = ($order->shipping_address['firstname'] == '') ? $contact->get('firstname') .' '.$contact->get('lastname') :  $order->shipping_address['firstname'] .' '. $order->shipping_address['firstname'];
            $child2 = $dom->createElement("contact_person",$contact_person);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("phone",$contact->get('phone','default'));
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("phone_sms",$contact->get('phone','default'));
            $delivery->appendChild($child2);

            $price = ($order->params['payment_plugin'] == '' || $order->params['payment_plugin'] == 'cash') ? $order->total : 0;
            $child2 = $dom->createElement("price",$price);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("ocen_price",$order->subtotal);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("site_name",$settings['site_name']);
            $delivery->appendChild($child2);

            $pickup_place = ($order->params['shipping_plugin'] == 'shoplogisticspickup') ? $order->params['shipping_rate_id'] : '';
            $child2 = $dom->createElement("pickup_place",$pickup_place);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("zabor_places_code",$settings['zabor_places_code']);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("add_product_from_disct",intval($settings['get_products_from_store']));
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("additional_info",$order->comment);
            $delivery->appendChild($child2);

            if ($settings['products_list'] == 1) {

            $child2 = $dom->createElement("products");
            $delivery->appendChild($child2);


            for ($i = 0; $i < count($order->items); $i++)
              {
              	$child3 = $dom->createElement("product");
                $child2->appendChild($child3);

              	$child4 = $dom->createElement("articul",$order->items[$i]['sku']);
                $child3->appendChild($child4);

              	$child4 = $dom->createElement("name",$order->items[$i]['name']);
                $child3->appendChild($child4);

              	$child4 = $dom->createElement("quantity",$order->items[$i]['quantity']);
                $child3->appendChild($child4);

              	$child4 = $dom->createElement("item_price",$order->items[$i]['price']);
                $child3->appendChild($child4);
              }
            }

        $xml_content = $this->sendRequest($dom->saveXML());
        try {
          $xml = new SimpleXMLElement($xml_content);
        } catch (Exception $e) {
          return array('fatalError' => 'Ошибка в структуре xml или не правильно закодированно.',);
        }

        if ($xml->error == 1)
          {
          	return array('fatalError' => 'Ошибка: не найден API ID',);
          }


        $alert_msg = '';
        if (isset($sl_order['id']))
          {
            if ($xml->deliveries->delivery->error_code == 0 || $xml->deliveries->delivery->error_code == 5)
              {
                $model->updateById($sl_order['id'], array(
                      'order_id' => $order_id,
                      'code' => trim($xml->deliveries->delivery->code),
                      'type' => 'delivery',
                      'delivery_date' => $delivery_date,
                      'delivery_time_from' => $time_from,
                      'delivery_time_to' => $time_to,
                      'status' => trim($xml->deliveries->delivery->status),
                      'errors' => trim($xml->deliveries->delivery->errors),
                      'updated_datetime' => date('Y-m-d H:i:s')
                ));
                $alert_msg = 'Заявка на доставку обновлена в ShopLogistics';
              }
            else
              {
                $model->updateById($sl_order['id'], array(
                      'status' => trim($xml->deliveries->delivery->status),
                      'errors' => trim($xml->deliveries->delivery->errors),
                      'is_edit' => 0,
                      'updated_datetime ' => date('Y-m-d H:i:s')
                ));
                $alert_msg = 'Статус ShopLogistics не позволяет изменять заявку на доставку';
              }

          }
        else
          {
            $model->insert(array(
               'order_id' => $order_id,
               'code' => trim($xml->deliveries->delivery->code),
               'type' => 'delivery',
               'delivery_date' => $delivery_date,
               'delivery_time_from' => $time_from,
               'delivery_time_to' => $time_to,
               'status' => trim($xml->deliveries->delivery->status),
               'errors' => trim($xml->deliveries->delivery->errors),
               'datetime' => date('Y-m-d H:i:s')
            ));
            $alert_msg = 'Заявка на доставку добавлена в ShopLogistics';
          }

          $sl_order = $model->getByField('order_id', $order_id);

          return array(
            'fatalError' => '',
            'alert_msg' => $alert_msg,
            'status' => trim($sl_order['status']),
            'post_status' => trim($sl_order['post_status']),
            'type' => trim($sl_order['type']),
            'errors' => trim($sl_order['errors']),
            'current_filial' => trim($sl_order['current_filial']),
            'reciver_filial' => trim($sl_order['reciver_filial']),
            );
 }
 private function sendPostOrder() {
 	     $plugin_id = 'shoplogistics';
         $plugin = waSystem::getInstance()->getPlugin($plugin_id);
         $settings = $plugin->getSettings();

         $order_id = waRequest::request('order_id', null, waRequest::TYPE_INT);

         $order = shopPayment::getOrderData($order_id, $this);

         $contact = new waContact($order->contact_id);

         $model = new shopShoplogisticsModel();
         $sl_order = $model->getByField('order_id', $order_id);

         $code = '';
         if (isset($sl_order['id']))
           {
           	 $code = $sl_order['code'];
           }

         if (isset($sl_order['id']) && $sl_order['type'] == 'delivery')
           {
             return array('fatalError' => 'Доставка уже отпрвлена как обычная',);
           }

          $dom = new domDocument("1.0", "utf-8");
          $root = $dom->createElement("request");
          $dom->appendChild($root);

          $child = $dom->createElement("function","add_post_delivery");
          $root->appendChild($child);

          $child = $dom->createElement("api_id",$settings['api_id']);
          $root->appendChild($child);

          $child = $dom->createElement("deliveries");
          $root->appendChild($child);

           $delivery = $dom->createElement("delivery");
           $child->appendChild($delivery);

            $child2 = $dom->createElement("order_id",$order->id_str);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("date_transfer_to_store",date("Y-m-d"));
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("zip",$order->shipping_address['zip']);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("region",$order->shipping_address['region_name']);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("city",$order->shipping_address['city']);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("address",$order->shipping_address['street']);
            $delivery->appendChild($child2);

            $contact_person = ($order->shipping_address['firstname'] == '') ? $contact->get('firstname') .' '.$contact->get('lastname') :  $order->shipping_address['firstname'] .' '. $order->shipping_address['firstname'];
            $child2 = $dom->createElement("person",$contact_person);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("nested_type",'разное');
            $delivery->appendChild($child2);

            $price = ($order->params['payment_plugin'] == '' || $order->params['payment_plugin'] == 'cash') ? $order->total : 0;
            $child2 = $dom->createElement("cash_on_delivery",$price);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("valuably",$order->subtotal);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("post_service",'Почта');
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("phone",$contact->get('phone','default'));
            $delivery->appendChild($child2);

            $ar = $contact->getFirst('email');
            $child2 = $dom->createElement("email",$ar['value']);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("site_name",$settings['site_name']);
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("add_product_from_disct",intval($settings['get_products_from_store']));
            $delivery->appendChild($child2);

            $child2 = $dom->createElement("comments",$order->comment);
            $delivery->appendChild($child2);

            if ($settings['products_list'] == 1) {

            $child2 = $dom->createElement("products");
            $delivery->appendChild($child2);


            for ($i = 0; $i < count($order->items); $i++)
              {
              	$child3 = $dom->createElement("product");
                $child2->appendChild($child3);

              	$child4 = $dom->createElement("articul",$order->items[$i]['sku']);
                $child3->appendChild($child4);

              	$child4 = $dom->createElement("name",$order->items[$i]['name']);
                $child3->appendChild($child4);

              	$child4 = $dom->createElement("quantity",$order->items[$i]['quantity']);
                $child3->appendChild($child4);

              	$child4 = $dom->createElement("item_price",$order->items[$i]['price']);
                $child3->appendChild($child4);
              }
            }


        $xml_content = $this->sendRequest($dom->saveXML());
        try {
          $xml = new SimpleXMLElement($xml_content);
        } catch (Exception $e) {
          return array('fatalError' => 'Ошибка в структуре xml или не правильно закодированно.',);
        }

        if ($xml->error == 1)
          {
          	return array('fatalError' => 'Ошибка: не найден API ID',);
          }

        $alert_msg = '';
        if (isset($sl_order['id']))
          {
            if ($xml->deliveries->delivery->error_code == 10)
              {              	return array('fatalError' => 'Номер заказа не задан, доставка не может быть добавлена',);              }
            if ($xml->deliveries->delivery->error_code == 12)
              {
                $model->updateById($sl_order['id'], array(
                      'order_id' => $order_id,
                      'code' => trim($xml->deliveries->delivery->code),
                      'type' => 'post',
                      'status' => trim($xml->deliveries->delivery->status),
                      'errors' => trim($xml->deliveries->delivery->errors),
                      'updated_datetime' => date('Y-m-d H:i:s')
                ));
                $alert_msg = 'Заявка на доставку обновлена в ShopLogistics';
              }
            else
              {
                $model->updateById($sl_order['id'], array(
                      'status' => trim($xml->deliveries->delivery->status),
                      'errors' => trim($xml->deliveries->delivery->errors),
                      'is_edit' => 0,
                      'updated_datetime ' => date('Y-m-d H:i:s')
                ));
                $alert_msg = 'Статус ShopLogistics не позволяет изменять заявку на доставку';
              }

          }
        else
          {
            $model->insert(array(
               'order_id' => $order_id,
               'code' => trim($xml->deliveries->delivery->code),
               'type' => 'post',
               'status' => trim($xml->deliveries->delivery->status),
               'errors' => trim($xml->deliveries->delivery->errors),
               'datetime' => date('Y-m-d H:i:s')
            ));
            $alert_msg = 'Заявка на доставку добавлена в ShopLogistics';
          }

          $sl_order = $model->getByField('order_id', $order_id);

          return array(
            'fatalError' => '',
            'alert_msg' => $alert_msg,
            'status' => trim($sl_order['status']),
            'post_status' => trim($sl_order['post_status']),
            'type' => trim($sl_order['type']),
            'errors' => trim($sl_order['errors']),
            'current_filial' => trim($sl_order['current_filial']),
            'reciver_filial' => trim($sl_order['reciver_filial']),
            );
 }
 private function sendRequest($xml)
  {
    $plugin_id = 'shoplogistics';
    $plugin = waSystem::getInstance()->getPlugin($plugin_id);
    $settings = $plugin->getSettings();

  	$curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $settings['request_url']);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($curl, CURLOPT_POSTFIELDS, 'xml='.urlencode(base64_encode($xml)));
    curl_setopt($curl, CURLOPT_USERAGENT, 'Opera 10.00');
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);

    $res = curl_exec($curl);
    curl_close($curl);

    return $res;
  }

 }
