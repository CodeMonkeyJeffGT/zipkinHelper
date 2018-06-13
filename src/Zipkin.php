<?php
namespace ZipkinHelper;

use ZipkinHelper\ZipkinSpan;

class Zipkin
{
    private static $url;
    private static $traceId;
    private static $id;
    private static $parentId;
    private static $localEndpoint;
    private static $spans;

    public static function init($url, $localEndpoint, $traceId = false)
    {
        self::$url = $url;
        self::$traceId = (empty($traceId) ? self::formId() : $traceId);
        if ( ! is_array($localEndpoint)) {
            $localEndpoint = array(
                'serviceName' => $localEndpoint,
            );
        }
        self::$localEndpoint = $localEndpoint;
        self::$spans = array();
    }

    public static function serverReceive($name, $parentId = false, $remoteEndpoint = array(), $id = false)
    {
        self::$id = empty($id) ? self::formId() : $id;
        self::$spans[] = new ZipkinSpan(
            self::$traceId,
            self::$id,
            $parentId,
            $name,
            'SERVER',
            self::$localEndpoint,
            array(),
            $remoteEndpoint
        );
        self::$parentId = self::$id;
    }

    public static function clientSend($name, $tags = array(), $remoteEndpoint = array(), $id = false)
    {
        $parentId = self::$parentId;
        self::$id = empty($id) ? self::formId() : $id;
        self::$spans[] = new ZipkinSpan(
            self::$traceId,
            self::$id,
            $parentId,
            $name,
            'CLIENT',
            self::$localEndpoint,
            $tags,
            $remoteEndpoint
        );
    }

    public static function clientReceive()
    {
        self::$spans[count(self::$spans) - 1]->end();
    }

    public static function serverSend()
    {
        self::$spans[0]->end();
        self::post();
    }

    public static function produce($name, $id = false, $tags = array())
    {
        $parentId = self::$parentId;
        self::$id = empty($id) ? self::formId() : $id;
        self::$spans[] = new ZipkinSpan(
            self::$traceId,
            self::$id,
            $parentId,
            $name,
            'PRODUCER',
            self::$localEndpoint,
            $tags,
            self::$remoteEndpoint
        );
    }

    public static function consume($name, $id = false, $tags = array())
    {
        $parentId = self::$parentId;
        self::$id = empty($id) ? self::formId() : $id;
        self::$spans[] = new ZipkinSpan(
            self::$traceId,
            self::$id,
            $parentId,
            $name,
            'CONSUMER',
            self::$localEndpoint,
            $tags,
            self::$remoteEndpoint
        );
    }

    public static function getTraceId()
    {
        return self::$traceId;
    }

    public static function getParentId()
    {
        return self::$parentId;
    }

    public static function getid()
    {
        return self::$id;
    }

    private static function build()
    {
        foreach (self::$spans as $key => $value) {
            self::$spans[$key] = $value->get();
        }
    }

    private static function post()
    {
        self::build();
        $ch = curl_init(self::$url . '/api/v2/spans');
        $payload = json_encode(self::$spans);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        echo $result;
        curl_close($ch);
    }

    private static function formId()
    {
        return substr(md5(uniqid(mt_rand())), 0, 16);
    }
}