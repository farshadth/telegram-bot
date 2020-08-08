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
    public $commands;

    public function __construct()
    {
        // initial setting
        $this->init([
            'bot_token' => "Bot_Token",
            'sleepPerRequest'  => 0.2,   // optional, sleep per request in long_polling method, default is 0.5 second
            'method' => 'long_polling',  // optional, "long_polling" or "webhook", default is "long_polling"
            'mysql' => [                 // optional, if you dont want to use database just remove it
                'host'     => 'HOST',
                'username' => 'USERNAME',
                'password' => 'PASSWORD',
                'database' => 'DATABASE',
            ],
        ]);

        // set commands
        $this->setCommands([
            'start,/start' => 'startCommand',
            'help' => 'helpCommand',
        ]);
    }

    public function run()
    {
        $messages = $this->getMessages();

        foreach($messages as $this->message)
        {
            $this->saveLastUpdateId(); // save last update id

            // when user send message
            if($this->MessageSent())
            {
                // code here
                $this->messageIsCommand($this);
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
            }
        }
    }
    

}

$obj = new Bot();

if($obj->method == 'webhook')
    $obj->run();
else if($obj->method == 'long_polling')
{
    while (true)
    {
        $obj->run();
        sleep($obj->sleepPerRequest);
    }
}