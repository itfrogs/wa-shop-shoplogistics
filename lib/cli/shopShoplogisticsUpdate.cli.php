<?php

class shopShoplogisticsUpdateCli extends waCliController
{

    public function execute()
    {
      if (!extension_loaded('curl')) {
        echo 'PHP extension CURL not loaded';
        exit;
      }

      if (!extension_loaded('SimpleXML')) {
        echo 'PHP extension SimpleXML not loaded';
        exit;
      }

      if (!extension_loaded('dom')) {
        echo 'PHP extension DOM not loaded';
        exit;
      }

      $plugin_id = 'shoplogistics';
      $plugin = waSystem::getInstance()->getPlugin($plugin_id);
      $settings = $plugin->getSettings();

      $model = new shopShoplogisticsModel();

      $model->query("UPDATE shoplogistics SET is_edit='0' WHERE TO_DAYS('". date('Y-m-d H:i:s') ."')-TO_DAYS(datetime) > 90");

      $results = $model->where("is_edit = 1 and `type` = 'post'")->fetchAll();

      if (count($results) > 0)
        {
          $dom = new domDocument("1.0", "utf-8");
          $root = $dom->createElement("request");
          $dom->appendChild($root);

          $child = $dom->createElement("function","get_post_deliveries_array");
          $root->appendChild($child);

          $child = $dom->createElement("api_id",$settings['api_id']);
          $root->appendChild($child);

          $child = $dom->createElement("deliveries");
          $root->appendChild($child);

          foreach ($results as $result) {
            $code = $dom->createElement("code",$result['code']);
            $child->appendChild($code);
          }

          $xml_content = $this->sendRequest($dom->saveXML());
          try {
            $xml = new SimpleXMLElement($xml_content);
          } catch (Exception $e) {
            echo  'Ошибка в структуре xml или не правильно закодированно.';
          }
          for ($i = 0; $i < count($xml->deliveries->delivery); $i++)
            {
              if ($xml->deliveries->delivery[$i]->post_operation != '')
                {
                  $post_status	= $xml->deliveries->delivery[$i]->post_operation .', '. $xml->deliveries->delivery[$i]->post_operation_attr;
                }
              $is_edit = 1;
              if (substr_count($post_status,'Вручение') > 0)
                {
                  $is_edit = 0;
                }

             $model->updateByField('code', trim($xml->deliveries->delivery[$i]->code), array(
                'status' => trim($xml->deliveries->delivery[$i]->status),
                'errors' => trim($xml->deliveries->delivery[$i]->errors),
                'post_status' => $post_status,
                'is_edit' => $is_edit,
                'updated_datetime' => date('Y-m-d H:i:s')
                ));
            }

        }

      $results = $model->where("is_edit = 1 and `type` = 'delivery'")->fetchAll();
      if (count($results) > 0)
        {
          $dom = new domDocument("1.0", "utf-8");
          $root = $dom->createElement("request");
          $dom->appendChild($root);

          $child = $dom->createElement("function","get_order_status_array");
          $root->appendChild($child);

          $child = $dom->createElement("api_id",$settings['api_id']);
          $root->appendChild($child);

          $child = $dom->createElement("deliveries");
          $root->appendChild($child);

          foreach ($results as $result) {
            $code = $dom->createElement("code",$result['code']);
            $child->appendChild($code);
          }

          $xml_content = $this->sendRequest($dom->saveXML());
          try {
            $xml = new SimpleXMLElement($xml_content);
          } catch (Exception $e) {
            echo  'Ошибка в структуре xml или не правильно закодированно.';
          }
          for ($i = 0; $i < count($xml->deliveries->delivery); $i++)
            {
              $is_edit = ($xml->deliveries->delivery[$i]->status_final == 1) ? 0 : 1;

              $model->updateByField('code', trim($xml->deliveries->delivery[$i]->code), array(
                'status' => trim($xml->deliveries->delivery[$i]->status),
                'errors' => trim($xml->deliveries->delivery[$i]->errors),
                'current_filial' => trim($xml->deliveries->delivery[$i]->current_filial),
                'reciver_filial' => trim($xml->deliveries->delivery[$i]->reciver_filial),
                'is_edit' => $is_edit,
                'updated_datetime' => date('Y-m-d H:i:s')
                ));
            }

        }
    //  echo "complited";
    //  exit;
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