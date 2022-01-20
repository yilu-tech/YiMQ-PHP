<?php


namespace YiluTech\YiMQ;


use Tuupola\Ksuid;

class Helpers
{

    public static function getNow():\DateTime{
        return new \DateTime('now',new \DateTimeZone("Asia/Shanghai"));
    }
    public static function addSeconds(\DateTime $datetime,int $seconds=null):\DateTime{
        $datetime->add(date_interval_create_from_date_string("$seconds seconds"));
        return $datetime;
    }
    public static function formartTime(\DateTime $datetime):string{
        return $datetime->format("Y-m-d H:i:s.v");
    }

    public static function ksuid():string{
        return (new Ksuid())->string();
    }
}