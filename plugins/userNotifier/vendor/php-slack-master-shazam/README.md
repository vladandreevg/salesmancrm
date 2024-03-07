# PHP Slack #
This library allows the developer to interact with Slack.

## Usage ##
```php
$token = "xoxp-2640873306-2668859284-2729881780-250123";
$client = new PhpSlack\Utils\RestApiClient($token);
$slack = new PhpSlack\Slack($client);

$slack->createChannel("test-slack-channel-1");
$slack->sendMessage('test-slack-channel-1', 'that is a test message');
$slack->addUserToChannel('test-slack-channel-1', 'Hans Solo');
```
