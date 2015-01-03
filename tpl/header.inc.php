<?php
function getIconList()
{
    static $return;
    if(!$return)
    {
        $return = [];

        preg_match_all('/^\.(icon-[a-z0-9-_]+):before/m',file_get_contents(str_replace(BUNZ_HTTP_DIR,BUNZ_DIR,BUNZ_CSS_DIR).'bunzilla-icons.css'),$icons,PREG_SET_ORDER);
        foreach($icons as $icon)
            $return[] = $icon[1];
    }
    return $return;
        
}
function iconSelectBox($selected = false)
{
/**/
    $select = '<select name="icon">';
    $icons = getIconList();
    foreach($icons as $icon)
        $select .= '<option value="'.$icon.'"'.($selected === $icon?' selected':'').' class="'.$icon.'">'.$icon.'</option>';
    $select .= '</select>';
    return $select;
/**
    $fakebox = '<div class="selectBox">';
    $icons = getIconList();
    $selected === false && $selected = current($icons);
    foreach($icons as $icon)
    {
        $id = uniqid();
        $fakebox .= '<input type="radio" id="'.$id.'" name="icon" value="'.$icon.'"'.($selected === $icon?' checked':'').'/><label for="'.$id.'" class="'.$icon.'">'.$icon.'</label>';
    }
    return $fakebox . '</div>';*/
}
function statusButton($statusId)
{
    static $buttons = [];
    if(!isset($buttons[$statusId]) && selectCount('statuses','id = '.(int)$statusId))
    {
        list($id,$title,$color,$icon) = current(db()->query('SELECT * FROM statuses WHERE id = '.(int)$statusId)->fetchAll(PDO::FETCH_NUM));
        $buttons[$id] = '<span class="pure-button '.$icon.'" style="background: #'.$color.'">'.$title.'</span>';
    }
    return isset($buttons[$statusId]) ? $buttons[$statusId] : '';
}
function statusSelectBox($selected = false)
{
    if(!selectCount('statuses'))
        return '<div class="danger">No statuses created <a href="'.BUNZ_HTTP_DIR.'admin">go make one</a>.</div>';

    $select = '<select name="status">';
//    $select = '<div style="position: relative; "><div class="selectBox">';
    foreach(db()->query('SELECT * FROM statuses ORDER BY title ASC')->fetchAll(PDO::FETCH_ASSOC) as $status)
    {
    
       $select .= '<option value="'.$status['id'].'"'.($selected === $status['id'] || !$selected && $status['default'] ?' selected':'').' class="'.$status['icon'].'" style="background: #'.$status['color'].'">'.$status['title'].'</option>';
        $id = uniqid();
//       $select .= '<input type="radio" name="status" id="'.$id.'" value="'.$status['id'].'"'.($selected === $status['id']?' checked':'').'/><label for="'.$id.'" class="'.$status['icon'].'" style="background: #'.$status['color'].'">'.$status['title'].'</label>';
    }
    $select .= '</select>';
//    $select .='</div></div>';
    return $select;
}
?>
<!doctype html>
<html>
    <head>
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <meta name='description' content='<?= BUNZ_PROJECT_MISSION_STATEMENT ?>'>

        <title><?= isset($pageTitle) ? "$pageTitle :: " : '',BUNZ_PROJECT_TITLE, ' :: tracked by Bunzilla' ?></title>

        <link rel='stylesheet' href='<?= BUNZ_CSS_DIR ?>pure-min.css'>
        <link rel='stylesheet' href='<?= BUNZ_CSS_DIR ?>grids-responsive-min.css'>
        <link rel='stylesheet' href='<?= BUNZ_CSS_DIR ?>style.css'>
        <link rel='stylesheet' href='<?= BUNZ_CSS_DIR ?>bunzilla-icons.css'>
<?php
/**
 * highlight.js has a ton of awesome styles
 * this is a shitty way to directory list them 
 * and use View > Page Style in Firefox to pick 

$hljsdir = str_replace(BUNZ_HTTP_DIR,BUNZ_DIR,BUNZ_CSS_DIR).'highlight.js';
$ls = dir($hljsdir);
while(($f = $ls->read()) !== false)
{
    echo is_file($hljsdir.'/'.$f) ? '<link rel="alternate stylesheet" href="'.BUNZ_CSS_DIR.'highlight.js/'.$f.'" title="'.$f.'">'."\n" : '';
}
*/

?>
        <link rel='stylesheet' href='<?= BUNZ_CSS_DIR ?>highlight.js/foundation.css'>
    </head>
    <body id='bunzilla'>
        <header class='header'>
            <nav class='home-menu pure-menu pure-menu-open pure-menu-horizontal'>
                <a class='pure-menu-heading' href='<?= BUNZ_HTTP_DIR ?>'>／(≡・ x ・≡)＼</a>
                <ul>
                    <li><a href='<?= BUNZ_HTTP_DIR ?>admin' class='icon-cog-alt' title='bunzilla settings'></a></li>
                    <li><a href='<?= BUNZ_HTTP_DIR ?>post' class='icon-plus' title='submit new'></a></li>
                </ul>
            </nav>
        </header>
        <nav class='pure-menu pure-menu-open pure-menu-horizontal breadcrumb'>
            <ul>
<?php
$crumbs = [
    BUNZ_PROJECT_TITLE => ['icon' => 'icon-home',
                           'href' => BUNZ_HTTP_DIR]
];
if(isset($bread))
    $crumbs += $bread;

//$iconz = getIconList();
foreach($crumbs as $text => $stuff)
{
    $icon = isset($stuff['icon']) ? $stuff['icon'] : '';
    $href = isset($stuff['href']) ? $stuff['href'] : '#';
    $color =// isset($stuff['color']) ? $stuff['color'] : 
false;
    echo "\t\t\t\t",'<li',($href === BUNZ_HTTP_DIR . $_GET['url']) || count($crumbs) === 1 ? ' class="pure-menu-selected"' : '','><a href="',$href,'" title="',$text,'"><span class="',$icon,'"',$color?' style="background: #'.$color.';"':'','>',$text,'</span></a></li>',"\n";
}
?>
            </ul>
        </nav>
<?php
       
if(!empty($this->flash))
{
    echo '<aside class="flash is-center">';
    foreach($this->flash as $msg)
        echo '<div>',$msg,'</div>';
    echo '</aside>';
}       
