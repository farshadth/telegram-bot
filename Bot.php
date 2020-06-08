<?php

include 'Functions.php';
include 'Api.php';

/**
 * @author Farshad Tofighi
 * @see https://farshadth.ir
 * @see https://github.com/farshadth/Telegram
 * @version  1.0
 */

class Bot extends Api
{



    use Functions;
    public $sleep;
    public $method;
    public $message;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function __construct()
    {
        $this->token = "Bot_Token";
        set_time_limit(0);
        error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        date_default_timezone_set('Asia/Tehran');
        @DB::connect('HOST' , 'USERNAME' , 'PASSWORD' , 'DATABASE');
        $this->sleep = 0.2;              // sleep per request in long_polling method
        $this->method = 'long_polling'; // "long_polling" or "webhook"
        if($this->method == 'long_polling')
            $this->deleteWebhook();
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function Run()
    {
        if($this->method == 'webhook')
            $messages = array(json_decode(file_get_contents( 'php://input' ), true));
        else if($this->method == 'long_polling')
            $messages = $this->getUpdates();
        foreach($messages as $this->message)
        {
            $last_update_id = $this->message->update_id;

            if(isset($this->message->message))
            {
                // when user send message
                $this->message = $this->message->message;

                if($this->message->text == '/start')
                    $this->start_command();
                else if($this->message->text == '/help')
                    $this->help_command();
            }
            else if(isset($this->message->edited_message))
            {
                // when user edit his message
                $this->message = $this->message->edited_message;
                // code here
            }
            else if(isset($this->message->callback_query))
            {
                // when user click on inline keyboard
                $this->message = $this->message->callback_query;
                // code here
            }

            // save last update id
            if($this->method == 'long_polling')
                file_put_contents('update_id.txt' , $last_update_id);
        }
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function start_command()
    {
        $text = "start command";
        // set button keyboard
        $keyboard1 =
        [
            [
                [ 'text' => 'Button 1' ],
                [ 'text' => 'Button 2' ]
            ],
            [
                [ 'text' => 'Button 3' ],
            ],
        ];
        // set inline keyboard
        $keyboard2 =
        [
            [
                [ 'text' => 'Button', 'callback_data' => 'data' ],
            ],
            [
                [ 'text' => 'farshadth.ir', 'url' => 'https://farshadth.ir' ],
            ],
        ];
        $keyboard = $this->buttonKeyboard($keyboard1);
//        $keyboard = $this->inlineKeyboard($keyboard2); // use for send inline keyboard instead
        $optional = "&reply_markup=$keyboard";
        $this->sendMessage($this->message->chat->id, $text, $optional);
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function help_command()
    {
        $text = "help command";
        $this->sendMessage($this->message->chat->id, $text);
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$obj = new Bot();
if($obj->method == 'webhook')
    $obj->Run();
else if($obj->method == 'long_polling')
{
    while (true)
    {
        $obj->Run();
        sleep($obj->sleep);
    }
}