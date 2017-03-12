<?php

declare( strict_types = 1 );

use PHPUnit\Framework\TestCase;

/**
 * @covers ArticleList
 */
final class ArticleListTest extends TestCase {

	/**
	 * @dataProvider getFirstParagraphDataProvider
	 */
	public function testGetFirstParagraph( $html, $expected ) {
		$articleList = new ArticleList();
		$this->assertEquals( $expected, $articleList->getFirstParagraph( $html ) );
	}

	public function getFirstParagraphDataProvider() {
		$html1 = '<p><b>Meteoritics</b> is a science that deals with meteorites and other extraterrestrial materials that further our understanding of the origin and history of the Solar System.</p> <p>It is closely connected to cosmochemistry, mineralogy and geochemistry.</p>';		$expected1 = '';
		$expected1 = 'Meteoritics is a science that deals with meteorites and other extraterrestrial materials that further our understanding of the origin and history of the Solar System.';

		$html2 = '<b>Meteoritics</b>';
		$expected2 = '';

		$html3 = '<p>This is a glossary of terms used in meteoritics, the science of meteorites.</p>';
		$expected3 = 'This is a glossary of terms used in meteoritics, the science of meteorites.';

		return [
			[ $html1, $expected1 ],
			[ $html2, $expected2 ],
			[ $html3, $expected3 ],
		];
	}

	/**
	 * @dataProvider sortArticlesDataProvider
	 */
	public function testSortArticles( $articles, $expected ) {
		$articleList = new ArticleList();
		$this->assertEquals( $expected, $articleList->sortArticles( $articles ) );
	}

	public function sortArticlesDataProvider() {
		$articles1 = [
			['title' => 'abc', 'extract'=> 'test', 'score' => 100],
			['title' => 'wxy', 'extract'=> 'test', 'score' => 15],
			['title' => 'wxyz', 'extract'=> 'test', 'score' => 50],
		];
		$expected1 = [
			['title' => 'wxy', 'extract'=> 'test', 'score' => 15],
			['title' => 'wxyz', 'extract'=> 'test', 'score' => 50],
			['title' => 'abc', 'extract'=> 'test', 'score' => 100],
		];

		$articles2 = [
			['title' => 'abc', 'extract'=> 'test', 'score' => 100],
			['title' => 'wxyz', 'extract'=> 'test', 'score' => 15],
			['title' => 'wxy', 'extract'=> 'test', 'score' => 15],
		];
		$expected2 = [
			['title' => 'wxy', 'extract'=> 'test', 'score' => 15],
			['title' => 'wxyz', 'extract'=> 'test', 'score' => 15],
			['title' => 'abc', 'extract'=> 'test', 'score' => 100],
		];

		$articles3 = [
			['title' => 'abc', 'extract'=> 'test', 'score' => 100],
			['title' => 'wxy3', 'extract'=> 'test', 'score' => 15],
			['title' => 'wxy1', 'extract'=> 'test', 'score' => 15],
		];
		$expected3 = [
			['title' => 'wxy1', 'extract'=> 'test', 'score' => 15],
			['title' => 'wxy3', 'extract'=> 'test', 'score' => 15],
			['title' => 'abc', 'extract'=> 'test', 'score' => 100],
		];

		return [
			[ $articles1, $expected1 ],
			[ $articles2, $expected2 ],
			[ $articles3, $expected3 ],
		];
	}

}

?>
