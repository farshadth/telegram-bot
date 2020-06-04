<?php

include 'Functions.php';
include 'Api.php';

class Bot extends Api
{



    use Functions;
    public $message;
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function __construct()
    {
        set_time_limit(0);
        error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        date_default_timezone_set('Asia/Tehran');
        @DB::connect('localhost' , 'Username' , 'Password' , 'DB_Name');
        $this->token = "Bot_Token";
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function Run()
    {
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
            file_put_contents('update_id.txt' , $last_update_id);
        }
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function start_command()
    {
        $text = "start command";
        // button keyboard
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
        // inline keyboard
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
        $this->saveState($this->message->chat->id, "user_is_in_help_command"); // save state of user for conversation
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



}
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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