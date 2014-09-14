{if !empty($news)}
    {foreach $new in $news}
        <article class="transparentAbout about">
            <h1 class="visuallyhidden">{echo $new->title}</h1>
            
            <div >
                <div>{echo $new->body}</div>
            </div>
        </article>
    {/foreach}
{/if}
{else}
    <div class="errorDiv transparentAbout">
        <span>Nebyly nalezeny žádné reference</span>
    </div>
{/else}