<?php
namespace Colbeh\Consts;

class Helper {

    public static function sucBack($message){
        return json_encode(['result'=>'success', 'message'=>$message]);
    }

    public static function errorBack($message){
        response( json_encode(['result'=>'error_message', 'message'=>$message]))->send();
        exit();
    }


}
