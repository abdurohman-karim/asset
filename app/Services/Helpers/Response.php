<?php

namespace App\Services\Helpers;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Validator;

class Response
{
    public static function successResponse($data)
    {
        return [
            'status' => true,
            'result' => $data,
            'error' => null
        ];
    }

    public function validationError($validation)
    {
        return [
            'status' => false,
            'error' => [
                'message' => $validation->errors()->first()
            ],
            'result' => null
        ];
    }

    public static function errorResponse($message,$data = [])
    {
        if (is_array($message)) $message = self::getErrorMessageFromResponse($message);

        if (count($data))
            return [
                'status' => false,
                'error' => [
                    'message' => $message,
                    'data' => $data
                ],
                'result' => null
            ];
        else
            return [
                'status' => false,
                'error' => [
                    'message' => $message
                ],
                'result' => null
            ];

    }

    public static function authFailed()
    {
        return [
            'status' => false,
            'error' => [
                'message' => "Authorization failed!"
            ],
            'result' => null
        ];
    }

    public function errorMethodUndefined($method = '')
    {
        return [
            'status' => false,
            'error' => [
                'message' => 'Метод '.$method.' не найден'
            ],
            'result' => null
        ];
    }

    /**
     * @param array $response
     * @return string $message of error
     */
    public static function getErrorMessageFromResponse(array $response):string
    {
        # initial set default info message
        $message = "Undefined error: ".json_encode($response);

        # find error message
        if (isset($response['error']))
        {
            if (is_string($response['error']))
                $message = $response['error'];

            elseif (isset($response['error']['message']))
            {
                if (isset($response['error']['message']['message']))
                    $message = $response['error']['message']['message'];
                elseif (is_array($response['error']['message'])) {
                    $response['error']['message'] = array_filter($response['error']['message']);
                    $message = array_shift($response['error']['message']);
                }
                else $message = $response['error']['message'];
            }
        }
        return $message;
    }

    public static function getMessageFromResponse(array $response):string
    {
        $message = "Undefined error: ".json_encode($response);

        # find error message
        if (isset($response['error']))
        {
            if (is_string($response['error']))
                $message = $response['error'];

            elseif (isset($response['error']['message']))
            {
                if (isset($response['error']['message']['message']))
                    $message = $response['error']['message']['message'];
                elseif (is_array($response['error']['message'])) {
                    $response['error']['message'] = array_filter($response['error']['message']);
                    $message = array_shift($response['error']['message']);
                }
                else $message = $response['error']['message'];
            }
        }

        if (isset($response['result']['respText'])){
            $message = $response['result']['respText'];
        } elseif(isset($response['exception']['message'])) {
            $message = $response['exception']['message'];
        }elseif(isset($response['result']['description'])) {
            $message = $response['result']['description'];
        }

        return $message;
    }


    public function validate(array $params, array $rules)
    {
        App::setLocale('ru');
        $validate = Validator::make($params,$rules);

        if ($validate->fails())
        {
            return self::validationError($validate);
        }
        return true;
    }
}
