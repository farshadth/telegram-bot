<?php


trait Api
{
    

    public $token;
    
    public function setWebhook($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function deleteWebhook()
    {
        return $this->apiGetContent();
    }
    
    public function getWebhookInfo()
    {
        return $this->apiGetContent();
    }
    
    public function getUpdates()
    {
        $last_update_id = file_get_contents('update_id.txt');
        $last_update_id++;
        $params = [
            "offset" => $last_update_id,
        ];
        $message = json_decode($this->apiGetContent($params));

        return $message->result;
    }
    
    public function getMe()
    {
        return $this->apiGetContent();
    }
    
    public function sendMessage($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function forwardMessage($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function sendPhoto($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function sendAudio($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function sendDocument($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function sendVideo($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function sendAnimation($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function sendVoice($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function sendVideoNote($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function sendMediaGroup($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function sendLocation($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function editMessageLiveLocation($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function stopMessageLiveLocation($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function sendVenue($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function sendContact($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function sendPoll($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function sendDice($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function sendChatAction($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function getUserProfilePhotos($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function getFile($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function kickChatMember($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function unbanChatMember($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function restrictChatMember($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function promoteChatMember($params)
    {
        $params = $params;
        return $this->apiGetContent($params);
    }
    
    public function setChatAdministratorCustomTitle($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function setChatPermissions($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function exportChatInviteLink($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function setChatPhoto($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function deleteChatPhoto($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function setChatTitle($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function setChatDescription($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function pinChatMessage($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function unpinChatMessage($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function leaveChat($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function getChat($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function getChatAdministrators($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function getChatMembersCount($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function getChatMember($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function setChatStickerSet($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function deleteChatStickerSet($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function answerCallbackQuery($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function setMyCommands($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function getMyCommands()
    {
        return $this->apiGetContent();
    }
    
    public function editMessageText($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function editMessageCaption($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function editMessageMedia($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function editMessageReplyMarkup($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function stopPoll($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function deleteMessage($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function sendSticker($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function getStickerSet($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function uploadStickerFile($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function createNewStickerSet($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function addStickerToSet($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function setStickerPositionInSet($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function deleteStickerFromSet($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function setStickerSetThumb($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function answerInlineQuery($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function sendInvoice($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function answerShippingQuery($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function answerPreCheckoutQuery($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function setPassportDataErrors($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function sendGame($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function setGameScore($params)
    {
        return $this->apiGetContent($params);
    }
    
    public function getGameHighScores($params)
    {
        return $this->apiGetContent($params);
    }


}