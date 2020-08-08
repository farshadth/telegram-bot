<?php

include_once 'DB.php';

/**
 * Trait Core
 */
trait Core
{


    public float $sleepPerRequest;
    public string $method;
    public string $conversationDir = "Conversations";

    private $setting;
    private $host;
    private $username;
    private $password;
    private $database;

    /**
     * initial settings
     * @param $setting
     */
    public function init($setting)
    {
        $this->setting = $setting;
        set_time_limit(0);
        error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
        date_default_timezone_set('Asia/Tehran');
        $this->token = $setting['bot_token'];
        $this->sleepPerRequest = $setting['sleepPerRequest'] ?? 0.5;
        $this->method = $setting['method'] ?? 'long_polling';
        $this->deleteWebhookInLongPolling();
        $this->connectDatabase();
    }

    /**
     * connect to database
     * if is set in setting
     */
    public function connectDatabase()
    {
        if(isset($this->setting['mysql']))
        {
            $this->host = $this->setting['mysql']['host'];
            $this->username = $this->setting['mysql']['username'];
            $this->password = $this->setting['mysql']['password'];
            $this->database = $this->setting['mysql']['database'];
            DB::connect($this->host, $this->username, $this->password, $this->password);
        }
    }

    /**
     * delete webhook if method is long polling
     */
    public function deleteWebhookInLongPolling()
    {
        if($this->method == 'long_polling')
            $this->deleteWebhook();
    }

    /**
     * return caller function
     * @return mixed
     */
    public function callerFunction()
    {
        return debug_backtrace()[2]['function'];
    }

    /**
     * @param $rows
     * @param null $optional
     * @return false|string
     */
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

    /**
     * @param $rows
     * @return false|string
     */
    public function inlineKeyboard($rows)
    {
        $keyboard = [
            'inline_keyboard' => $rows
        ];
        $keyboard = json_encode($keyboard);
        
        return $keyboard;
    }

    /**
     * @param $commands
     */
    public function setCommands($commands)
    {
        $this->commands = $commands;
    }

