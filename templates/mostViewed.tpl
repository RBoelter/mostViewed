<div class="card mb-3 default-card-layout">
    <div class="card-body">
        <div class="card-title">
            <h2>{translate key="plugins.generic.most.viewed.headline"}</h2>
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
                        {$article['articleAuthor']|strip_unsafe_html}
                    </div>
                    <div class="col-auto">
                        <span class="badge"><i class="fa fa-eye" aria-hidden="true"></i>&nbsp;{$article['metric']|strip_unsafe_html}</span>
                    </div>
                </div>
            </li>
        {/foreach}
    </ul>
</div>
