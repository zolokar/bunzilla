<?php
// ok let's do this right this time

// random bunny emotes
// http://japaneseemoticons.net/ <- go there, highly recommended
require_once BUNZ_TPL_DIR . 'bunnies.php';
require_once BUNZ_TPL_DIR . 'displayfuncs.inc.php';
?>
<!DOCTYPE html>
<html>
    <head>
<!--
    muh semantic web
-->
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <meta name='description' content='<?= BUNZ_PROJECT_MISSION_STATEMENT ?>'>

        <title><?= isset($pageTitle) ? "$pageTitle :: " : '',BUNZ_PROJECT_TITLE, ' :: tracked by Bunzilla' ?></title>

<!--        <link rel='stylesheet' href='<?= BUNZ_CSS_DIR ?>materialize.min.css'>-->
<!--        <link rel='stylesheet' href='/bunzilla/material/materialize.min.css'>-->
        <link rel='stylesheet' href='<?= str_replace(BUNZ_DIR,BUNZ_HTTP_DIR,BUNZ_TPL_DIR) ?>assets/css/materialize.min.css'>

        <link rel='stylesheet' href='<?= BUNZ_CSS_DIR ?>bunzilla-icons.css'>
        <link rel='stylesheet' href='<?= BUNZ_CSS_DIR ?>highlight.js/foundation.css'>
        <link rel='stylesheet' href='<?= str_replace(BUNZ_DIR,BUNZ_HTTP_DIR,BUNZ_TPL_DIR) ?>temp.css'>
        <link rel='stylesheet' type='text/css' href='<?=  str_replace(BUNZ_DIR,BUNZ_HTTP_DIR,BUNZ_TPL_DIR) ?>customcolors.css.php'>

        <link rel='stylesheet' href='<?=  str_replace(BUNZ_DIR,BUNZ_HTTP_DIR,BUNZ_TPL_DIR) ?>gn-codrops.css'>
    </head>

<!-- 
    main screen turn on
-->
    <body id="bunzilla">
   <div id="page-wrap"<?= isset($background) ? " class='$background'" : ''?>>
        <header>

<!--
    google nexus 7 product page menu from codrops
-->
        <ul id="gn-menu" class="gn-menu-main">
<!--
    sidebar
-->
            <li class="gn-trigger primary-text" style="background: transparent !important">
                <a class="gn-icon gn-icon-menu icon-hamburger"><span>Navigation</span></a>
                <nav class="gn-menu-wrapper z-depth-5">
                    <div class="gn-scroller">
<!--
    all sidebar items go in this ul:
-->
                        <ul class="gn-menu">
                            <li class="gn-search-item">
                                <form action="<?= BUNZ_HTTP_DIR ?>search" method="get">
                                <button type="submit" title="Search!" class="btn btn-floating right secondary-base gone" id="searchsubmit" style="margin-top: 0.5em; margin-right: 1em">&#8617;</button>
                                <div class="input-field">
                                    <i class="gn-icon icon-search">
                                    <span>Search</span></i>
                                    <input placeholder="Search" type="text" name="q" class="gn-search" onfocus="document.getElementById('searchsubmit').classList.remove('gone')" onblur="if(this.value === ''){document.getElementById('searchsubmit').classList.add('gone')}">
                                    <span class="material-input"></span>
                                </div>
                                </form>
                            </li>
<!--
    ex:

    <li>
        <a class="icon-whatever">Heading</a>
        <ul class="gn-submenu"><li><a>etc</li></ul>
    </li>

                            <li>
                                <a class="gn-icon icon-male">test</a>
                                <ul class="gn-submenu"><li class="gn-icon icon-person">test</li></ul>
                            </li>
                            <li>
                                <a class="gn-icon icon-male">test</a>
                                <ul class="gn-submenu"><li class="gn-icon icon-person">test</li></ul>
                            </li>
                            <li>
                                <a class="gn-icon icon-male">test</a>
                                <ul class="gn-submenu"><li class="gn-icon icon-person">test</li></ul>
                            </li>
                            <li>
                                <a class="gn-icon icon-male">test</a>
                                <ul class="gn-submenu"><li class="gn-icon icon-person">test</li></ul>
                            </li>
                            <li>
                                <a class="gn-icon icon-male">test</a>
                                <ul class="gn-submenu"><li class="gn-icon icon-person">test</li></ul>
                            </li>
-->
                        </ul>
                    </div>
                </nav>
            </li>
<!--
    ~brand~ of our ~product~
-->
            <li title="<?= $_BUNNIES[array_rand($_BUNNIES)] ?>"></li>

