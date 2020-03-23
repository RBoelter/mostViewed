{if $mostReadArticles && sizeof($mostReadArticles)>0}
    {if $mostReadPosition}
		<script>
			document.addEventListener("DOMContentLoaded", function () {
				let mv_div = document.querySelector('.most-viewed');
				mv_div.parentElement.append(mv_div);
				mv_div.style.display = 'block';
			});
		</script>
		<style>
			.most-viewed {
				display: none;
			}
		</style>
    {/if}
	<div class="most-viewed obj_article_summary">
		<h2 class="most-viewed-headline">{if $mostReadHeadline}{$mostReadHeadline}{else}{translate key="plugins.generic.most.viewed.headline"}{/if}</h2>
        {foreach from=$mostReadArticles item="article"}
			<div class="most-viewed-content">
				<div class="most-viewed-title">
					<a href={url page="article" op="view" path=$article['articleId']}>
                        {$article['articleTitle']|strip_unsafe_html}
					</a>
				</div>
				<div class="most-viewed-subtitle">
                    {if $article['articleSubtitle']}
                        {$article['articleSubtitle']|strip_unsafe_html}
                    {/if}
				</div>
				<div class="most-viewed-author">
					<div class="font-italic">{$article['articleAuthor']|strip_unsafe_html}</div>
					<div>
						<span class="badge"><i class="fa fa-eye" aria-hidden="true"></i>&nbsp;{$article['metric']|strip_unsafe_html}</span>
					</div>
				</div>
			</div>
        {/foreach}
	</div>
{/if}