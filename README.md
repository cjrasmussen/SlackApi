# SlackApi

Simple class for making requests to the Slack API or Slack Webhooks.  Not affiliated with Slack.


## Usage

```php
use cjrasmussen\SlackApi\SlackApi;

// INVITE A USER TO A CHANNEL
$slack = new SlackApi($token);

$args = [
    'channel' => 'C1234567',
    'member' => 'U9876543',
];
$slack->request('POST', 'conversations.invite', $args);

// SEND A MESSAGE VIA A SLACK WEBHOOK
$msg = [
    'text' => 'Message text',
];
$response = (new SlackApi($webhook_url))->sendMessage($msg);
```

## More Examples

More examples, as well as other things I've learned using the Slack API, are [available at my blog](https://blog.cjr.dev/?s=Slack).

## Installation

Simply add a dependency on cjrasmussen/slack-api to your composer.json file if you use [Composer](https://getcomposer.org/) to manage the dependencies of your project:

```sh
composer require cjrasmussen/slack-api
```

Although it's recommended to use Composer, you can actually include the file(s) any way you want.


## License

SlackApi is [MIT](http://opensource.org/licenses/MIT) licensed.