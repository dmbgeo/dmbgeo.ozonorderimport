<?

use \GuzzleHttp\Client;
use \Bitrix\Main\Config\Option;
use Bitrix\Main\Context,
    Bitrix\Main\Loader,
    Bitrix\Currency\CurrencyManager,
    Bitrix\Sale\Order,
    Bitrix\Sale\Basket,
    Bitrix\Sale\Delivery,
    Bitrix\Sale\PaySystem;


class OzonOrderImport
{
    public static $MODULE_ID = 'dmbgeo.ozonorderimport';


    public static function Agent()
    {   
        if (!Loader::includeModule("sale") && !Loader::includeModule("catalog") && !Loader::includeModule("iblock")) die();
        $sites=self::getSites();
        foreach($sites as $site){
            if(self::getOption("STATUS",$site['LID'])=="N"){
                continue;
            }
            if(self::getOption("API_LINK",$site['LID'])=="N"){
                continue;
            }
            if(self::getOption("LOGIN",$site['LID'])=="N"){
                continue;
            }
            if(self::getOption("PASSWORD",$site['LID'])=="N"){
                continue;
            }
            if(self::getOption("EMAIL",$site['LID'])=="N"){
                continue;
            }

            if(self::getOption("INN",$site['LID'])=="N"){
                continue;
            }

            if(self::getOption("COMPANY_NAME",$site['LID'])=="N"){
                continue;
            }
    
            if(self::getOption("COMMENT",$site['LID'])=="N"){
                continue;
            }
            if(self::getOption("IBLOCK_ID",$site['LID'])=="N"){
                continue;
            }
            if(self::getOption("PRODUCT_LINK",$site['LID'])=="N"){
                continue;
            }
            if(self::getOption("ORDER_OZON_STATUS",$site['LID'])=="N"){
                continue;
            }
            if(self::getOption("USER",$site['LID'])=="N"){
                continue;
            }
            if(self::getOption("PERSON_TYPE",$site['LID'])=="N"){
                continue;
            }
            if(self::getOption("ORDER_PAYMENT",$site['LID'])=="N"){
                continue;
            }
            
            if(self::getOption("ORDER_DELIVERY",$site['LID'])=="N"){
                continue;
            }
            $orders=self::getOzonOrders(50,$site['LID']);
           

            foreach($orders->result as $order){
                $order=(array) $order;
               global $DB;
               $result = $DB->Query("SELECT * FROM `b_sale_order` WHERE `OZON_ID`= '".$order['order_id']."'");
            
               if(!$result->Fetch()){
                self::createOrder($order,$site['LID']);
               }
               
            }
        }
       

        return '\OzonOrderImport::Agent();';
    }

    public static function getSites()
    {
        $SITES = array();
        $rsSites = \CSite::GetList($by = "sort", $order = "desc");
        while ($arSite = $rsSites->Fetch()) {
            $SITES[] = $arSite;
        }
        return $SITES;
    }

    public static function getIblockFields($IBLOCK_ID)
    {
        if (!CModule::IncludeModule('iblock'))
            return false;
        $fields = array(
            array('ID' => 'ID', "NAME" => "ID"),
            array('ID' => 'XML_ID', 'NAME' => 'XML_ID'),
            array('ID' => 'CODE', 'NAME' => 'Символьный код')
        );



        $res = CIBlock::GetProperties($IBLOCK_ID ?? 0);
        while ($res_arr = $res->Fetch()) {
            $fields[] = array('ID' => $res_arr['CODE'], 'NAME' => $res_arr['NAME']);
        }
       
        return $fields;
    }

    public static function getOption($PARAM, $SITE_ID = SITE_ID)
    {
        return Option::get(static::$MODULE_ID, 'OPTION_' . $PARAM . '_' . $SITE_ID, "N");
    }

    public static function getApiLink($SITE_ID = SITE_ID)
    {
        return Option::get(static::$MODULE_ID, 'OPTION_API_LINK_' . $SITE_ID, "N");
    }
    public static function getLogin($SITE_ID = SITE_ID)
    {
        return Option::get(static::$MODULE_ID, 'OPTION_LOGIN_' . $SITE_ID, "N");
    }
    public static function getPassword($SITE_ID = SITE_ID)
    {
        return Option::get(static::$MODULE_ID, 'OPTION_PASSWORD_' . $SITE_ID, "N");
    }

