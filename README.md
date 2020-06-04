# Telegram
Library for telegram bot api

This is a library for telegram bot which is included all telegram api`s, and some custom functions to help you.

# Long Polling and Webhook Methods
You can use this library in both long polling and webhook method

long polling example:

* $this->method = 'long_polling'

webhook example:

* $this->method = 'webhook'

# Start Conversation With User
To start a conversation with any user you need to create "states" table

* Table Name: states

Columns:

* id: Primary Key, Auto Increament

* chat_id: INT, UNIQUE

* state: VARCHAR

And there are some helper functions you can use in your code to have a conversation with your user:

* saveState();

* getState();

* updateState();

* deleteState();

* updateOrSaveState();

# Contact
farshad.tofighi74 [at] gmail [dot] com
