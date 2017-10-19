# Mailchimp API Wordpress plugin #
 
> A wordpress plugin connected to Mailchimp API 

Display a form to add new subscriber 
Select the Mailchimp list you want to target 



## Requirements
Uses Mailchimp API version 3 
Require an api key that you may get at [Mailchimp's dev pages ](https://developer.mailchimp.com/)


## Installation 
You can install the plugin by uploading the folder `deli-mailchimp` to your `plugins` folder.
[here's how](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation)

## Configuration 
1 - Get an API key from Mailchimp
2 - Get the Id of the list you want to populate
3 - Go to Wordpress Dashboard > Settings 
There's two input fileds where to paster your API KEY and the list ID


## Usage 
Use the shortcode into your theme
This will display the form and the feedbacks.

### Example usage into post content
```html
[formulaire-subscribe][/formulaire-subscribe]
```
### Example usage into PHP code
```php
echo do_shortcode('[formulaire-subscribe]');
```

## Documentation 
Mailchimp's API documentation is available [here ](http://developer.mailchimp.com/documentation/mailchimp/)


## Help!
Drop me a line at [delicyus](http://delicyus.com)
I'll be glad to help

                _      _
               | |    | |()
             __| | ___| |_  ___ _   _ _   _ ___   ___ ___  _ __ ___
            / _` |/ _ \ | |/ __| | | | | | / __| / __/ _ \| '_ ` _ \
           | (_| |  __/ | | (__| |_| | |_| \__ \| (_| (_) | | | | | |
           |     |                              |   |     | | | | | |
            \____|\___|_|_|\___|\__, |\__,_|___(_)___\___/|_| |_| |_|
                                 __/ |
                                |___/
            web by delicyus.com