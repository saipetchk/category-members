<div class="show-data">
	<table align="center" cellspacing="0">
		<tr>
			<th>Title</th>
			<th>Score</th>
		</tr>
		<?php foreach( $articles as $article ) { ?>
		<tr>
			<td><?= $article['title'] ?></td>
			<td class="data-score"><?= $article['score'] ?></td>
		</tr>
		<?php } ?>
	</table>
</div>
