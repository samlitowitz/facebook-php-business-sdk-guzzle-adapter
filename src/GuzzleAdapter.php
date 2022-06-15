<?php

namespace FacebookGuzzleAdapter;

use FacebookAds\Http\Adapter\AbstractAdapter;
use FacebookAds\Http\Client as FacebookClient;
use FacebookAds\Http\FileParameter;
use FacebookAds\Http\Headers as FacebookHeaders;
use FacebookAds\Http\RequestInterface;
use FacebookAds\Http\ResponseInterface;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\MultipartStream;
use GuzzleHttp\Psr7\Request as Psr7Request;

final class GuzzleAdapter extends AbstractAdapter {
	/** @var GuzzleClient */
	private $guzzleClient;

	/** @var \ArrayObject */
	private $opts;

	public function __construct(FacebookClient $facebookClient, GuzzleClient $guzzleClient = null)
	{
		$this->guzzleClient = $guzzleClient ?: new GuzzleClient();
		parent::__construct($facebookClient);
	}

	public function getOpts()
	{
		if ($this->opts === null) {
			$this->opts = new \ArrayObject([
				'connect_timeout' => 10,
				'timeout' => 60,
				'verify' => $this->getCaBundlePath(),
			]);
		}
		return $this->opts;
	}

	public function setOpts(\ArrayObject $opts)
	{
		$this->opts = $opts;
	}

	public function sendRequest(RequestInterface $request)
	{
		$method = $request->getMethod();

		switch ($method) {
			case RequestInterface::METHOD_GET:
				return $this->sendGETRequest($request);

			case RequestInterface::METHOD_POST:
				return $this->sendPOSTRequest($request);

			default:
				return $this->sendOtherRequest($request);
		}
	}

	private function sendGETRequest(RequestInterface $request): ResponseInterface
	{
		$psrRequest = new Psr7Request(
			$request->getMethod(),
			$request->getUrl(),
			$request->getHeaders()->getArrayCopy()
		);
		$psrResponse = $this->guzzleClient->send($psrRequest, $this->getOpts()->getArrayCopy());
		$response = $this->getClient()->createResponse();

		$response->setStatusCode($psrResponse->getStatusCode());
		$response->setHeaders(new FacebookHeaders($psrResponse->getHeaders()));
		$response->setBody($psrResponse->getBody()->getContents());

		return $response;
	}

	private function sendPOSTRequest(RequestInterface $request): ResponseInterface
	{
		$fileParams = $request->getFileParams()->getArrayCopy();
		$fileParamsForMultipart = [];
		foreach ($fileParams as $key => $filepath) {
			if ($filepath instanceof FileParameter) {
				$fileParamsForMultipart[] = [
					'name' => $key,
					'contents' => fopen($filepath->getPath(), 'r'),
					'filename' => $filepath->getName() ?? '',
				];
				continue;
			}
			$fileParamsForMultipart[] = [
				'name' => $key,
				'contents' => fopen($filepath, 'r'),
			];
		}

		$bodyParamsExport = $request->getBodyParams()->export();
		$bodyParamsForMultipart = [];
		foreach ($bodyParamsExport as $key => $value) {
			$bodyParamsForMultipart[] = [
				'name' => $key,
				'contents' => $value,
			];
		}

		$multipart = new MultipartStream(
			array_merge(
				$fileParamsForMultipart,
				$bodyParamsForMultipart
			)
		);
		$psrRequest = new Psr7Request(
			$request->getMethod(),
			$request->getUrl(),
			$request->getHeaders()->getArrayCopy(),
			$multipart
		);
		$psrResponse = $this->guzzleClient->send($psrRequest, $this->getOpts()->getArrayCopy());
		$response = $this->getClient()->createResponse();

		$response->setStatusCode($psrResponse->getStatusCode());
		$response->setHeaders(new FacebookHeaders($psrResponse->getHeaders()));
		$response->setBody($psrResponse->getBody()->getContents());

		return $response;
	}

	private function sendOtherRequest(RequestInterface $request): ResponseInterface
	{
		$bodyParamsExport = $request->getBodyParams()->export();
		$bodyParamsForMultipart = [];
		foreach ($bodyParamsExport as $key => $value) {
			$bodyParamsForMultipart[] = [
				'name' => $key,
				'contents' => $value,
			];
		}

		$multipart = new MultipartStream($bodyParamsForMultipart);
		$psrRequest = new Psr7Request(
			$request->getMethod(),
			$request->getUrl(),
			$request->getHeaders()->getArrayCopy(),
			$multipart
		);
		$psrResponse = $this->guzzleClient->send($psrRequest, $this->getOpts()->getArrayCopy());
		$response = $this->getClient()->createResponse();

		$response->setStatusCode($psrResponse->getStatusCode());
		$response->setHeaders(new FacebookHeaders($psrResponse->getHeaders()));
		$response->setBody($psrResponse->getBody()->getContents());

		return $response;
	}
}
