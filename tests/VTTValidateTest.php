<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class VTTValidateTest extends TestCase {

	protected function getValidVTT():string {

		return <<<END
WEBVTT

00:11.000 --> 00:13.000 vertical:rl
<v Roger Bingham>We are in New York City

00:13.000 --> 00:16.000
<v Roger Bingham>We're actually at the Lucern Hotel, just down the street

00:16.000 --> 00:18.000
<v Roger Bingham>from the American Museum of Natural History

00:18.000 --> 00:20.000
<v Roger Bingham>And with me is Neil deGrasse Tyson

00:20.000 --> 00:22.000
<v Roger Bingham>Astrophysicist, Director of the Hayden Planetarium
END;

	}

	public function testClassCanBeInstantiated(): void {
		$webVTT = new \PHPWebVTT\PHPWebVTT();
		$this->assertInstanceOf(
			\PHPWebVTT\PHPWebVTT::class,
			$webVTT
		);
	}

	public function testCanValidateVTT(): void {
		$webVTT = new \PHPWebVTT\PHPWebVTT();
		$res = $webVTT->parse( $this->getValidVTT() );

		$this->assetTrue($res);

	}

}
