<h1>Articles with Readability Score</h1>
<div class="content">
	<fieldset>
		<legend>Category Information:</legend>
		<form name="get_articles" method="post" action="index.php">
			Category name:
			<input type="text" class="category-name" name="category_name" value="<?= $categoryName ?>" />
			<input type="submit" value="Submit" />
		</form>
	</fieldset>
	<?= $showData ?>
</div>