    public static function getUser($SITE_ID = SITE_ID)
    {
        return Option::get(static::$MODULE_ID, 'OPTION_USER_' . $SITE_ID, "N");
    }

    public static function getUserProfile($SITE_ID = SITE_ID)
    {
        return Option::get(static::$MODULE_ID, 'OPTION_USER_PROFILE_' . $SITE_ID, "N");
    }
    
    public static function getComment($SITE_ID = SITE_ID)
    {
        return Option::get(static::$MODULE_ID, 'OPTION_COMMENT_' . $SITE_ID, "N");
    }
    public static function getIblockId($SITE_ID = SITE_ID)
    {
        return Option::get(static::$MODULE_ID, 'OPTION_IBLOCK_ID_' . $SITE_ID, "N");
    }
    public static function getProductLink($SITE_ID = SITE_ID)
    {
        return Option::get(static::$MODULE_ID, 'OPTION_PRODUCT_LINK_' . $SITE_ID, "N");
    }

    public static function getOrderOzonStatus($SITE_ID = SITE_ID)
    {
        return Option::get(static::$MODULE_ID, 'OPTION_ORDER_OZON_STATUS_' . $SITE_ID, "N");
    }

    public static function getOrderPayment($SITE_ID = SITE_ID)
    {
        return Option::get(static::$MODULE_ID, 'OPTION_ORDER_PAYMENT_' . $SITE_ID, "N");
    }

    public static function getOrderPaymentStatus($SITE_ID = SITE_ID)
    {
        return Option::get(static::$MODULE_ID, 'OPTION_ORDER_PAYMENT_STATUS_' . $SITE_ID, "N");
    }

    public static function getOrderDelivery($SITE_ID = SITE_ID)
    {
        return Option::get(static::$MODULE_ID, 'OPTION_ORDER_DELIVERY_' . $SITE_ID, "N");
    }



    public static function request($method, $url, $params = array(),$site_id=SITE_ID)
    {
        $headers = array(
            "Host" => parse_url(self::getApiLink($site_id), PHP_URL_HOST),
            "Content-Type" => "application/json",
            "Client-Id" => self::getLogin($site_id),
            "Api-Key" => self::getPassword($site_id),
        );

        if (!in_array($params) && (count($params)) == 0) {
            $params = array();
        }

        $client = new Client(["headers" => $headers, 'base_uri' => self::getApiLink($site_id), 'body' => json_encode($params)]);
        $response = $client->request($method, $url);
        $body = $response->getBody();
        return json_decode($body->getContents());
    }

    public static function getOzonOrders($limit = 50,$site_id=SITE_ID)
    {
        $sicle = gmdate("c", time() - 60 * 60 * 24 * 7);
        $to = gmdate("c", time());
        $limit = intval($limit);
        $status = self::getOrderOzonStatus($site_id);

        $params = array(
            "dir" => "asc",
            "filter" => array(
                "since" => $sicle,
                "to" => $to,
                "status" => $status != "N" ? $status : "awaiting_packaging"
            ),
            "limit" => $limit,
        );

        return self::request("POST", "/v2/posting/fbs/list", $params,$site_id);
    }

    public static function getAttributes($category_id = 17037056, $language = "RU",$site_id=SITE_ID)
    {
        $limit = intval($limit);
        $params = array(
            "category_id" => $category_id,
            "language" => $language
        );
        return self::request("POST", "/v1/category/attribute", $params,$site_id);
    }

    public static function getProducts($page = 1, $limit = 100,$site_id=SITE_ID)
    {
        $limit = intval($limit);
        $params = array(
            "page" => $page,
            "page_size" => $limit
        );
        return self::request("POST", "/v1/product/list", $params,$site_id);
    }


