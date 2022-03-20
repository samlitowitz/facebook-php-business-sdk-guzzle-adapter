<?php

namespace FacebookGuzzleAdapter;

use DateTimeImmutable;
use DateTimeInterface;
use FacebookAds\Api;
use FacebookAds\Object\Fields\PageFields;
use FacebookAds\Object\Fields\PagePostFields;
use FacebookAds\Object\Page;
use FacebookAds\Object\PagePost;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;

final class GuzzleAdapterTest extends TestCase {
	private const APP_ID = '<APP_ID>';
	private const APP_SECRET = '<APP_SECRET>';
	private const PAGE_ACCESS_TOKEN = '<PAGE_ACCESS_TOKEN>';
	private const PAGE_ID = '<PAGE_ID>';

	public function test_page_get_self()
	{
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
	}

	public function test_page_update_self()
	{
		$expectedMessage = (new DateTimeImmutable()) ->format(DateTimeInterface::ISO8601);
		Api::init(
			self::APP_ID,
			self::APP_SECRET,
			self::PAGE_ACCESS_TOKEN
		);
		$facebookClient = Api::instance()->getHttpClient();
		$guzzleClient = new Client();
		Api::instance()->getHttpClient()->setAdapter(new GuzzleAdapter($facebookClient, $guzzleClient));

		$page = new Page(self::PAGE_ID);
		$response = $page->createFeed(
			[
				'id',
			],
			[
				'message' => $expectedMessage
			]
		);

		$pagePost = new PagePost($response->{PagePostFields::ID});
		$pagePost = $pagePost->getSelf([
			PagePostFields::ID,
			PagePostFields::MESSAGE,
		]);

		$this->assertEquals($response->{PagePostFields::ID}, $pagePost->{PagePostFields::ID});
		$this->assertEquals($expectedMessage, $pagePost->{PagePostFields::MESSAGE});
	}
}
