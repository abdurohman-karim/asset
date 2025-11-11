<?php

namespace App\Services;

class Helper
{
    static function phoneFormat($phone):string
    {
        $phone = "+998".substr(preg_replace('/[^0-9]/', '', $phone), -9);
        return $phone;
    }
    static function phoneFormatDB($phone):string
    {
        return substr(preg_replace('/[^0-9]/', '', $phone), -9);
    }

    static function convert_to_latin($text)
    {
        $cyr = [
            'қ','Қ','а', 'б', 'в', 'г', 'д', 'е', 'ё', 'ж', 'з', 'и', 'й', 'к', 'л', 'м', 'н', 'о', 'п',
            'р', 'с', 'т', 'у', 'ф', 'х', 'ц', 'ч', 'ш', 'щ', 'ъ', 'ы', 'ь', 'э', 'ю', 'я',
            'А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П',
            'Р', 'С', 'Т', 'У', 'Ў', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я'
        ];
        $lat = [
            'q','Q','a', 'b', 'v', 'g', 'd', 'e', 'yo', 'j', 'z', 'i', 'y', 'k', 'l', 'm', 'n', 'o', 'p',
            'r', 's', 't', 'u', 'f', 'h', 'ts', 'ch', 'sh', 'sh', 'a', 'i', 'y', 'e', 'yu', 'ya',
            'A', 'B', 'V', 'G', 'D', 'E', 'Yo', 'J', 'Z', 'I', 'Y', 'K', 'L', 'M', 'N', 'O', 'P',
            'R', 'S', 'T', 'U', 'O', 'F', 'H', 'Ts', 'Ch', 'Sh', 'Sh', 'A', 'I', 'Y', 'e', 'Yu', 'Ya'
        ];
        return str_replace($cyr, $lat, $text);
    }

    static function phoneShowFormatting($phone):string
    {
        $phoneNumber = substr(preg_replace("/[^0-9]/", '', $phone),-9);
        return sprintf("(%s) %s-%s-%s",
            substr($phoneNumber, 0, 2),
            substr($phoneNumber, 2, 3),
            substr($phoneNumber, 5, 2),
            substr($phoneNumber, 7, 2)
        );
    }

    static function forbiddenAccess():void
    {
        header('HTTP/1.1 403 Forbidden');
        die();
    }

    static function checkPermission($permission):void
    {
        if (!auth()->user()->hasPermission($permission))
            self::forbiddenAccess();
    }
}
