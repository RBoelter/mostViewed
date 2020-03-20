{if $mostReadArticles && sizeof($mostReadArticles)>0}
	<div class="most-viewed">
		<h2 class="most-viewed-headline">{if $mostReadHeadline}{$mostReadHeadline}{else}{translate key="plugins.generic.most.viewed.headline"}{/if}</h2>
        {foreach from=$mostReadArticles item="article"}
			<div class="most-viewed-content">
				<div>
					<a href={url page="article" op="view" path=$article['articleId']}>
                        {$article['articleTitle']|strip_unsafe_html}
					</a>
					<br/>
                    {if $article['articleSubtitle']}
                        {$article['articleSubtitle']|strip_unsafe_html}
						<br/>
                    {/if}
					<span class="font-italic">{$article['articleAuthor']|strip_unsafe_html}</span>
				</div>
				<div>
					<span class="badge"><i class="fa fa-eye" aria-hidden="true"></i>&nbsp;{$article['metric']|strip_unsafe_html}</span>
				</div>
			</div>
        {/foreach}
		<style>
			.most-viewed-content {
				min-width: 100%;
				display: grid;
				grid-template-columns: 1fr fit-content(250px);
			}

			.most-viewed-content:not(:last-child) {
				border-bottom: 1px solid rgba(0, 0, 0, .125);
				margin-bottom: 5px;
			}

			.most-viewed-content > div:first-child {
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