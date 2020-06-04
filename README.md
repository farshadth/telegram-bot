# Telegram
Library for telegram bot

This is a library for telegram bot which is included all telegram api`s, and some custom functions to help you.

# Long Polling and Webhook Methods
the bot is flexible that you can use your bot in both long polling and webhook methods

use long polling method:

* $this->method = 'long_polling'

use webhook method:

* $this->method = 'webhook'

# Start Conversation With User
for start any conversation with user you need to create "states" table

* Table Name: states

Columns:

* id: Primary Key, Auto Increament

* chat_id: INT, UNIQUE

* state: VARCHAR

And there are some helper functions you can use in your code to have conversation with your user:

* saveState();

* getState();

* updateState();

* deleteState();

* updateOrSaveState();

# Contact
farshad.tofighi74@gmail.com
