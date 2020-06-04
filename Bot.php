<?php

include 'Functions.php';
include 'Api.php';

class Bot extends Api
{




    use Functions;
    public $message;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function __construct()
    {
        set_time_limit(0);
        error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        date_default_timezone_set('Asia/Tehran');
        @DB::connect('localhost' , 'root' , '' , 'digital_coins');
        $this->token = "496504159:AAGjs_ud4woiyNBT3pXNXt2kCt_cP0ovYIM";
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function Run()
    {
        $messages = $this->getUpdates();
        foreach($messages as $this->message)
        {
            $last_update_id = $this->message['update_id'];

            if(isset($this->message['message']))
            {
                exit($this->message['chat']['id']);
                $this->message = $this->message['message'];
                $this->sendMessage($this->message['chat']['id'], 'hi');
            }
            else if(isset($this->message['edited_message']))
            {
                $this->message = $this->message['edited_message'];
                // code here
            }
            else if(isset($this->message['callback_query']))
            {
                $this->message = $this->message['callback_query'];
                // code here
            }

            // save last update id
            file_put_contents('update_id.txt' , $last_update_id);
        }
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$obj = new Bot();
while (true)
{
    try
    {
        $obj->Run();
    }
    catch (Exception $e)
    {
        echo "Error: ".$e->getMessage()." Line: ".$e->getLine();
    }
    sleep(0.2);
}