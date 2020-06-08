<?php

include 'Database.php';

trait Functions
{



    public $conversationDir = "Conversations";
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function callerFunction()
    {
        // return caller function
        $trace = debug_backtrace();
        return $trace[2]['function'];
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function buttonKeyboard($rows, $optional = null)
    {
        $keyboard =
        [
            'keyboard' => $rows,
            'resize_keyboard' => true,
            $optional
        ];
        $keyboard = json_encode($keyboard);
        return $keyboard;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function inlineKeyboard($rows)
    {
        $keyboard =
        [
            'inline_keyboard' => $rows
        ];
        $keyboard = json_encode($keyboard);
        return $keyboard;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function updateState($state)
    {
        // update state of user when answers to conversation
        $result = DB::update("UPDATE states SET state = ? WHERE chat_id = ? LIMIT 1", [ $state, $this->chatId() ] );
        return $result;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function deleteState()
    {
        // delete state of user when conversation is finished
        $result = DB::delete("DELETE FROM states WHERE chat_id = ? LIMIT 1", [ $this->chatId() ] );
        return $result;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function chatId()
    {
        if(isset($this->message->message))
            $chat_id = $this->message->message->chat->id;
        else
            $chat_id = $this->message->chat->id;

        return $chat_id;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
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
        $chat_id = $this->chatId();
        $userConversationFile = fopen("$this->conversationDir/$chat_id.txt", $mod);
        fwrite($userConversationFile, $input);
        fclose($userConversationFile);
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function getConversationData()
    {
        $userConversationFile = $this->conversationDir."/".$this->chatId().".txt";
        if(file_exists($userConversationFile))
            return file_get_contents($userConversationFile);
        else
            return false;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function deleteConversationData()
    {
        $userConversationFile = $this->conversationDir."/".$this->chatId().".txt";
        if(file_exists($userConversationFile))
            unlink($userConversationFile);
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



}