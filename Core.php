<?php

include_once 'Database.php';

trait Core
{



    public $sleep;
    public $method;
    public $conversationDir = "Conversations";

    private $host;
    private $username;
    private $password;
    private $database;

    public function Init($setting)
    {
        set_time_limit(0);
        error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        date_default_timezone_set('Asia/Tehran');
        $this->token = $setting['token'];
        $this->host = $setting['database']['host'];
        $this->username = $setting['database']['username'];
        $this->password = $setting['database']['password'];
        $this->database = $setting['database']['database'];
        $this->sleep = ($setting['sleep']) ? $setting['sleep'] : 0.2;
        $this->method = ($setting['method']) ? $setting['method'] : 'long_polling';
        $this->deleteWebhookInLongPolling();
        $this->connectDatabase();
    }

    public function connectDatabase()
    {
        DB::connect($this->host, $this->username, $this->password, $this->password);
    }

    public function deleteWebhookInLongPolling()
    {
        if($this->method == 'long_polling')
            $this->deleteWebhook();
    }

    public function callerFunction()
    {
        // return caller function
        $trace = debug_backtrace();
        
        return $trace[2]['function'];
    }

    public function buttonKeyboard($rows, $optional = null)
    {
        $keyboard = [
            'keyboard' => $rows,
            'resize_keyboard' => true,
            $optional
        ];
        $keyboard = json_encode($keyboard);
        
        return $keyboard;
    }

    public function inlineKeyboard($rows)
    {
        $keyboard = [
            'inline_keyboard' => $rows
        ];
        $keyboard = json_encode($keyboard);
        
        return $keyboard;
    }

    public function getMessages()
    {
        if($this->method == 'webhook')
            $messages = array(json_decode(file_get_contents( 'php://input' ), true));
        else if($this->method == 'long_polling')
            $messages = $this->getUpdates();

        return $messages;
    }

    public function saveState($state)
    {
        /**
         * create table in your database with info in below
         * table name: states
         * columns:
         * id: Primary Key, Auto Increament
         * chat_id: INT, UNIQUE
         * state: VARCHAR
         */
        // save state of user to start conversation
        $result = DB::insert("INSERT INTO states (chat_id, state) VALUES (? , ?)", [ $this->chatId(), $state ] );
        
        return $result;
    }

    public function getState()
    {
        // get state of user when is in conversation
        $state = DB::select("SELECT state FROM states WHERE chat_id = ?", [ $this->chatId() ] );
        
        if($state->num_rows > 0)
            while ($row = $state->fetch_object())
                return $row->state;
        else
            return false;
    }

    public function updateState($state)
    {
        // update state of user when answers to conversation
        $result = DB::update("UPDATE states SET state = ? WHERE chat_id = ? LIMIT 1", [ $state, $this->chatId() ] );
        
        return $result;
    }

    public function deleteState()
    {
        // delete state of user when conversation is finished
        $result = DB::delete("DELETE FROM states WHERE chat_id = ? LIMIT 1", [ $this->chatId() ] );
        
        return $result;
    }

    public function updateOrSaveState($state)
    {
        // update state of user in conversation if saved before else save it
        $check_state = DB::select("SELECT state FROM states WHERE chat_id = ? LIMIT 1", [ $this->chatId() ] );
        
        if($check_state->num_rows > 0)
            $result = DB::update("UPDATE states SET state = ? WHERE chat_id = ? LIMIT 1", [ $state, $this->chatId() ] );
        else
            $result = DB::insert("INSERT INTO states (chat_id, state) VALUES (? , ?)", [ $this->chatId(), $state ] );

        return $result;
    }

    public function chatId()
    {
        if(isset($this->message->message))
            $chat_id = $this->message->message->chat->id;
        else
            $chat_id = $this->message->chat->id;

        return $chat_id;
    }

    public function saveConversationData($input, $overwrite = null)
    {
        /**
         * save the data that user has sent in any step of conversation
         * data will be appended in user`s conversation file
         * to overwrite data just pass true for $overwrite variable
         */

        if(isset($overwrite) && $overwrite === true)
            $mod = "w";
        else if(isset($overwrite) && $overwrite !== true)
            exit("$overwrite is invalid, pass true if you want to overwrite data in ".__FUNCTION__." method");
        else if(!$overwrite)
            $mod = "a";
        if(!file_exists($this->conversationDir))
            mkdir($this->conversationDir);
        
        $userConversationFile = fopen("$this->conversationDir/".$this->chatId().".txt", $mod);
        fwrite($userConversationFile, $input);
        fclose($userConversationFile);
    }

    public function getConversationData()
    {
        $userConversationFile = $this->conversationDir."/".$this->chatId().".txt";
        
        if(file_exists($userConversationFile))
            return file_get_contents($userConversationFile);
        else
            return false;
    }

    public function deleteConversationData()
    {
        $userConversationFile = $this->conversationDir."/".$this->chatId().".txt";
        
        if(file_exists($userConversationFile))
            unlink($userConversationFile);
    }

    public function saveLastUpdateId()
    {
        $file = 'update_id.txt';
        if($this->method == 'long_polling')
        {
            if(!file_exists($file))
                fopen($file, 'w');

            file_put_contents($file, $this->message->update_id);
        }
    }

    public function apiGetContent($params = null)
    {
        $url = "https://api.telegram.org/bot$this->token/".$this->callerFunction();
        $data = $this->curlExec($url, $params);

        return $data;
    }

    public function curlExec($url, $params = null)
    {
        $ch = curl_init();
        $data = http_build_query($params);

        if(!$params)
            $request = $url;
        else
            $request = $url."?".$data;

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $request);
        $response = curl_exec($ch);

        return $response;
    }


}