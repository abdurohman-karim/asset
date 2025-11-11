<?php

namespace App\Services;

use Illuminate\Http\Request;

class LogWriter
{

    public static function info($message)
    {
        return self::log($message,'info','info');
    }

    public static function getBalanceErrors($url,$request, $response, $processing)
    {
        $request = json_encode($request,128);
        $response = json_encode($response,128);
        $message = "Time [".date('H:i:s')."]\n";
        $message.= "URL------|  $url\n";
        $message.= "Request--|  \n$request\n";
        $message.= "Response-|  \n$response\n";
        return self::log($message,date('M-d'),"AutopayBalance/$processing/".date('Y-m'));
    }

    public static function exception(\Exception $exception,$folder = null)
    {
        if (is_null($folder))
            $folder = str_replace('.','_',basename($exception->getFile()));

        $message = "Time [".date('H:i:s')."]\n";
        $message.= "\nFile-----|  ".$exception->getFile();
        $message.= "\nLine-----|  ".$exception->getLine();
        $message.= "\nMessage--|  ".$exception->getMessage();
        $message.= "\nCode-----|  ".$exception->getCode();
        $message .= "\n------------------------------------------------------------------------------------------------------------------------\n";
        return self::log($message,date('M-d'),"Exceptions/$folder");
    }

    public static function requests(Request $request, $response,$execution_time = null)
    {
        $headers = logObj($request->header());
        $body = logObj($request->all());
        $response = logObj($response);

        $message = "Time [".date('H:i:s')."]\n";
        $message.= "Headers--|  $headers\n";
        $message.= "Body-----|  $body\n";
        $message.= "Response-|  $response\n";
        $message.= "Execution|  $execution_time ms\n------------------------------------------------------------------------------------------------------------------------\n";
        return self::log($message,date('M-d'),"API/".date('Y-m'));
    }

    public static function sendedSMS($message)
    {
        return self::log($message,date('M-d'),'SMS/'.date('Y-m'));
    }

    public static function exceptions($message)
    {
        return self::log($message,date('M-d'),'Exceptions/'.date('Y-m'));
    }

    public static function humo_agrobank($pinfl,$response)
    {
        $response = json_encode($response);
        $message = "Time [".date('H:i:s')."]\n";
        $message.= "Pinfl--|  $pinfl\n";
        $message.= "Response-|  $response\n";
        $message.= "------------------------------------------------------------------------------------------------------------------------\n";
        return self::log($message,date('M-d'),'Agro/'.date('Y-m'));
    }

    public static function humo_callback($pinfl,$cards,$type = "Success")
    {
        if ($type == 'Success')
        {
            $data = json_encode($cards['data']);
            $news = json_encode($cards['new']);
        }
        else
            $response = json_encode($cards);

        $message = "Time [".date('H:i:s')."]\n";
        $message.= "Pinfl--|  $pinfl\n";
        if ($type == 'Success')
        {
            $message.= "Cards-|  $data\n";
            $message.= "News -|  $news\n";
        }
        else
            $message.= "Response-|  $response\n";
        $message.= "------------------------------------------------------------------------------------------------------------------------\n";
        return self::log($message,date('M-d'),"Callbacks/$type/".date('Y-m'));
    }

    public static function payment_log($message)
    {
        return self::log($message,date('M-d'),'Payment/'.date('Y-m'));
    }
    public static function avtospisaniyaUzcard($message)
    {
        return self::log($message,date('M-d'),'AvtospisaniyaUzcard/'.date('Y-m'));
    }
    public static function avtospisaniyaHumo($message)
    {
        return self::log($message,date('M-d'),'AvtospisaniyaHumo/'.date('Y-m'));
    }
    public static function avtospisaniya($message)
    {
        return self::log($message,date('M-d'),'Avtospisaniya/'.date('Y-m'));
    }

    public static function user_activity($message,$file = 'UserActivity' )
    {
        $message = "\n[\"DateTime\"]: [".date('Y-m-d H:i:s')."]\n".$message;
        return self::log($message,$file,'UserActivity');
    }

    // main log writer function
    public static function log($content, $file = 'app', $dir = 'AppLogs')
    {
        self::dirChecker($dir);
        $path = storage_path("logs/".$dir."/".$file.'.log');
        return fwrite(fopen($path,'a'),$content."\n");
    }

    // check existing of directory and create if not exists
    public static function dirChecker($dir)
    {
        $directories = explode("/",$dir);
        $dir_path = storage_path("logs");

        foreach ($directories as $directory) {

            $dir_path .= "/".$directory;

            if(is_dir($dir_path) === false )
            {
                mkdir($dir_path);
            }
        }
    }


}
