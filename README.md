# Telegram
Library for telegram bot based on oop

This is a library for telegram bot which is included all telegram api`s, and some methods to help you.

# Long Polling and Webhook Methods
You can use this library in both long polling and webhook method

long polling example:

* $this->method = 'long_polling'

webhook example:

* $this->method = 'webhook'

# Start Conversation With User

The state of user will be saved in database.

To save state of user, you need to create "states" table

* Table Name: states

Columns:

* id: Primary Key, Auto Increament
* chat_id: INT, UNIQUE
* state: VARCHAR

And there are some methods you can use to handle state of user in a conversation:

* saveState();
* getState();
* updateState();
* deleteState();
* updateOrSaveState();

Also the bot creates a file for each user which stores data that user sends in any step of conversations.

To handle the data that is sent by user in conversation, you can use these methods:

* saveConversationData()
* getConversationData()
* deleteConversationData()

# Contact
farshad.tofighi74 [at] gmail [dot] com
