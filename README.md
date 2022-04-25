# Facebook PHP Business SDK Guzzle Adapter

# Install
`composer require samlitowitz/facebook-php-business-sdk-guzzle-adapter`

# Use
```php
Api::init(
	self::APP_ID,
	self::APP_SECRET,
	self::PAGE_ACCESS_TOKEN
);
$facebookClient = Api::instance()->getHttpClient();
$guzzleClient = new Client();
Api::instance()->getHttpClient()->setAdapter(new GuzzleAdapter($facebookClient, $guzzleClient));
$page = new Page(self::PAGE_ID);

$page->getSelf();
$this->assertEquals(self::PAGE_ID, $page->{PageFields::ID});
```
