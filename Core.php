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

    /*
     * return caller function
     */
    public function callerFunction()
    {
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

    /**
     * create table in your database with info in below
     * table name: states
     * columns:
     * id: Primary Key, Auto Increament
     * chat_id: INT, UNIQUE
     * state: VARCHAR
     * save state of user to start conversation
     */
    public function saveState($state)
    {
        $result = DB::insert("INSERT INTO states (chat_id, state) VALUES (? , ?)", [ $this->chatId(), $state ] );
        
        return $result;
    }

    /**
     * get state of user when is in conversation
     * @return bool
     */
    public function getState()
    {
        $state = DB::select("SELECT state FROM states WHERE chat_id = ?", [ $this->chatId() ] );
        
        if($state->num_rows > 0)
            while ($row = $state->fetch_object())
                return $row->state;
        else
            return false;
    }

    /**
     * update state of user when answers to conversation
     * @param $state
     * @return mixed
     */
    public function updateState($state)
    {
        $result = DB::update("UPDATE states SET state = ? WHERE chat_id = ? LIMIT 1", [ $state, $this->chatId() ] );
        
        return $result;
    }

    /**
     * delete state of user when conversation is finished
     * @return mixed
     */
    public function deleteState()
    {
        $result = DB::delete("DELETE FROM states WHERE chat_id = ? LIMIT 1", [ $this->chatId() ] );
        
        return $result;
    }

    /**
     * update state of user in conversation if saved before else save it
     * @param $state
     * @return mixed
     */
    public function updateOrSaveState($state)
    {
        $check_state = DB::select("SELECT state FROM states WHERE chat_id = ? LIMIT 1", [ $this->chatId() ] );
        
        if($check_state->num_rows > 0)
            $result = DB::update("UPDATE states SET state = ? WHERE chat_id = ? LIMIT 1", [ $state, $this->chatId() ] );
        else
            $result = DB::insert("INSERT INTO states (chat_id, state) VALUES (? , ?)", [ $this->chatId(), $state ] );

        return $result;
    }

    /**
     * save the data that user has sent in any step of conversation
     * data will be appended in user`s conversation file
     * to overwrite data just pass true for $overwrite variable
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

    public function MessageSent()
    {
        return (isset($this->message->message));
    }

    public function messageEdited()
    {
        return (isset($this->message->edited_message));
    }

    public function keyboardClicked()
    {
        return (isset($this->message->callback_query));
    }

    public function getKeyboardData()
    {
        return $this->message->callback_query->data;
    }

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

    public function getMessageId()
    {
        return $this->getMessage()->message_id;
    }

    public function getUserId()
    {
        return $this->getMessage()->from->id;
    }

    public function getFrom()
    {
        return $this->getMessage()->from;
    }

    public function getFirstName()
    {
        return $this->getChatInfo()->first_name;
    }

    public function getLastName()
    {
        return $this->getChatInfo()->first_name;
    }

    public function getFullName()
    {
        return $this->getChatInfo()->first_name." ".$this->getChatInfo()->last_name;
    }

    public function getUsername()
    {
        return $this->getChatInfo()->username;
    }

    public function canJoinGroups()
    {
        return $this->getMe()->can_join_groups;
    }

    public function canReadAllGroupMessages()
    {
        return $this->getMe()->can_read_all_group_messages;
    }

    public function supportsInlineQueries()
    {
        return $this->getMe()->supports_inline_queries;
    }

    public function getDate()
    {
        return $this->getMessage()->date;
    }

    public function getChatInfo()
    {
        return $this->getMessage()->chat;
    }

    public function getChatId()
    {
        return $this->getChatInfo()->id;
    }

    public function getChatType()
    {
        return $this->getChatInfo()->type;
    }

    public function getChatTitle()
    {
        return $this->getChatInfo()->title;
    }

    public function getChatUsername()
    {
        return $this->getChatInfo()->username;
    }

    public function getChatFirstName()
    {
        return $this->getChatInfo()->first_name;
    }

    public function getChatLastName()
    {
        return $this->getChatInfo()->last_name;
    }

    public function getChatFullName()
    {
        return $this->getChatInfo()->first_name." ".$this->getChatInfo()->last_name;
    }

    public function getChatData()
    {
        $result = $this->getChat([
            'chat_id' => $this->getChatId()
        ]);

        return $result;
    }

    public function getChatPhoto()
    {
        return $this->getChatData()->photo;
    }

    public function getChatDescription()
    {
        return $this->getChatData()->description;
    }

    public function getChatInviteLink()
    {
        return $this->getChatData()->invite_link;
    }

    public function getChatPinnedMessage()
    {
        return $this->getChatData()->pinned_message;
    }

    public function getChatPermissions()
    {
        return $this->getChatData()->permissions;
    }

    public function getChatSlowModeDelay()
    {
        return $this->getChatData()->slow_mode_delay;
    }

    public function stickerSetName()
    {
        return $this->getChatData()->sticker_set_name;
    }

    public function canSetStickerSet()
    {
        return $this->getChatData()->can_set_sticker_set;
    }

    public function getForwardFrom()
    {
        return $this->getMessage()->forward_from;
    }

    public function getForwardFromId()
    {
        return $this->getForwardFrom()->id;
    }

    public function getForwardFromBot()
    {
        return $this->getForwardFrom()->is_bot;
    }

    public function getForwardFromFirstName()
    {
        return $this->getForwardFrom()->first_name;
    }

    public function getForwardFromLastName()
    {
        return $this->getForwardFrom()->last_name;
    }

    public function getForwardFromFullName()
    {
        return $this->getForwardFrom()->first_name." ".$this->getForwardFrom()->last_name;
    }

    public function getForwardFromUsername()
    {
        return $this->getForwardFrom()->username;
    }

    public function getForwardFromChat()
    {
        return $this->getMessage()->forward_from_chat;
    }

    public function getForwardFromMessageId()
    {
        return $this->getMessage()->forward_from_message_id;
    }

    public function getForwardSignature()
    {
        return $this->getMessage()->forward_signature;
    }

    public function getForwardSenderName()
    {
        return $this->getMessage()->forward_sender_name;
    }

    public function getForwardDate()
    {
        return $this->getMessage()->forward_date;
    }

    public function replyToMessage()
    {
        return $this->getMessage()->reply_to_message;
    }

    public function viaBot()
    {
        return $this->getMessage()->via_bot;
    }

    public function getEditDate()
    {
        return $this->getMessage()->edit_date;
    }

    public function getMediaGroupId()
    {
        return $this->getMessage()->media_group_id;
    }

    public function getAuthorSignature()
    {
        return $this->getMessage()->author_signature;
    }

    public function getText()
    {
        return $this->getMessage()->text;
    }

    public function getEntities()
    {
        return $this->getMessage()->entities;
    }

    public function getAnimation()
    {
        return $this->getMessage()->animation;
    }

    public function getAnimationFileId()
    {
        return $this->getAnimation()->file_id;
    }

    public function getAnimationFileUniqueId()
    {
        return $this->getAnimation()->file_unique_id;
    }

    public function getAnimationWidth()
    {
        return $this->getAnimation()->width;
    }

    public function getAnimationHeight()
    {
        return $this->getAnimation()->height;
    }

    public function getAnimationDuration()
    {
        return $this->getAnimation()->duration;
    }

    public function getAnimationThumb()
    {
        return $this->getAnimation()->thumb;
    }

    public function getAnimationFileName()
    {
        return $this->getAnimation()->file_name;
    }

    public function getAnimationMimeType()
    {
        return $this->getAnimation()->mime_type;
    }

    public function getAnimationFileSize()
    {
        return $this->getAnimation()->file_size;
    }

    public function getAudio()
    {
        return $this->getMessage()->audio;
    }

    public function getAudioFileId()
    {
        return $this->getAudio()->file_id;
    }

    public function getAudioFileUniqueId()
    {
        return $this->getAudio()->file_unique_id;
    }

    public function getAudioDuration()
    {
        return $this->getAudio()->duration;
    }

    public function getAudioPerformer()
    {
        return $this->getAudio()->performer;
    }

    public function getAudioTitle()
    {
        return $this->getAudio()->title;
    }

    public function getAudioMimeType()
    {
        return $this->getAudio()->mime_type;
    }

    public function getAudioFileSize()
    {
        return $this->getAudio()->file_size;
    }

    public function getAudioThumb()
    {
        return $this->getAudio()->thumb;
    }

    public function getDocument()
    {
        return $this->getMessage()->document;
    }

    public function getDocumentFileId()
    {
        return $this->getDocument()->file_id;
    }

    public function getDocumentFileUniqueId()
    {
        return $this->getDocument()->file_unique_id;
    }

    public function getDocumentThumb()
    {
        return $this->getDocument()->thumb;
    }

    public function getDocumentFileName()
    {
        return $this->getDocument()->file_name;
    }

    public function getDocumentMimeType()
    {
        return $this->getDocument()->mime_type;
    }

    public function getDocumentFileSize()
    {
        return $this->getDocument()->file_size;
    }

    public function getPhoto()
    {
        return $this->getMessage()->photo;
    }

    public function getSticker()
    {
        return $this->getMessage()->sticker;
    }

    public function getStickerFileId()
    {
        return $this->getSticker()->file_id;
    }

    public function getStickerFileUniqueId()
    {
        return $this->getSticker()->file_unique_id;
    }

    public function getStickerWidth()
    {
        return $this->getSticker()->width;
    }

    public function getStickerHeight()
    {
        return $this->getSticker()->height;
    }

    public function getStickerIsAnimated()
    {
        return $this->getSticker()->is_animated;
    }

    public function getStickerThumb()
    {
        return $this->getSticker()->thumb;
    }

    public function getStickerEmoji()
    {
        return $this->getSticker()->emoji;
    }

    public function getStickerSetName()
    {
        return $this->getSticker()->set_name;
    }

    public function getStickerMaskPosition()
    {
        return $this->getSticker()->mask_position;
    }

    public function getStickerFileSize()
    {
        return $this->getSticker()->file_size;
    }

    public function getVideo()
    {
        return $this->getMessage()->video;
    }

    public function getVideoFileId()
    {
        return $this->getVideo()->file_id;
    }

    public function getVideoFileUniqueId()
    {
        return $this->getVideo()->file_unique_id;
    }

    public function getVideoWidth()
    {
        return $this->getVideo()->width;
    }

    public function getVideoHeight()
    {
        return $this->getVideo()->height;
    }

    public function getVideoDuration()
    {
        return $this->getVideo()->duration;
    }

    public function getVideoThumb()
    {
        return $this->getVideo()->thumb;
    }

    public function getVideoMimeType()
    {
        return $this->getVideo()->mime_type;
    }

    public function getVideoFileSize()
    {
        return $this->getVideo()->file_size;
    }

    public function getVideoNote()
    {
        return $this->getMessage()->video_note;
    }

    public function getVideoNoteFileId()
    {
        return $this->getVideoNote()->file_id;
    }

    public function getVideoNoteFileUniqueId()
    {
        return $this->getVideoNote()->file_unique_id;
    }

    public function getVideoNoteLength()
    {
        return $this->getVideoNote()->length;
    }

    public function getVideoNoteDuration()
    {
        return $this->getVideoNote()->duration;
    }

    public function getVideoNoteThumb()
    {
        return $this->getVideoNote()->thumb;
    }

    public function getVideoNoteFileSize()
    {
        return $this->getVideoNote()->file_size;
    }

    public function getVoice()
    {
        return $this->getMessage()->voice;
    }

    public function getVoiceFileId()
    {
        return $this->getVoice()->file_id;
    }

    public function getVoiceFileUniqueId()
    {
        return $this->getVoice()->file_unique_id;
    }

    public function getVoiceDuration()
    {
        return $this->getVoice()->duration;
    }

    public function getVoiceMimeType()
    {
        return $this->getVoice()->mime_type;
    }

    public function getVoiceFileSize()
    {
        return $this->getVoice()->file_size;
    }

    public function getCaption()
    {
        return $this->getMessage()->caption;
    }

    public function getCaptionEntities()
    {
        return $this->getMessage()->caption_entities;
    }

    public function getContact()
    {
        return $this->getMessage()->contact;
    }

    public function getContactPhoneNumber()
    {
        return $this->getContact()->phone_number;
    }

    public function getContactFirstName()
    {
        return $this->getContact()->first_name;
    }

    public function getContactLastName()
    {
        return $this->getContact()->last_name;
    }

    public function getContactUserId()
    {
        return $this->getContact()->user_id;
    }

    public function getContactVcard()
    {
        return $this->getContact()->vcard;
    }

    public function getDice()
    {
        return $this->getMessage()->dice;
    }

    public function getDiceEmoji()
    {
        return $this->getDice()->emoji;
    }

    public function getDiceValue()
    {
        return $this->getDice()->value;
    }

    public function getGame()
    {
        return $this->getMessage()->game;
    }

    public function getGameTitle()
    {
        return $this->getGame()->title;
    }

    public function getGameDescription()
    {
        return $this->getGame()->description;
    }

    public function getGamePhoto()
    {
        return $this->getGame()->photo;
    }

    public function getGameText()
    {
        return $this->getGame()->text;
    }

    public function getGameTextEntities()
    {
        return $this->getGame()->text_entities;
    }

    public function getGameAnimation()
    {
        return $this->getGame()->animation;
    }

    public function getPoll()
    {
        return $this->getMessage()->poll;
    }

    public function getPollId()
    {
        return $this->getPoll()->id;
    }

    public function getPollQuestion()
    {
        return $this->getPoll()->question;
    }

    public function getPollOptions()
    {
        return $this->getPoll()->options;
    }

    public function getPollTotalVoterCount()
    {
        return $this->getPoll()->total_voter_count;
    }

    public function getPollIsClosed()
    {
        return $this->getPoll()->is_closed;
    }

    public function getPollIsAnonymous()
    {
        return $this->getPoll()->is_anonymous;
    }

    public function getPollType()
    {
        return $this->getPoll()->type;
    }

    public function getPollAllowsMultipleAnswers()
    {
        return $this->getPoll()->allows_multiple_answers;
    }

    public function getPollCorrectOptionId()
    {
        return $this->getPoll()->correct_option_id;
    }

    public function getPollExplanation()
    {
        return $this->getPoll()->explanation;
    }

    public function getPollExplanationEntities()
    {
        return $this->getPoll()->explanation_entities;
    }

    public function getPollOpenPeriod()
    {
        return $this->getPoll()->open_period;
    }

    public function getPollCloseDate()
    {
        return $this->getPoll()->close_date;
    }

    public function getVenue()
    {
        return $this->getMessage()->venue;
    }

    public function getVenueLocation()
    {
        return $this->getVenue()->location;
    }

    public function getVenueTitle()
    {
        return $this->getVenue()->title;
    }

    public function getVenueAddress()
    {
        return $this->getVenue()->address;
    }

    public function getVenueFoursquareId()
    {
        return $this->getVenue()->foursquare_id;
    }

    public function getVenueFoursquareType()
    {
        return $this->getVenue()->foursquare_type;
    }

    public function getLocation()
    {
        return $this->getMessage()->location;
    }

    public function getLocationLongitude()
    {
        return $this->getLocation()->longitude;
    }

    public function getLocationLatitude()
    {
        return $this->getLocation()->latitude;
    }

    public function getNewChatMembers()
    {
        return $this->getMessage()->new_chat_members;
    }

    public function getLeftChatMember()
    {
        return $this->getMessage()->left_chat_member;
    }

    public function getNewChatTitle()
    {
        return $this->getMessage()->new_chat_title;
    }

    public function getNewChatPhoto()
    {
        return $this->getMessage()->new_chat_photo;
    }

    public function getDeleteChatPhoto()
    {
        return $this->getMessage()->delete_chat_photo;
    }

    public function getGroupChatCreated()
    {
        return $this->getMessage()->group_chat_created;
    }

    public function getSupergroupChatCreated()
    {
        return $this->getMessage()->supergroup_chat_created;
    }

    public function getChannelChatCreated()
    {
        return $this->getMessage()->channel_chat_created;
    }

    public function getMigrateToChatId()
    {
        return $this->getMessage()->migrate_to_chat_id;
    }

    public function getMigrateFromChatId()
    {
        return $this->getMessage()->migrate_from_chat_id;
    }

    public function getPinnedMessage()
    {
        return $this->getMessage()->pinned_message;
    }

    public function getInvoice()
    {
        return $this->getMessage()->invoice;
    }

    public function getInvoiceTitle()
    {
        return $this->getInvoice()->title;
    }

    public function getInvoiceDescription()
    {
        return $this->getInvoice()->description;
    }

    public function getInvoiceStartParameter()
    {
        return $this->getInvoice()->start_parameter;
    }

    public function getInvoiceCurrency()
    {
        return $this->getInvoice()->currency;
    }

    public function getInvoiceTotalAmount()
    {
        return $this->getInvoice()->total_amount;
    }

    public function getSuccessfulPayment()
    {
        return $this->getMessage()->successful_payment;
    }

    public function getSuccessfulPaymentCurrency()
    {
        return $this->getSuccessfulPayment()->currency;
    }

    public function getSuccessfulPaymentTotalAmount()
    {
        return $this->getSuccessfulPayment()->total_amount;
    }

    public function getSuccessfulPaymentInvoicePayload()
    {
        return $this->getSuccessfulPayment()->invoice_payload;
    }

    public function getSuccessfulPaymentShippingOptionId()
    {
        return $this->getSuccessfulPayment()->shipping_option_id;
    }

    public function getSuccessfulPaymentOrderInfo()
    {
        return $this->getSuccessfulPayment()->order_info;
    }

    public function getSuccessfulPaymentTelegramPaymentChargeId()
    {
        return $this->getSuccessfulPayment()->telegram_payment_charge_id;
    }

    public function getSuccessfulPaymentProviderPaymentChargeId()
    {
        return $this->getSuccessfulPayment()->provider_payment_charge_id;
    }

    public function getConnectedWebsite()
    {
        return $this->getMessage()->connected_website;
    }

    public function getPassportData()
    {
        return $this->getMessage()->passport_data;
    }

    public function getPassportDataData()
    {
        return $this->getPassportData()->data;
    }

    public function getPassportDataCredentials()
    {
        return $this->getPassportData()->credentials;
    }

    public function getReplyMarkup()
    {
        return $this->getMessage()->reply_markup;
    }


}