SAPNS - Simple APNS Server for PHP
=====

Version 1.0

Apple Push Notification Service (APNS) has been with us since 2009. Yet I'm a bit disappointed with PHP tools available to support this awesome service. 
Some primitive tools "recommend" a cron-job approach to periodically poke a script that connects to APNS in order to send notifications. These tools are in breach of Apple's policy that clearly states "The Apple Push Notification Service provides a high-speed, high-capacity interface, so you should establish and maintain an open connection to handle all your notifications. Connections that are repeatedly opened and closed will affect the performance and stability of your connection to the Apple Push Notification Service and may be considered denial-of-service attacks.".

There are also more advanced PHP tools available that are typically run as background processes (daemons) re-using APNS connection socket once connection is established, however tools I've come across so far were either hugely overcomplicated or didn't provide enough flexibility to separate out a business logic behind sending push notifications.

SAPNS is a tool I wrote to address two issues I mentioned above. It favours simplicity over complexity yet it's powerful enough to let you implement your own business logic behind sending push notifications in an elegant and neat way.