    /**
     * @param $bot
     * @return bool
     */
    public function messageIsCommand($bot)
    {
        if(!isset($this->commands))
            return false;

        foreach ($this->commands as $command => $method)
        {
            $items = explode(',', $command);

            foreach ($items as $item)
            {
                if ($this->getText() == $item)
                {
                    $bot->{$method}();
                    break;
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getMessages()
    {
        if($this->method == 'webhook')
            $messages = array(json_decode(file_get_contents( 'php://input' ), true));
        else if($this->method == 'long_polling')
            $messages = $this->getUpdates();

        return $messages;
    }

    /**
     * save state of user
     */
    public function saveState($state)
    {
        return DB::insert("INSERT INTO states (chat_id, state) VALUES (? , ?)", [ $this->getChatId(), $state ] );
    }

    /**
     * get state of user when
     * is in conversation
     * @return bool
     */
    public function getState()
    {
        $state = DB::select("SELECT state FROM states WHERE chat_id = ? LIMIT 1", [ $this->getChatId() ] );

        if($state->num_rows == 0)
            return false;

        while ($row = $state->fetch_object())
            return $row->state;
    }

    /**
     * update state of user
     * when answers to conversation
     * @param $state
     * @return mixed
     */
    public function updateState($state)
    {
        return DB::update("UPDATE states SET state = ? WHERE chat_id = ? LIMIT 1", [ $state, $this->getChatId() ] );
    }

    /**
     * delete state of user when conversation is finished
     * @return mixed
     */
    public function deleteState()
    {
        return DB::delete("DELETE FROM states WHERE chat_id = ? LIMIT 1", [ $this->getChatId() ] );
    }

    /**
     * update state of user in conversation
     * if saved before else save it
     * @param $state
     * @return mixed
     */
    public function updateOrSaveState($state)
    {
        $checkState = DB::select("SELECT state FROM states WHERE chat_id = ? LIMIT 1", [ $this->getChatId() ] );
        
        if($checkState->num_rows > 0)
            $result = DB::update("UPDATE states SET state = ? WHERE chat_id = ? LIMIT 1", [ $state, $this->getChatId() ] );
        else
            $result = DB::insert("INSERT INTO states (chat_id, state) VALUES (? , ?)", [ $this->getChatId(), $state ] );

        return $result;
    }

    /**
     * save the data that user has sent
     * in any step of conversation.
     * data will be appended in
     * user`s conversation file.
     * to overwrite data just pass
     * true for $overwrite variable
     */
    public function saveConversationData($input, $overwrite = null)
    {
        if(isset($overwrite) && $overwrite === true)
            $mod = "w";
        else if(isset($overwrite) && $overwrite !== true)
            exit("$overwrite is invalid, pass true if you want to overwrite data in ".__FUNCTION__." method");
        else if(!$overwrite)
            $mod = "a";

        if(!file_exists($this->conversationDir))
            mkdir($this->conversationDir);
        
        $userConversationFile = fopen("$this->conversationDir/".$this->getChatId().".txt", $mod);
        fwrite($userConversationFile, $input);
        fclose($userConversationFile);
    }

    /**
     * get conversation data of user
     * @return bool|false|string
     */
    public function getConversationData()
    {
        $userConversationFile = $this->conversationDir."/".$this->getChatId().".txt";
        
        if(!file_exists($userConversationFile))
            return false;

        return file_get_contents($userConversationFile);
    }

    /**
     * delete conversation data of user
     */
    public function deleteConversationData()
    {
        $userConversationFile = $this->conversationDir."/".$this->getChatId().".txt";
        
        if(file_exists($userConversationFile))
            unlink($userConversationFile);
    }

    /**
     * @param null $params
     * @return bool|string
     */
    public function apiGetContent($params = null)
    {
        $url = "https://api.telegram.org/bot{$this->token}/".$this->callerFunction();
        $data = $this->curlExec($url, $params);

        return $data;
    }

    /**
     * @param $url
     * @param null $params
     * @return bool|string
     */
    public function curlExec($url, $params = null)
    {
        $ch = curl_init();
        $data = http_build_query($params);
        ($params) ? $request = $url."?".$data : $request = $url;
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $request);
        $response = curl_exec($ch);

        return $response;
    }

    /**
     * save last update id of message
     */
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

    /**
     * @return bool
     */
    public function MessageSent()
    {
        return (isset($this->message->message));
    }

    /**
     * @return bool
     */
    public function messageEdited()
    {
        return (isset($this->message->edited_message));
    }

    /**
     * @return bool
     */
    public function keyboardClicked()
    {
        return (isset($this->message->callback_query));
    }

    /**
     * @return mixed
     */
    public function getKeyboardData()
    {
        return $this->message->callback_query->data;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        if(isset($this->message->message))
            $message = $this->message->message;
        else if(isset($this->message->edited_message))
            $message = $this->message->edited_message->message;
        else if(isset($this->message->callback_query))
            $message = $this->message->callback_query->message;

        return $message;
    }

    /**
     * @return mixed
     */
    public function getMessageId()
    {
        return $this->getMessage()->message_id;
    }

    /**
     * @return mixed
     */
    public function getUserId()
    {
        return $this->getMessage()->from->id;
    }

    /**
     * @return mixed
     */
    public function getFrom()
    {
        return $this->getMessage()->from;
    }

    /**
     * @return mixed
     */
    public function getFirstName()
    {
        return $this->getChatInfo()->first_name;
    }

    /**
     * @return mixed
     */
    public function getLastName()
    {
        return $this->getChatInfo()->first_name;
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        return $this->getChatInfo()->first_name." ".$this->getChatInfo()->last_name;
    }

    /**
     * @return mixed
     */
    public function getUsername()
    {
        return $this->getChatInfo()->username;
    }

    /**
     * @return mixed
     */
    public function canJoinGroups()
    {
        return $this->getMe()->can_join_groups;
    }

    /**
     * @return mixed
     */
    public function canReadAllGroupMessages()
    {
        return $this->getMe()->can_read_all_group_messages;
    }

    /**
     * @return mixed
     */
    public function supportsInlineQueries()
    {
        return $this->getMe()->supports_inline_queries;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->getMessage()->date;
    }

    /**
     * @return mixed
     */
    public function getChatInfo()
    {
        return $this->getMessage()->chat;
    }

    /**
     * @return mixed
     */
    public function getChatId()
    {
        return $this->getChatInfo()->id;
    }

    /**
     * @return mixed
     */
    public function getChatType()
    {
        return $this->getChatInfo()->type;
    }

    /**
     * @return mixed
     */
    public function getChatTitle()
    {
        return $this->getChatInfo()->title;
    }

    /**
     * @return mixed
     */
    public function getChatUsername()
    {
        return $this->getChatInfo()->username;
    }

    /**
     * @return mixed
     */
    public function getChatFirstName()
    {
        return $this->getChatInfo()->first_name;
    }

    /**
     * @return mixed
     */
    public function getChatLastName()
    {
        return $this->getChatInfo()->last_name;
    }

    /**
     * @return string
     */
    public function getChatFullName()
    {
        return $this->getChatInfo()->first_name." ".$this->getChatInfo()->last_name;
    }

    /**
     * @return mixed
     */
    public function getChatData()
    {
        $result = $this->getChat([
            'chat_id' => $this->getChatId()
        ]);

        return $result;
    }

    /**
     * @return mixed
     */
    public function getChatPhoto()
    {
        return $this->getChatData()->photo;
    }

    /**
     * @return mixed
     */
    public function getChatDescription()
    {
        return $this->getChatData()->description;
    }

    /**
     * @return mixed
     */
    public function getChatInviteLink()
    {
        return $this->getChatData()->invite_link;
    }

    /**
     * @return mixed
     */
    public function getChatPinnedMessage()
    {
        return $this->getChatData()->pinned_message;
    }

    /**
     * @return mixed
     */
    public function getChatPermissions()
    {
        return $this->getChatData()->permissions;
    }

    /**
     * @return mixed
     */
    public function getChatSlowModeDelay()
    {
        return $this->getChatData()->slow_mode_delay;
    }

    /**
     * @return mixed
     */
    public function stickerSetName()
    {
        return $this->getChatData()->sticker_set_name;
    }

    /**
     * @return mixed
     */
    public function canSetStickerSet()
    {
        return $this->getChatData()->can_set_sticker_set;
    }

    /**
     * @return mixed
     */
    public function getForwardFrom()
    {
        return $this->getMessage()->forward_from;
    }

    /**
     * @return mixed
     */
    public function getForwardFromId()
    {
        return $this->getForwardFrom()->id;
    }

    /**
     * @return mixed
     */
    public function getForwardFromBot()
    {
        return $this->getForwardFrom()->is_bot;
    }

    /**
     * @return mixed
     */
    public function getForwardFromFirstName()
    {
        return $this->getForwardFrom()->first_name;
    }

    /**
     * @return mixed
     */
    public function getForwardFromLastName()
    {
        return $this->getForwardFrom()->last_name;
    }

    /**
     * @return string
     */
    public function getForwardFromFullName()
    {
        return $this->getForwardFrom()->first_name." ".$this->getForwardFrom()->last_name;
    }

    /**
     * @return mixed
     */
    public function getForwardFromUsername()
    {
        return $this->getForwardFrom()->username;
    }

    /**
     * @return mixed
     */
    public function getForwardFromChat()
    {
        return $this->getMessage()->forward_from_chat;
    }

    /**
     * @return mixed
     */
    public function getForwardFromMessageId()
    {
        return $this->getMessage()->forward_from_message_id;
    }

    /**
     * @return mixed
     */
    public function getForwardSignature()
    {
        return $this->getMessage()->forward_signature;
    }

    /**
     * @return mixed
     */
    public function getForwardSenderName()
    {
        return $this->getMessage()->forward_sender_name;
    }

    /**
     * @return mixed
     */
    public function getForwardDate()
    {
        return $this->getMessage()->forward_date;
    }

    /**
     * @return mixed
     */
    public function replyToMessage()
    {
        return $this->getMessage()->reply_to_message;
    }

    /**
     * @return mixed
     */
    public function viaBot()
    {
        return $this->getMessage()->via_bot;
    }

    /**
     * @return mixed
     */
    public function getEditDate()
    {
        return $this->getMessage()->edit_date;
    }

    /**
     * @return mixed
     */
    public function getMediaGroupId()
    {
        return $this->getMessage()->media_group_id;
    }

    /**
     * @return mixed
     */
    public function getAuthorSignature()
    {
        return $this->getMessage()->author_signature;
    }

    /**
     * @return mixed
     */
    public function getText()
    {
        return $this->getMessage()->text;
    }

    /**
     * @return mixed
     */
    public function getEntities()
    {
        return $this->getMessage()->entities;
    }

    /**
     * @return mixed
     */
    public function getAnimation()
    {
        return $this->getMessage()->animation;
    }

    /**
     * @return mixed
     */
    public function getAnimationFileId()
    {
        return $this->getAnimation()->file_id;
    }

    /**
     * @return mixed
     */
    public function getAnimationFileUniqueId()
    {
        return $this->getAnimation()->file_unique_id;
    }

    /**
     * @return mixed
     */
    public function getAnimationWidth()
    {
        return $this->getAnimation()->width;
    }

    /**
     * @return mixed
     */
    public function getAnimationHeight()
    {
        return $this->getAnimation()->height;
    }

    /**
     * @return mixed
     */
    public function getAnimationDuration()
    {
        return $this->getAnimation()->duration;
    }

    /**
     * @return mixed
     */
    public function getAnimationThumb()
    {
        return $this->getAnimation()->thumb;
    }

    /**
     * @return mixed
     */
    public function getAnimationFileName()
    {
        return $this->getAnimation()->file_name;
    }

    /**
     * @return mixed
     */
    public function getAnimationMimeType()
    {
        return $this->getAnimation()->mime_type;
    }

    /**
     * @return mixed
     */
    public function getAnimationFileSize()
    {
        return $this->getAnimation()->file_size;
    }

    /**
     * @return mixed
     */
    public function getAudio()
    {
        return $this->getMessage()->audio;
    }

    /**
     * @return mixed
     */
    public function getAudioFileId()
    {
        return $this->getAudio()->file_id;
    }

    /**
     * @return mixed
     */
    public function getAudioFileUniqueId()
    {
        return $this->getAudio()->file_unique_id;
    }

    /**
     * @return mixed
     */
    public function getAudioDuration()
    {
        return $this->getAudio()->duration;
    }

    /**
     * @return mixed
     */
    public function getAudioPerformer()
    {
        return $this->getAudio()->performer;
    }

    /**
     * @return mixed
     */
    public function getAudioTitle()
    {
        return $this->getAudio()->title;
    }

    /**
     * @return mixed
     */
    public function getAudioMimeType()
    {
        return $this->getAudio()->mime_type;
    }

    /**
     * @return mixed
     */
    public function getAudioFileSize()
    {
        return $this->getAudio()->file_size;
    }

    /**
     * @return mixed
     */
    public function getAudioThumb()
    {
        return $this->getAudio()->thumb;
    }

    /**
     * @return mixed
     */
    public function getDocument()
    {
        return $this->getMessage()->document;
    }

    /**
     * @return mixed
     */
    public function getDocumentFileId()
    {
        return $this->getDocument()->file_id;
    }

    /**
     * @return mixed
     */
    public function getDocumentFileUniqueId()
    {
        return $this->getDocument()->file_unique_id;
    }

    /**
     * @return mixed
     */
    public function getDocumentThumb()
    {
        return $this->getDocument()->thumb;
    }

    /**
     * @return mixed
     */
    public function getDocumentFileName()
    {
        return $this->getDocument()->file_name;
    }

    /**
     * @return mixed
     */
    public function getDocumentMimeType()
    {
        return $this->getDocument()->mime_type;
    }

    /**
     * @return mixed
     */
    public function getDocumentFileSize()
    {
        return $this->getDocument()->file_size;
    }

    /**
     * @return mixed
     */
    public function getPhoto()
    {
        return $this->getMessage()->photo;
    }

    /**
     * @return mixed
     */
    public function getSticker()
    {
        return $this->getMessage()->sticker;
    }

    /**
     * @return mixed
     */
    public function getStickerFileId()
    {
        return $this->getSticker()->file_id;
    }

    /**
     * @return mixed
     */
    public function getStickerFileUniqueId()
    {
        return $this->getSticker()->file_unique_id;
    }

    /**
     * @return mixed
     */
    public function getStickerWidth()
    {
        return $this->getSticker()->width;
    }

    /**
     * @return mixed
     */
    public function getStickerHeight()
    {
        return $this->getSticker()->height;
    }

    /**
     * @return mixed
     */
    public function getStickerIsAnimated()
    {
        return $this->getSticker()->is_animated;
    }

    /**
     * @return mixed
     */
    public function getStickerThumb()
    {
        return $this->getSticker()->thumb;
    }

    /**
     * @return mixed
     */
    public function getStickerEmoji()
    {
        return $this->getSticker()->emoji;
    }

    /**
     * @return mixed
     */
    public function getStickerSetName()
    {
        return $this->getSticker()->set_name;
    }

    /**
     * @return mixed
     */
    public function getStickerMaskPosition()
    {
        return $this->getSticker()->mask_position;
    }

    /**
     * @return mixed
     */
    public function getStickerFileSize()
    {
        return $this->getSticker()->file_size;
    }

    /**
     * @return mixed
     */
    public function getVideo()
    {
        return $this->getMessage()->video;
    }

    /**
     * @return mixed
     */
    public function getVideoFileId()
    {
        return $this->getVideo()->file_id;
    }

    /**
     * @return mixed
     */
    public function getVideoFileUniqueId()
    {
        return $this->getVideo()->file_unique_id;
    }

    /**
     * @return mixed
     */
    public function getVideoWidth()
    {
        return $this->getVideo()->width;
    }

    /**
     * @return mixed
     */
    public function getVideoHeight()
    {
        return $this->getVideo()->height;
    }

    /**
     * @return mixed
     */
    public function getVideoDuration()
    {
        return $this->getVideo()->duration;
    }

    /**
     * @return mixed
     */
    public function getVideoThumb()
    {
        return $this->getVideo()->thumb;
    }

    /**
     * @return mixed
     */
    public function getVideoMimeType()
    {
        return $this->getVideo()->mime_type;
    }

    /**
     * @return mixed
     */
    public function getVideoFileSize()
    {
        return $this->getVideo()->file_size;
    }

    /**
     * @return mixed
     */
    public function getVideoNote()
    {
        return $this->getMessage()->video_note;
    }

    /**
     * @return mixed
     */
    public function getVideoNoteFileId()
    {
        return $this->getVideoNote()->file_id;
    }

    /**
     * @return mixed
     */
    public function getVideoNoteFileUniqueId()
    {
        return $this->getVideoNote()->file_unique_id;
    }

    /**
     * @return mixed
     */
    public function getVideoNoteLength()
    {
        return $this->getVideoNote()->length;
    }

    /**
     * @return mixed
     */
    public function getVideoNoteDuration()
    {
        return $this->getVideoNote()->duration;
    }

    /**
     * @return mixed
     */
    public function getVideoNoteThumb()
    {
        return $this->getVideoNote()->thumb;
    }

    /**
     * @return mixed
     */
    public function getVideoNoteFileSize()
    {
        return $this->getVideoNote()->file_size;
    }

    /**
     * @return mixed
     */
    public function getVoice()
    {
        return $this->getMessage()->voice;
    }

    /**
     * @return mixed
     */
    public function getVoiceFileId()
    {
        return $this->getVoice()->file_id;
    }

    /**
     * @return mixed
     */
    public function getVoiceFileUniqueId()
    {
        return $this->getVoice()->file_unique_id;
    }

    /**
     * @return mixed
     */
    public function getVoiceDuration()
    {
        return $this->getVoice()->duration;
    }

    /**
     * @return mixed
     */
    public function getVoiceMimeType()
    {
        return $this->getVoice()->mime_type;
    }

    /**
     * @return mixed
     */
    public function getVoiceFileSize()
    {
        return $this->getVoice()->file_size;
    }

    /**
     * @return mixed
     */
    public function getCaption()
    {
        return $this->getMessage()->caption;
    }

    /**
     * @return mixed
     */
    public function getCaptionEntities()
    {
        return $this->getMessage()->caption_entities;
    }

    /**
     * @return mixed
     */
    public function getContact()
    {
        return $this->getMessage()->contact;
    }

    /**
     * @return mixed
     */
    public function getContactPhoneNumber()
    {
        return $this->getContact()->phone_number;
    }

    /**
     * @return mixed
     */
    public function getContactFirstName()
    {
        return $this->getContact()->first_name;
    }

    /**
     * @return mixed
     */
    public function getContactLastName()
    {
        return $this->getContact()->last_name;
    }

    /**
     * @return mixed
     */
    public function getContactUserId()
    {
        return $this->getContact()->user_id;
    }

    /**
     * @return mixed
     */
    public function getContactVcard()
    {
        return $this->getContact()->vcard;
    }

    /**
     * @return mixed
     */
    public function getDice()
    {
        return $this->getMessage()->dice;
    }

    /**
     * @return mixed
     */
    public function getDiceEmoji()
    {
        return $this->getDice()->emoji;
    }

    /**
     * @return mixed
     */
    public function getDiceValue()
    {
        return $this->getDice()->value;
    }

    /**
     * @return mixed
     */
    public function getGame()
    {
        return $this->getMessage()->game;
    }

    /**
     * @return mixed
     */
    public function getGameTitle()
    {
        return $this->getGame()->title;
    }

    /**
     * @return mixed
     */
    public function getGameDescription()
    {
        return $this->getGame()->description;
    }

    /**
     * @return mixed
     */
    public function getGamePhoto()
    {
        return $this->getGame()->photo;
    }

    /**
     * @return mixed
     */
    public function getGameText()
    {
        return $this->getGame()->text;
    }

    /**
     * @return mixed
     */
    public function getGameTextEntities()
    {
        return $this->getGame()->text_entities;
    }

    /**
     * @return mixed
     */
    public function getGameAnimation()
    {
        return $this->getGame()->animation;
    }

    /**
     * @return mixed
     */
    public function getPoll()
    {
        return $this->getMessage()->poll;
    }

    /**
     * @return mixed
     */
    public function getPollId()
    {
        return $this->getPoll()->id;
    }

    /**
     * @return mixed
     */
    public function getPollQuestion()
    {
        return $this->getPoll()->question;
    }

    /**
     * @return mixed
     */
    public function getPollOptions()
    {
        return $this->getPoll()->options;
    }

    /**
     * @return mixed
     */
    public function getPollTotalVoterCount()
    {
        return $this->getPoll()->total_voter_count;
    }

    /**
     * @return mixed
     */
    public function getPollIsClosed()
    {
        return $this->getPoll()->is_closed;
    }

    /**
     * @return mixed
     */
    public function getPollIsAnonymous()
    {
        return $this->getPoll()->is_anonymous;
    }

    /**
     * @return mixed
     */
    public function getPollType()
    {
        return $this->getPoll()->type;
    }

    /**
     * @return mixed
     */
    public function getPollAllowsMultipleAnswers()
    {
        return $this->getPoll()->allows_multiple_answers;
    }

    /**
     * @return mixed
     */
    public function getPollCorrectOptionId()
    {
        return $this->getPoll()->correct_option_id;
    }

    /**
     * @return mixed
     */
    public function getPollExplanation()
    {
        return $this->getPoll()->explanation;
    }

    /**
     * @return mixed
     */
    public function getPollExplanationEntities()
    {
        return $this->getPoll()->explanation_entities;
    }

    /**
     * @return mixed
     */
    public function getPollOpenPeriod()
    {
        return $this->getPoll()->open_period;
    }

    /**
     * @return mixed
     */
    public function getPollCloseDate()
    {
        return $this->getPoll()->close_date;
    }

    /**
     * @return mixed
     */
    public function getVenue()
    {
        return $this->getMessage()->venue;
    }

    /**
     * @return mixed
     */
    public function getVenueLocation()
    {
        return $this->getVenue()->location;
    }

    /**
     * @return mixed
     */
    public function getVenueTitle()
    {
        return $this->getVenue()->title;
    }

    /**
     * @return mixed
     */
    public function getVenueAddress()
    {
        return $this->getVenue()->address;
    }

    /**
     * @return mixed
     */
    public function getVenueFoursquareId()
    {
        return $this->getVenue()->foursquare_id;
    }

    /**
     * @return mixed
     */
    public function getVenueFoursquareType()
    {
        return $this->getVenue()->foursquare_type;
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->getMessage()->location;
    }

    /**
     * @return mixed
     */
    public function getLocationLongitude()
    {
        return $this->getLocation()->longitude;
    }

    /**
     * @return mixed
     */
    public function getLocationLatitude()
    {
        return $this->getLocation()->latitude;
    }

    /**
     * @return mixed
     */
    public function getNewChatMembers()
    {
        return $this->getMessage()->new_chat_members;
    }

    /**
     * @return mixed
     */
    public function getLeftChatMember()
    {
        return $this->getMessage()->left_chat_member;
    }

    /**
     * @return mixed
     */
    public function getNewChatTitle()
    {
        return $this->getMessage()->new_chat_title;
    }

    /**
     * @return mixed
     */
    public function getNewChatPhoto()
    {
        return $this->getMessage()->new_chat_photo;
    }

    /**
     * @return mixed
     */
    public function getDeleteChatPhoto()
    {
        return $this->getMessage()->delete_chat_photo;
    }

    /**
     * @return mixed
     */
    public function getGroupChatCreated()
    {
        return $this->getMessage()->group_chat_created;
    }

    /**
     * @return mixed
     */
    public function getSupergroupChatCreated()
    {
        return $this->getMessage()->supergroup_chat_created;
    }

    /**
     * @return mixed
     */
    public function getChannelChatCreated()
    {
        return $this->getMessage()->channel_chat_created;
    }

    /**
     * @return mixed
     */
    public function getMigrateToChatId()
    {
        return $this->getMessage()->migrate_to_chat_id;
    }

    /**
     * @return mixed
     */
    public function getMigrateFromChatId()
    {
        return $this->getMessage()->migrate_from_chat_id;
    }

    /**
     * @return mixed
     */
    public function getPinnedMessage()
    {
        return $this->getMessage()->pinned_message;
    }

    /**
     * @return mixed
     */
    public function getInvoice()
    {
        return $this->getMessage()->invoice;
    }

    /**
     * @return mixed
     */
    public function getInvoiceTitle()
    {
        return $this->getInvoice()->title;
    }

    /**
     * @return mixed
     */
    public function getInvoiceDescription()
    {
        return $this->getInvoice()->description;
    }

    /**
     * @return mixed
     */
    public function getInvoiceStartParameter()
    {
        return $this->getInvoice()->start_parameter;
    }

    /**
     * @return mixed
     */
    public function getInvoiceCurrency()
    {
        return $this->getInvoice()->currency;
    }

    /**
     * @return mixed
     */
    public function getInvoiceTotalAmount()
    {
        return $this->getInvoice()->total_amount;
    }

    /**
     * @return mixed
     */
    public function getSuccessfulPayment()
    {
        return $this->getMessage()->successful_payment;
    }

    /**
     * @return mixed
     */
    public function getSuccessfulPaymentCurrency()
    {
        return $this->getSuccessfulPayment()->currency;
    }

    /**
     * @return mixed
     */
    public function getSuccessfulPaymentTotalAmount()
    {
        return $this->getSuccessfulPayment()->total_amount;
    }

    /**
     * @return mixed
     */
    public function getSuccessfulPaymentInvoicePayload()
    {
        return $this->getSuccessfulPayment()->invoice_payload;
    }

    /**
     * @return mixed
     */
    public function getSuccessfulPaymentShippingOptionId()
    {
        return $this->getSuccessfulPayment()->shipping_option_id;
    }

    /**
     * @return mixed
     */
    public function getSuccessfulPaymentOrderInfo()
    {
        return $this->getSuccessfulPayment()->order_info;
    }

    /**
     * @return mixed
     */
    public function getSuccessfulPaymentTelegramPaymentChargeId()
    {
        return $this->getSuccessfulPayment()->telegram_payment_charge_id;
    }

    /**
     * @return mixed
     */
    public function getSuccessfulPaymentProviderPaymentChargeId()
    {
        return $this->getSuccessfulPayment()->provider_payment_charge_id;
    }

    /**
     * @return mixed
     */
    public function getConnectedWebsite()
    {
        return $this->getMessage()->connected_website;
    }

    /**
     * @return mixed
     */
    public function getPassportData()
    {
        return $this->getMessage()->passport_data;
    }

    /**
     * @return mixed
     */
    public function getPassportDataData()
    {
        return $this->getPassportData()->data;
    }

    /**
     * @return mixed
     */
    public function getPassportDataCredentials()
    {
        return $this->getPassportData()->credentials;
    }

    /**
     * @return mixed
     */
    public function getReplyMarkup()
    {
        return $this->getMessage()->reply_markup;
    }


}