<?php

include_once 'Core.php';
include_once 'Api.php';

/**
 * @author Farshad Tofighi
 * @see https://farshadth.ir
 * @see https://github.com/farshadth/Telegram
 * @version  1.0
 */

class Bot
{


    use Core, Api;

    public $message;

    public function __construct()
    {
        $setting = [
            'bot_token' => "Bot_Token",
            'database' => [             // optional, if you dont want to use database just remove database key
                'host'     => 'HOST',
                'username' => 'USERNAME',
                'password' => 'PASSWORD',
                'database' => 'DATABASE',
            ],
            'sleep'  => 0.5,            // optional, sleep per request in long_polling method, default is 0.2 second
            'method' => 'long_polling', // optional, "long_polling" or "webhook", default is "long_polling"
        ];
        $this->Init($setting); // initial setting
    }
    
    public function Run()
    {
        $messages = $this->getMessages();

        foreach($messages as $this->message)
        {
            // save last update id
            $this->saveLastUpdateId();

            // when user send message
            if($this->MessageSent())
            {
                // code here
                if($this->getText() == '/start')
                    $this->startCommand();
                else if($this->getText() == '/help')
                    $this->helpCommand();
            }
            // when user edit his message
            else if($this->messageEdited())
            {
                // code here
            }
            // when user click on inline keyboard
            else if($this->keyboardClicked())
            {
                // code here
                if($this->getKeyboardData() == 'data')
                    $this->helpCommand();
            }
        }
    }
    
    public function startCommand()
    {
        $text = "start command";
        // set button keyboard
        $keyboard1 = [
            [
                [ 'text' => 'Button 1' ],
                [ 'text' => 'Button 2' ]
            ],
            [
                [ 'text' => 'Button 3' ],
            ],
        ];
        // set inline keyboard
        $keyboard2 = [
            [
                [ 'text' => 'Button', 'callback_data' => 'data' ],
            ],
            [
                [ 'text' => 'farshadth.ir', 'url' => 'https://farshadth.ir' ],
            ],
        ];
//        $keyboard = $this->buttonKeyboard($keyboard1);
        $keyboard = $this->inlineKeyboard($keyboard2); // use for send inline keyboard instead
        $this->sendMessage([
            'chat_id' => $this->getChatId(),
            'text' => $this->getFullName(),
            'reply_markup' => $keyboard
        ]);
    }
    
    public function helpCommand()
    {
        $text = "help command";
        $this->sendMessage([
            'chat_id' => $this->getChatId(),
            'text' => $text,
        ]);
    }
    

}

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