<?php
if(isset($this->breadcrumbs) && count($this->breadcrumbs))
{
    $category = isset($this->data['category_id']) ? $this->data['categories'][$this->data['category_id']] : null;
    echo "\t\t\t",'<li class="hide-on-small-only bc-parent">',"\n",
        "\t\t\t\t",'<div class="row section no-pad z-depth-3 bc-container">',"\n";

    $colSize = 12 / count($this->breadcrumbs);
    $offset  = 0;
    if(is_float($colSize))
    {
        $colSize = floor($colSize);
        $offset  = (int)($colSize * count($this->breadcrumbs) - 12);
    }

    foreach($this->breadcrumbs as $i => $crumb)
    {
        $prevClassName = isset($className) ? $className : null;
        $className = strpos($crumb['href'],'report/category') === 0
                ? "category-{$category['id']}-base" 
                : (strpos($crumb['href'],$this->tpl) === 0 ? 'secondary-text' : 'primary-text');
        echo "\t\t\t\t\t",
            '<div class="bc-item col s',$colSize,
            $offset ? " offset-s$offset" : '',
            ' ',
            $className,
            '">',"\n\t\t\t\t\t\t";

        if($prevClassName)
        {
            /**
             * just go with it */
            $offset = 0;
            list($what,$why) = strpos($prevClassName, 'text') !== false ? ['text','base'] : ['base','text'];
            echo '<div class="bc-triangle ', str_replace($what, $why, $prevClassName) ,'"></div>',"\n";
        }
        if(strpos($crumb['href'],'report/category') === 0)
        {
/**

    ;_;

            echo categoryDropdown(
                isset($category) ? $category['id'] : 0,
                false,
                'window.location="'.BUNZ_HTTP_DIR.'report/category/" + this.value'
            );
**/
            $currentCat = $this->data['categories'][$category['id']];
            echo '<a href="',BUNZ_HTTP_DIR,'report/category/',$category['id'],'"
class="waves-effect  hide-overflow-text ',$currentCat['icon'],'"><span class="hide-on-med-and-down">',$currentCat['title'],'</span></a>
<a class="dropdown-button btn ',$className,'" data-activates="bc-catlist">&nbsp;</a>';
        } else {
            echo '<a href="',BUNZ_HTTP_DIR,$crumb['href'],
            '" class="waves-effect  hide-overflow-text ',
            isset($crumb['icon']) ? $crumb['icon'] : '',
            '"><span class="hide-on-med-and-down">',$crumb['title'],'</span></a>',"\n";
        }
        echo "\t\t\t\t\t</div>\n";
    }
    echo "\t\t\t\t</div>\n\t\t\t\t</li>\n\n";
}
//    breadcrumb($this->breadcrumbs, $this->tpl, isset($category) ? $category['id'] : 0);
/* todo
            <li class="hide-on-small-only"><a href="#" class="icon-home">Bread</a></li>
            <li class="hide-on-small-only"><a href="#" class="icon-list-dl">Crumb</a></li>
            <li class="hide-on-small-only"><a href="#" class="icon-bug">Trail</a></li>
*/
?>
<?php
if($this->auth())
{
?>
            <li class="gone gn-multiline right">
                <a href="?logout" class="btn "><i class=" icon-logout"></i><!--Logout<small><?= $_SERVER['PHP_AUTH_USER'] ?></small>--></a>
                <a href="<?= BUNZ_HTTP_DIR ?>cpanel" class="btn"><i class=" icon-cog-alt"></i><!--Cpanel--></a>
            </li>
<?php
} else {
?>
            <li class="right">
                <a href="?login" class="btn secondary-darken-4 icon-key"><span class="hide-on-med-only">Login</span></a>
            </li>
<?php
}
?>
        </ul>
<?php
if(isset($currentCat))
{
?>
    <ul id="bc-catlist" class="dropdown-content">
<?php
    foreach($this->data['categories'] as $c)
    {
        if($c['id'] == $currentCat['id'])
            continue;

        echo '<li class=""><a href="',BUNZ_HTTP_DIR,'report/category/',$c['id'],'" class="waves-effect  ',$c['icon'], ' category-',$c['id'],'-base" title="',$c['caption'],'">',$c['title'],'</a></li>';
    }
    unset($c);
    echo '</ul>';
}
?>
        </header>

        <noscript>
            <aside class="yellow h1">
                <p><b>Note:</b> This layout uses a large amount of Javascript.</p>
                <p>Certain things may not work as intended with Javascript disabled.</p>
                <p>You may find <a href="?nofrills">this layout</a> more suitable to your tastes.</p>
                <p>By the way, the 1970s called. When are you coming home, grandpa?</p>
            </aside>
        </noscript>
        <main>
