<?php

class shopShoplogisticsPlugin extends shopPlugin
{
    private $templatepaths = array();

    public function shoplogisticsInfo() {

        $model = new shopShoplogisticsModel();
        $order_id = waRequest::request('id', null, waRequest::TYPE_INT);

        $order = shopPayment::getOrderData($order_id, $this);

        $sl_order = $model->getByField('order_id', $order_id);

        if (isset($sl_order['id']))
           {
           	 $return['action_link'] = $this->getDeliveryContent($sl_order['delivery_date'],substr($sl_order['delivery_time_from'],0,2),substr($sl_order['delivery_time_to'],0,2),$sl_order,$order_id,$order);
           }
        else
           {
           	 $return['action_link'] = $this->getDeliveryContent(date("Y-m-d",mktime(0, 0, 0, date('m') , date('d')+1, date('Y'))),'10','18',array(),$order_id,$order);
           }
        return $return;
    }

    private function getDeliveryContent($delivery_date, $delivery_time_from, $delivery_time_to, $sl_order, $order_id,$order) {
      $str = '<div id="shop_logistics_div">';

          $delivery_date_ar = explode('-',$delivery_date);
          $str .= '<select name="dd_day" id="dd_day" >';
          for ($i = 1; $i < 32; $i++)
            {
              $value = ($i < 10) ? '0'.$i : $i;
              $selected = ($value == $delivery_date_ar[2]) ? 'selected' : '';
              $str .= '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
            }
          $str .= '</select>';

          $str .= '<select name="dd_month" id="dd_month" >';
          for ($i = 1; $i < 13; $i++)
            {
              $value = ($i < 10) ? '0'.$i : $i;
              $selected = ($value == $delivery_date_ar[1]) ? 'selected' : '';
              $str .= '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
            }
          $str .= '</select>';

          $str .= '<select name="dd_year" id="dd_year" >';
          $year = intval(date('Y'));
          for ($i = $year; $i <$year+2; $i++)
            {
              $value = $i;
              $selected = ($value == $delivery_date_ar[0]) ? 'selected' : '';
              $str .= '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
            }
          $str .= '</select><br>';

          $str .= 'с <select name="time_from" id="time_from" >';
          for ($i = 9; $i < 23; $i++)
            {
              $value = ($i < 10) ? '0'.$i : $i;
              $selected = ($value == $delivery_time_from) ? 'selected' : '';
              $str .= '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
            }
          $str .= '</select>';

          $str .= ' до <select name="time_to" id="time_to" >';
          for ($i = 9; $i < 23; $i++)
            {
              $value = ($i < 10) ? '0'.$i : $i;
              $selected = ($value == $delivery_time_to) ? 'selected' : '';
              $str .= '<option value="'.$value.'" '.$selected.' >'.$value.'</option>';
            }
          $str .= '</select>';

          $partial_ransom_checked = (isset($sl_order['partial_ransom']) && $sl_order['partial_ransom'] == 1) ? 'checked' : '';
          $delivery_price_for_customer =  (isset($sl_order['delivery_price_for_customer'])) ? $sl_order['delivery_price_for_customer'] : number_format($order['shipping'],2);
          $delivery_price_porog_for_customer =  (isset($sl_order['delivery_price_porog_for_customer'])) ? $sl_order['delivery_price_porog_for_customer'] : 0;
          $delivery_discount_for_customer =  (isset($sl_order['delivery_discount_for_customer'])) ? $sl_order['delivery_discount_for_customer'] : number_format($order['discount'],2);;
          $delivery_discount_porog_for_customer =  (isset($sl_order['delivery_discount_porog_for_customer'])) ? $sl_order['delivery_discount_porog_for_customer'] : 0;

          $str .= '<span style="font-size:12px;"><br><input type="checkbox" name="partial_ransom" id="partial_ransom" value="1" '. $partial_ransom_checked .' > Частичный выкуп';
          $is_paid = (!isset($order->params['payment_plugin']) || $order->params['payment_plugin'] == '' || $order->params['payment_plugin'] == 'cash' || $order->params['payment_plugin'] == 'cod') ? '' : 'checked';
          $str .= '<br><input type="checkbox" name="is_paid" id="is_paid" value="1" '. $is_paid .' > Заказ оплачен';
          $str .= '<br><nobr>Доставка: <input type="text" name="delivery_price_for_customer" id="delivery_price_for_customer" value="'. $delivery_price_for_customer .'" size="5"  ></nobr>';
          $str .= '<br><nobr>Бесплатная от: <input type="text" name="delivery_price_porog_for_customer" id="delivery_price_porog_for_customer" value="'. $delivery_price_porog_for_customer .'" size="5"  ></nobr>';
          $str .= '<br><nobr>Скидка: <input type="text" name="delivery_discount_for_customer" id="delivery_discount_for_customer" value="'. $delivery_discount_for_customer .'" size="5"  ></nobr>';
          $str .= '<br><nobr>Но не менее: <input type="text" name="delivery_discount_porog_for_customer" id="delivery_discount_porog_for_customer" value="'. $delivery_discount_porog_for_customer .'" size="5"  ></nobr></span>';


          $errors_str = (isset($sl_order['errors']) && $sl_order['errors'] != '') ? '<br><font color="red">Ошибки: '.$sl_order['errors'].'</font>' : '';
          $str .= '<input type="button"  id="shopLogisitcs_button_send" value="Курьером" OnClick="sendOrderToShopLogisitcs(\'send\');">
                   <input type="button"  id="shopLogisitcs_button_post_send" value=" Почтой " OnClick="sendOrderToShopLogisitcs(\'post_send\');">
                  ';
          $add_info = '';
          if (isset($sl_order['id']))
            {
              if ($sl_order['type'] == 'post')
                {
                   $add_info = '<b>Почтовая доставка</b><br><b>Статус:</b> '.$sl_order['status'].'
                   <br><b>Статус почты:</b> '.$sl_order['post_status'] . $errors_str;
                }
              else
                {
                   $add_info = '<b>Статус:</b> '.$sl_order['status'].'
                   <br><b>Филиал получатель:</b> '.$sl_order['current_filial'].'
                   <br><b>Текущий филиал:</b> '.$sl_order['reciver_filial']. $errors_str;
                }
            }

          $str .= '</div>
                   <div id="shop_logistics_div_status" style="padding:5px;">'.$add_info.'
                   </div>
                   <input type="button"  id="shopLogisitcs_button_update" value="Обновить статус" OnClick="sendOrderToShopLogisitcs(\'update\');">
                  ';
           $str .= '
                    <script type="text/javascript" >
                      function sendOrderToShopLogisitcs(task) {
                        var delivery_date = $("#dd_year").val() + \'-\' + $("#dd_month").val() + \'-\' + $("#dd_day").val();
                        var time_from = $("#time_from").val() + \':00:00\';
                        var time_to = $("#time_to").val() + \':00:00\';
                        $("#shopLogisitcs_button_send").prop( "disabled", true );
                        $("#shopLogisitcs_button_post_send").prop( "disabled", true );

                        var partial_ransom = (document.getElementById(\'partial_ransom\').checked) ? 1 : 0;
                        var delivery_price_for_customer = $("#delivery_price_for_customer").val();
                        var delivery_price_porog_for_customer = $("#delivery_price_porog_for_customer").val();
                        var delivery_discount_for_customer = $("#delivery_discount_for_customer").val();
                        var delivery_discount_porog_for_customer = $("#delivery_discount_porog_for_customer").val();
                        var is_paid  = (document.getElementById(\'is_paid\').checked) ? 1 : 0;

                        var url2 = \'&partial_ransom=\' + partial_ransom + \'&delivery_price_for_customer=\' + delivery_price_for_customer + \'&delivery_price_porog_for_customer=\' + delivery_price_porog_for_customer + \'&delivery_discount_for_customer=\' + delivery_discount_for_customer + \'&delivery_discount_porog_for_customer=\' + delivery_discount_porog_for_customer + \'&is_paid=\' + is_paid;

                        $.getJSON(\'?plugin=shoplogistics&action=send&order_id='. $order_id .'&task=\'+ task +\'&delivery_date=\'+ delivery_date +\'&time_from=\'+ time_from +\'&time_to=\'+ time_to +\'\' + url2, function (json) {
                             if (json.data.fatalError != \'\')
                               {
                               	 alert(json.data.fatalError);
                               }
                             else
                               {
                                 alert(json.data.alert_msg);
                                 var content = \'\';
                                 if (json.data.type == \'post\')
                                   {
                                   	 content += \'<b>Почтовая доставка</b> <br>\';
                                   }
                                 content += \'<b>Статус:</b> \' + json.data.status;
                                 if (json.data.type != \'post\')
                                   {
                                   	 content += \'<br><b>Филиал получатель:</b> \' + json.data.reciver_filial;
                                     content += \'<br><b>Текущий филиал:</b> \' + json.data.current_filial;
                                   }
                                 else
                                   {
                                   	 content += \'<br><b>Статус почты:</b> \' + json.data.post_status;
                                   }
                                 if (json.data.errors != \'\')
                                   {
                                   	 content += \'<br><font color="red"><b>Ошибки:</b> \' + json.data.errors + \'</font>\';
                                   }
                                 $("#shop_logistics_div_status").html(content);
                               }
                             $("#shopLogisitcs_button_send").prop( "disabled", false );
                             $("#shopLogisitcs_button_post_send").prop( "disabled", false );
                        });
                      }
                    </script>
                   ';
      return $str;
    }

}
