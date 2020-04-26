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
	<div class="card mb-3 default-card-layout most-viewed">
		<div class="card-body">
			<div class="card-title">
				<h2>{if $mostReadHeadline}{$mostReadHeadline}{else}{translate key="plugins.generic.most.viewed.headline"}{/if}</h2>
			</div>
		</div>
		<ul class="list-group list-group-flush">
            {foreach from=$mostReadArticles item="article"}
				<li class="list-group-item">
					<div class="row">
						<div class="col text-truncate">
							<a href={url page="article" op="view" path=$article['articleId']}>
                                {$article['articleTitle']|strip_unsafe_html}
							</a>
                            {if $article['articleSubtitle']}
								<div>{$article['articleSubtitle']|strip_unsafe_html}</div>
                            {/if}
							<div class="font-italic">{$article['articleAuthor']|strip_unsafe_html}</div>
						</div>
						<div class="col-auto">
                        <span class="badge"><i class="fa fa-eye"
                                               aria-hidden="true"></i>&nbsp;{$article['metric']|strip_unsafe_html}</span>
						</div>
					</div>
				</li>
            {/foreach}
		</ul>
	</div>
{/if}