    public static function createOrder($orderData, $site_id = SITE_ID)
    {
        $orderData=(array)$orderData;
        global $USER,$DB;

        if (!Loader::includeModule("sale") && !Loader::includeModule("catalog") && !Loader::includeModule("iblock")) die();
        $currencyCode = CurrencyManager::getBaseCurrency();
        // Создаёт новый заказ
        $order = Order::create($site_id, self::getUser($site_id));
 
        $order->setField('CURRENCY', $currencyCode);
        $comment = self::getComment($site_id);
        if ($comment) {
            foreach($orderData as $key  => $data){
                if($key !== "products"){
                    $comment=str_replace("#".$key."#",$data,$comment);
                }
            }
            $order->setField('USER_DESCRIPTION', $comment); // Устанавливаем поля комментария покупателя
        }
        
        // Создаём корзину с одним товаром
        $IBLOCK_ID_BASE = self::getIblockId($site_id);
        $IBLOCK_ID_OFFER = CCatalog::GetByID($IBLOCK_ID_BASE)['OFFERS_IBLOCK_ID'] ?? 0;
        $PROP_LINK = self::getProductLink($site_id);
        if ($PROP_LINK !== "ID" && $PROP_LINK !== "XML_ID" && $PROP_LINK !== "CODE") {
            $PROP_LINK = "PROPERTY_" . $PROP_LINK;
        }

        $basket = Basket::create($site_id);
        $products=Array();
        foreach ($orderData['products'] as $product) {
            $product=(array)$product;
            $productId = 0;
            $rsElement = CIBlockElement::GetList(
                $arOrder  = array("SORT" => "ASC"),
                $arFilter = array(
                    "ACTIVE"    => "Y",
                    "IBLOCK_ID" => array($IBLOCK_ID_BASE, $IBLOCK_ID_OFFER),
                    $PROP_LINK => $product['offer_id']
                ),
                false,
                false,
                $arSelectFields = array("ID")
            );
    
            if ($arElement = $rsElement->fetch()) {
                // var_dump($arElement,$product);
                $productId = intval($arElement['ID']);
                $products[$productId]=$product;
                $item = $basket->createItem('catalog', $productId);
                $item->markFieldCustom('PRICE');
                $item->setFields(array(
                    'QUANTITY' => $product['quantity'],
                    'CURRENCY' => $currencyCode,
                    'PRICE' => $product['price'],
                    'LID' => $site_id,
                ));
            }
        }

        $order->setBasket($basket);
   
    
        // Создаём одну отгрузку и устанавливаем способ доставки - "Без доставки" (он служебный)
        $shipmentCollection = $order->getShipmentCollection();
        foreach ($basket as $item) {

            $shipment = $shipmentCollection->createItem();
            $service = Delivery\Services\Manager::getById(self::getOrderDelivery($site_id));
            $shipment->setFields(array(
                'DELIVERY_ID' => $service['ID'],
                'DELIVERY_NAME' => $service['NAME'],
            ));
            $shipmentItemCollection = $shipment->getShipmentItemCollection();
            $shipmentItem = $shipmentItemCollection->createItem($item);
            $shipmentItem->setQuantity($item->getQuantity());
        }
        // Создаём оплату со способом #1
        $paymentCollection = $order->getPaymentCollection();
        $payment = $paymentCollection->createItem();
        $paySystemService = PaySystem\Manager::getObjectById(self::getOrderPayment($site_id));
        $payment->setFields(array(
            'PAY_SYSTEM_ID' => $paySystemService->getField("PAY_SYSTEM_ID"),
            'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME")
            // 'PAID'=>self::getOrderPaymentStatus($site_id)
        ));

        // Устанавливаем свойства
        $propertyCollection = $order->getPropertyCollection();

      
   

        $db_props = $DB->Query("SELECT * FROM `b_sale_user_props` WHERE `ID`='".self::getUserProfile($site_id)."'");
        if ($userProfile = $db_props->Fetch()){

            $order->setPersonTypeId($userProfile['PERSON_TYPE_ID']);
            $db_propVals = $DB->Query("SELECT * FROM `b_sale_user_props_value` WHERE `USER_PROPS_ID`='".$userProfile['ID']."'");

            while ($userProfileValue = $db_propVals->Fetch())
            { 

                if($userProfileValue['VALUE']){
                    $propObject = $propertyCollection->getItemByOrderPropertyId($userProfileValue['ORDER_PROPS_ID']);
                    $propObject->setValue($userProfileValue['VALUE']);
                }

            }
           
        }

        $nameProp = $propertyCollection->getPayerName();
        $nameProp->setValue($orderData['posting_number']);
        $order->doFinalAction(true);
        $result = $order->save();
        $basket->save();
        $ORDER_ID=$order->getId();
        if($ORDER_ID){
            $DB->Query("UPDATE `b_sale_order` SET `OZON_ID`='".$orderData['order_id']."' WHERE `ID`='".$ORDER_ID."'");

            return $ORDER_ID;
        }

        return false;
    }
}
