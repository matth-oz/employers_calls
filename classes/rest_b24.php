<?php

namespace Mattweb\RestB24;

class RestBx24{
    protected $webhookUrl;
    protected $batchSize = 50;

    /**
     * Конструктор класса
     * @param string $webhookUrl URL вебхука Битрикс24 (например: https://your-domain.bitrix24.ru/rest/1/xxxxxxx/)
     */
    public function __construct($webhookUrl){
        $this->webhookUrl = rtrim($webhookUrl, '/');
    }


    /**
     * Выполнение запроса к REST API
     * @param string $method Метод API
     * @param array $params Параметры запроса
     * @return array Ответ API
     */
    public function callMethod($method, $params = [])
    {
        $url = $this->webhookUrl . '/' . $method;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode != 200) {
            throw new Exception("HTTP Error: " . $httpCode);
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['error'])) {
            throw new Exception("API Error: " . $result['error_description'] ?? $result['error']);
        }
        
        return $result;
    }

}