<?php

use DaveChild\TextStatistics as TS;

class ArticleList {

	const ARTICLE_LIMIT = 50;
	const EXTRACT_LIMIT = 20;

	const API_URL = 'https://en.wikipedia.org/w/api.php';
	const API_TIMEOUT = 3;

	const CACHE_TTL = 360;

	public static $categoryTypes = ['page', 'file'];

	protected static $queryCategoryMembers = [
		'action' => 'query',
		'format' => 'json',
		'list' => 'categorymembers',
	];

	protected static $queryArticleContent = [
		'action' => 'query',
		'format' => 'json',
		'prop' => 'extracts',
		'exintro' => 1,
	];

	/**
	 * Get list of articles with readability scores
	 * @param $categoryName
	 * @return array|false|mixed
	 */
	public function getArticlesWithScores( $categoryName ) {
		// get cached data
		$cacheInstance = FileCache::getInstance();
		$cacheKey = 'article-score::'.sha1( $categoryName );
		$cachedData = $cacheInstance->getItem( $cacheKey );
		$articles = $cachedData->get();
		if ( !is_null( $articles ) ) {
			return $articles;
		}

		// get list of pages in the category
		$pages = $this->getCategoryMembers( $categoryName, self::$categoryTypes );
		if ( empty( $pages ) ) {
			return [];
		}

		// get content of the pages
		$pagesWithContent = $this->getArticleContent( $categoryName, $pages );
		if ( empty( $pagesWithContent ) ) {
			// set score to 100 if cannot get content
			foreach( $pages as &$page ) {
				$page['score'] = 100;
			}

			return $pages;
		}

		// get readability score of the pages
		$articles = $this->getReadabilityScore( $pagesWithContent );

		// sort by score and title
		$articles = $this->sortArticles( $articles );

		$cachedData->set( $articles )->expiresAfter( self::CACHE_TTL );
		$cacheInstance->save( $cachedData );

		return $articles;
	}

	/**
	 * Sort articles by score and title
	 * @param array $articles - list of articles
	 * @return array
	 */
	public function sortArticles( $articles ) {
		usort( $articles, function( $article1, $article2 ) {
			if ( $article1['score'] == $article2['score'] ) {
				return ( strcmp($article1['title'], $article2['title'] ) > 0 ) ? 1 : 0;
			}

			return ( $article1['score'] < $article2['score'] ) ? -1 : 1;
		});

		return $articles;
	}

	/**
	 * Get category members
	 * @param string $categoryName - category name
	 * @param array $categoryTypes - category types
	 * @param int $limit - article limit
	 * @return array|false
	 */
	private function getCategoryMembers( $categoryName, $categoryTypes, $limit = self::ARTICLE_LIMIT ) {
		$cacheInstance = FileCache::getInstance();
		$cacheKey = 'category-members::'.sha1( $categoryName );
		$cachedData = $cacheInstance->getItem( $cacheKey );
		$pages = $cachedData->get();
		if ( !is_null( $pages ) ) {
			return $pages;
		}

		$query = [
			'cmtitle' => $categoryName,
			'cmtype' => implode( '|', $categoryTypes ),
			'cmlimit' => $limit
		];
		$apiUrl = self::API_URL . '?' . http_build_query( array_merge( self::$queryCategoryMembers, $query ) );

		$resp = $this->sendRequest( $apiUrl );
		if ( $resp === false ) {
			return false;
		}

		$data = json_decode( $resp, true );
		if ( empty( $data['query']['categorymembers'] ) ) {
			$pages = [];
		} else {
			$pages = $data['query']['categorymembers'];
		}

		$cachedData->set( $pages )->expiresAfter( self::CACHE_TTL );
		$cacheInstance->save( $cachedData );

		return $pages;
	}

	/**
	 * Get content of the page
	 * @param string $categoryName - category name
	 * @param array $pages - list of pages
	 * @return array
	 */
	private function getArticleContent( $categoryName, $pages ) {
		$cacheInstance = FileCache::getInstance();
		$cacheKey = 'article-content::'.sha1( $categoryName );
		$cachedData = $cacheInstance->getItem( $cacheKey );
		$results = $cachedData->get();
		if ( !is_null( $results ) ) {
			return $results;
		}

		$results = [];

		$chunks = array_chunk( $pages, self::EXTRACT_LIMIT );
		foreach( $chunks as $chunk ) {
			$titles = array_column( $chunk, 'title' );

			$query = [
				'titles' => implode( '|', $titles ),
				'exlimit' => count( $titles ),
			];
			$apiUrl = self::API_URL . '?' . http_build_query( array_merge( self::$queryArticleContent, $query ) );

			$resp = $this->sendRequest( $apiUrl );
			if ( $resp === false ) {
				return false;
			}

			$data = json_decode( $resp, true );
			foreach( $chunk as $page ) {
				$pageId = $page['pageid'];
				if ( empty( $data['query']['pages'][$pageId]['extract'] ) ) {
					$page['extract'] = '';
				} else {
					$page['extract'] = $this->getFirstParagraph( $data['query']['pages'][$pageId]['extract'] );
				}
				$results[$pageId] = $page;
			}
		}

		$cachedData->set( $results )->expiresAfter( self::CACHE_TTL );
		$cacheInstance->save( $cachedData );

		return $results;
	}

	/**
	 * Get first paragraph
	 * @param string $html
	 * @return string
	 */
	public function getFirstParagraph( $html ) {
		$error_setting = libxml_use_internal_errors( true );

		$document = new DOMDocument();
		$document->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ) );

		libxml_clear_errors();
		libxml_use_internal_errors( $error_setting );

		if ( empty( $document->getElementsByTagName('p')->item(0) ) ) {
			$paragraph = '';
		} else {
			$paragraph = strip_tags( $document->getElementsByTagName('p')->item(0)->nodeValue );
		}

		return $paragraph;
	}

	/**
	 * Get readability score of the page (Flesch-Kincaid Reading Ease)
	 * @param array $pages - list of pages
	 * @return array
	 */
	private function getReadabilityScore( $pages ) {
		$textStatistics = new TS\TextStatistics();
		foreach( $pages as &$page ) {
			$page['score'] = round( $textStatistics->fleschKincaidReadingEase( $page['extract'] ) );
		}

		return $pages;
	}

	/**
	 * Send API request
	 * @param string $url
	 * @return mixed
	 */
	private function sendRequest( $url ) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, self::API_TIMEOUT);

		$data = curl_exec($ch);

		curl_close($ch);

		return $data;
	}

}

?>
