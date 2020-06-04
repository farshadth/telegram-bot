<?php

include 'Database.php';

trait Functions
{



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
        $result = @DB::insert("INSERT INTO states (chat_id, state) VALUES (? , ?)", [ $this->getChatId(), $state ] );
        return $result;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function getState()
    {
        // get state of user when is in conversation
        $state = @DB::select("SELECT state FROM states WHERE chat_id = ?", [ $this->getChatId() ] );
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
        $result = @DB::update("UPDATE states SET state = ? WHERE chat_id = ? LIMIT 1", [ $state, $this->getChatId() ] );
        return $result;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function deleteState()
    {
        // delete state of user when conversation is finished
        $result = @DB::delete("DELETE FROM states WHERE chat_id = ? LIMIT 1", [ $this->getChatId() ] );
        return $result;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function updateOrSaveState($state)
    {
        // update state of user in conversation if saved before else save it
        $check_state = @DB::select("SELECT state FROM states WHERE chat_id = ? LIMIT 1", [ $this->getChatId() ] );
        if($check_state->num_rows > 0)
            $result = @DB::update("UPDATE states SET state = ? WHERE chat_id = ? LIMIT 1", [ $state, $this->getChatId() ] );
        else
            $result = @DB::insert("INSERT INTO states (chat_id, state) VALUES (? , ?)", [ $this->getChatId(), $state ] );

        return $result;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    public function getChatId()
    {
        if(isset($this->message->message))
            $chat_id = $this->message->message->chat->id;
        else
            $chat_id = $this->message->chat->id;

        return $chat_id;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////



}