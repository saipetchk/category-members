<?php

class ArticleListController {

	public function index() {
		$categoryName = '';
		$showData = '';
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			if ( !empty( $_POST['category_name'] ) ) {
				$categoryName = $this->validateCategoryName( $_POST['category_name'] );
			}

			ob_start();
			$this->show();
			$showData = ob_get_clean();
		}

		require_once('templates/ArticleList_index.php');
	}

	/**
	 * Show list of articles with readability scores
	 */
	public function show() {
		if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] == 'POST' ) {
			if ( !empty( $_POST['category_name'] ) ) {
				require_once("modules/ArticleList.class.php");
				require_once("modules/FileCache.class.php");

				$categoryName = $this->validateCategoryName( $_POST['category_name'] );
				$articles = ( new ArticleList() )->getArticlesWithScores( $categoryName );

				// hide table if articles is empty
				if ( !empty( $articles ) ) {
					require_once('templates/ArticleList_show.php');
				}
			}
		}
	}

	/**
	 * Validate category name
	 * @param string $categoryName - category name
	 * @return string
	 */
	private function validateCategoryName( $categoryName ) {
		$categoryName = trim( $categoryName );
		// check category name
		if ( stripos( $categoryName, 'category:' ) !== 0 ) {
			$categoryName = 'category:' . $categoryName;
		}

		return $categoryName;
	}

}

?>
