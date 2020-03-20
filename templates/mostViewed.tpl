{if $mostReadArticles && sizeof($mostReadArticles)>0}
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
		<style>
			.most-viewed-author {
				font-size: 13px;
				min-width: 100%;
				display: grid;
				grid-template-columns: 1fr fit-content(250px);
			}

			.most-viewed-title a {
				font-size: 14px;
				line-height: 20px;
				font-weight: 700;
				text-decoration: none;
				margin-bottom: 50px;
			}

			.most-viewed-content:not(:last-child) {
				/*border-bottom: 1px solid rgba(0, 0, 0, .125);*/
				margin-bottom: 20px;
			}

			.most-viewed-content .most-viewed-subtitle{
				margin-bottom: 5px;
			}

			.most-viewed-author> div:first-child{
				overflow: hidden;
				text-overflow: ellipsis;
				white-space: nowrap;
			}

			{if $mostReadPosition}
			.most-viewed {
				display: none;
			}

			{/if}
		</style>
        {if $mostReadPosition}
			<script>
				document.addEventListener("DOMContentLoaded", function () {
					let mv_div = document.querySelector('.most-viewed');
					mv_div.parentElement.append(mv_div);
					mv_div.style.display = 'block';
				});
			</script>
        {/if}
	</div>
{/if}