<?php
/**
 * Control Panel Controller */
class cpanel extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->requireLogin();
    }
    public $breadcrumbs = [];
    public function setBreadcrumbs($method)
    {
        $this->breadcrumbs[] = ['href' => '', 
                                'title' => BUNZ_PROJECT_TITLE,
                                'icon'  => 'icon-home'];

        $this->breadcrumbs[] = ['href' => 'cpanel/index', 
                                'title' => 'cpanel',
                                'icon'  => 'icon-cog'];

        switch($method)
        {
            case 'tagEdit':
                $this->breadcrumbs[] = ['href' => 'cpanel#tags',
                                        'title' => 'Tags',
                                        'icon' => 'icon-tags'];
                return;                
        }

        return;
    }
    /**
     * The cpanel itself */
    public function index()
    {
        $this->tpl .= '/index';

        $this->data['statistics'] = [
            'total_reports' => selectCount('reports'),
            'open_reports'  => selectCount('reports', 'closed = 0'),
            'prolific_user' => db()->query('SELECT email FROM reports ORDER BY RAND() LIMIT 1')->fetchColumn()
        ];

        $this->data['changelog'] = db()->query('SELECT message FROM change_log ORDER BY time DESC')->fetchAll(PDO::FETCH_NUM);

/*        
        $this->data
            'categories' => selectCount('categories') ? db()->query(
                'SELECT c.*, COUNT(r.id) AS total_reports, MAX(r.time) AS last_submission
                 FROM categories AS c
                    LEFT JOIN reports AS r
                    ON c.id = r.category
                 GROUP BY c.id
                 ORDER BY c.title ASC'
                )->fetchAll(PDO::FETCH_ASSOC) : null,
            'statuses' => selectCount('statuses') ? db()->query(
                'SELECT s.*, COUNT(r.id) AS total_reports
                 FROM statuses AS s
                    LEFT JOIN reports AS r
                    ON s.id = r.status
                 GROUP BY s.id
                 ORDER BY s.title ASC'
                )->fetchAll(PDO::FETCH_ASSOC) : null,
            'tags' => selectCount('tags') ? db()->query(
                'SELECT t.*, COUNT(tj.id) AS total_reports
                 FROM tags AS t
                    LEFT JOIN tag_joins AS tj
                    ON t.id = tj.tag
                GROUP BY t.id
                ORDER BY t.title ASC'
                )->fetchAll(PDO::FETCH_ASSOC) : null
        ];*/

        $this->setBreadcrumbs(__FUNCTION__);
    }

    private function __NOTWORKING_crontab()
    {
        /**
         * harmful */
        $user = system('whoami', $exit_code);
        if($exit_code != 0)
            echo 'whoami doesn\'t work';
        unset($exit_code);

        $cron = system('crontab -u '.$user.' -l',$exit_code);
        $test = '# This is a test';

        if($exit_code == 127)
            echo 'crontab not installed';

        if(preg_match('/^no crontab/', $cron))
        {
            $cron = $test;
        } else {
            $cron .= "\n$test";
        }

        unset($exit_code);

        $temp = tempnam('/tmp', 'cron-');
        file_put_contents($temp, $cron);

        echo system('crontab -u '.$user.' '.$temp, $exit_code);
        echo "\n$exit_code";
    }

    /**
     * INSERT */
    public function add( )
    {
        $args = func_get_args();
        $mode = empty($args) ? null : array_shift($args);
        switch($mode)
        {
            case 'category':
                $tbl = 'categories';
                break;
            case 'status':
                $tbl = 'statuses';
                break;

            case 'tag':
                $tbl = 'tags';
                break;

            case 'priority':
                $tbl = 'priorities';
                break;

            default:
                $this->abort('Unsupported action!');
        }

        call_user_func_array([$this,$mode.'Add'],$args);
        Statuslog::create($mode, db()->lastInsertId(), 'created');
        Cache::clear($tbl);
        $this->index();
    }

    /**
     * UPDATE */
    public function edit()
    {
        $args = func_get_args();
        $mode = empty($args) ? null : array_shift($args);
        switch($mode)
        {
            case 'category':
                $tbl = 'categories';
                if(isset($_POST['move']))
                    $this->handleMove();
                break;

            case 'status':
                $tbl = 'statuses';
                $this->handleSetDefault($mode,$tbl);
                break;

            case 'tag':
                $tbl = 'tags';
                break;

            case 'priority':
                $tbl = 'priorities';
                $this->handleSetDefault($mode,$tbl);
                break;

            default:
                $this->abort('Unsupported action!');
        }
        call_user_func_array([$this,$mode.'Edit'],$args);
        Statuslog::create($mode, (int)$args[0], 'modified');
        Cache::clear($tbl);
        $this->index();
    }

    private function handleSetDefault($field, $tbl)
    {
        if(isset($_POST['set_default']))
        {
            $id = (int)$_POST['set_default'];
            if(!selectCount($tbl,"id = $id"))
                $this->abort("invalid $field");
            db()->query("UPDATE $tbl SET `default` = 1 WHERE id = $id");
            db()->query("UPDATE $tbl SET `default` = 0 WHERE id != $id");
            Statuslog::create($field, $id, 'is now the default '.$field);
            Cache::clear($tbl);
            $this->flash[] = 'Default '.$field.' updated.';
            $this->index();
            exit;
        }
    }

    /**
     * DELETE FROM */
    public function delete($mode,$id)
    {
        $id = (int)$id;
        switch($mode)
        {
            case 'category':
                $table = 'categories';
                $field = 'category';
                $comments = db()->query('DELETE FROM comments WHERE report IN (SELECT id FROM reports WHERE category = '.$id.')')->rowCount();
                db()->query('DELETE FROM tag_joins WHERE report IN (SELECT id FROM reports WHERE category = '.$id.')');
                $reports = db()->query('DELETE FROM reports WHERE category = '.$id)->rowCount();

                if($reports + $comments)
                    Statuslog::globalMessage(sprintf('%d report%s and %d comment%s were purged by %s',
                        $reports, $reports == 1 ? '' : 's', $comments, $comments == 1 ? '' : 's',
                        $_SERVER['PHP_AUTH_USER']
                    ));

                $this->flash[] = $reports .' report(s) and '.$comments.' comment(s) were purged as a result of this action.';
                break;
            case 'status':
                $table = 'statuses';
                $field = 'status';
                $default_status = db()->query('SELECT id FROM statuses WHERE `default` = 1')->fetchColumn();
                if(selectCount('statuses') == 1)
                    $this->abort('Can\'t delete the only status! (Try editing it instead)');
                if($default_status == $id)
                {
                    $default_status = db()->query('SELECT id FROM statuses WHERE id != '.$id.' ORDER BY RAND() LIMIT 1')->fetchColumn();
                    db()->query('UPDATE statuses SET `default` = 1 WHERE id = '.$default_status);
                    $this->flash[] = 'New default status is '.statusButton($default_status);
                }
                db()->query('UPDATE reports SET status = '.$default_status.' WHERE status = '.$id);
                $stat = Cache::read('status');
                Statuslog::create('status', $default_status, 
                    'all reports previously marked '.$stat[$id]['title'].' are now marked as '.$stat[$id]['title']
                );
                break;
            case 'tag':
                $table = 'tags';
                $field = 'tag';
                db()->query('DELETE FROM tag_joins WHERE tag = '.$id);
                break;

            default:
                $this->abort('Unsupported action!');
        }
        
        db()->query('DELETE FROM '.$table.' WHERE id = '.$id);
        Statuslog::globalMessage(sprintf('%s "%s" was permanently deleted by %s',
            $mode,Cache::read($table)[$id]['title'],$_SERVER['PHP_AUTH_USER']
        ));
        Cache::clear($table);
        $this->flash[] = $field .' permanently deleted. You monster.';
        $this->index();        
    }

    /**
     * for to consolidating the reports */
    private function handleMove()
    {   
        if(!isset($_POST['from'],$_POST['to']))
            return;

        $from = (int)$_POST['from'];
        $to   = (int)$_POST['to'];

        if(selectCount('categories',"id IN ($from,$to)") != 2)
        {
            $this->flash[] = 'Invalid parameter';
        } else {
            $reports = db()->query(
                'SELECT id, category, description, reproduce, expected, actual
                 FROM reports
                 WHERE category = '.$from
            )->fetchAll(PDO::FETCH_ASSOC);
            
            require_once BUNZ_CTL_DIR . 'report.php';

            if(report::moveBulk($reports,$from,$to))
            {
                $categories = Cache::read('categories');
                Statuslog::create('category',$to,sprintf(
                    'All reports in category "%s" were moved to "%s"',
                    $categories[$from]['title'],
                    $categories[$to]['title']
                ));
                $this->flash[] = count($reports) .' were moved to ' . $categories[$to]['title'];
            } else {
                $this->abort('Something bad happened.');
            }
        }
        $this->index();
        exit;
    }
    protected function move( $from, $to )
    {
        $reports = db()->query('UPDATE reports SET category = '.(int)$to.' WHERE category = '.(int)$from)->rowCount();
        $cats = Cache::read('categories');
        Statuslog::create('category',$to,sprintf(
            'all reports in category "%s" were moved to "%s"',
            $cats[$from]['title'],
            $cats[$to]['title']
        ));
        $this->index();
        $this->flash[] = $reports .' were moved to ';
    }
    // Controller::__construct() checks for method_exists()
    // so much for efficiency
    /**
     * public function __call( $method, $args )
     * {
     * }
     */

    /**
     * Don't Repeat Yourself!
     * I mean you did already with add()/edit()/delete()
     * asdf */
    private function _exec($sql,$params)
    {
        if($params === false || empty($params))
            $this->abort('Bad form!');

        foreach($params as $k => $v)
            $params[$k] = (string)$v;

        $stmt = db()->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * OK but really factoring all this shit out is awesome */
    private function categoryAdd()
    {
        $params = filter_input_array(INPUT_POST, [
            'title' => filterOptions(0,'full_special_chars'),
            'caption' => filterOptions(0,'full_special_chars'),
            'description' => filterOptions(1,'boolean'),
            'reproduce' => filterOptions(1,'boolean'),
            'actual' => filterOptions(1,'boolean'),
            'expected' => filterOptions(1,'boolean'),
            'color' => filterOptions(1,'regexp',null,
                ['regexp'=>'/^[0-9a-f]{6}/i']),
            'icon' => filterOptions(0,'full_special_chars')
        ]);
        $sql = 
            'INSERT INTO categories

(id,title,caption,description,reproduce,expected,actual,color,icon)

            VALUES

(\'\',:title,:caption,:description,:reproduce,:actual,:expected,:color,:icon)';

        if($this->_exec($sql,$params))
            $this->flash[] = 'Category added.';
    }

    private function statusAdd()
    {
        $params = filter_input_array(INPUT_POST, [
            'title' => filterOptions(0,'full_special_chars'),
            'color' => filterOptions(1,'regexp',null,
                ['regexp'=>'/^[0-9a-f]{6}/i']),
            'icon' => filterOptions(0,'full_special_chars')
        ]);
        $params['def'] = !selectCount('statuses') ? 1 : 0;
        $sql = 
            'INSERT INTO statuses
                (id,title,color,icon,`default`)
            VALUES
                (\'\',:title,:color,:icon,:def)';
        if($this->_exec($sql,$params))
            $this->flash[] = 'Status added';
    }

    private function priorityAdd()
    {
        $params = filter_input_array(INPUT_POST, [
            'id'    => filterOptions(0, 'number_int'),
            'title' => filterOptions(0,'full_special_chars'),
            'color' => filterOptions(1,'regexp',null,
                ['regexp'=>'/^[0-9a-f]{6}/i']),
            'icon' => filterOptions(0,'full_special_chars')
        ]);
        $params['def'] = !selectCount('priority') ? 1 : 0;
        $sql = 
            'INSERT INTO priority
                (id,title,color,icon,`default`)
            VALUES
                (\'\',:title,:color,:icon,:def)';
        if($this->_exec($sql,$params))
            $this->flash[] = 'Priority added';
    }


    private function tagAdd()
    {
        $params = filter_input_array(INPUT_POST, [
            'title' => filterOptions(0,'full_special_chars'),
            'color' => filterOptions(1,'regexp',null,
                ['regexp'=>'/^[0-9a-f]{6}/i']),
            'icon' => filterOptions(0,'full_special_chars')
        ]);
        $sql = 
            'INSERT INTO tags
                (id,title,color,icon)
            VALUES
                (\'\',:title,:color,:icon)';
        if($this->_exec($sql,$params))
            $this->flash[] = 'Tag added';
    }

    private function categoryEdit($id)
    {
        if(!selectCount('categories','id = '.(int)$id))
            $this->abort('No such category!');
       
        $this->data['category'] = current(db()->query(
            'SELECT * FROM categories WHERE id = '.(int)$id
        )->fetchAll(PDO::FETCH_ASSOC));

        if(empty($_POST))
        {
            $this->tpl .= '/categoryEdit';
            exit;
        }

        $params = filter_input_array(INPUT_POST, [
            'title' => filterOptions(0,'full_special_chars','null_on_failure'),
            'caption'=>filterOptions(0,'full_special_chars','null_on_failure'),
            'description' => filterOptions(1,'boolean','null_on_failure'),
            'reproduce' => filterOptions(1,'boolean','null_on_failure'),
            'actual' => filterOptions(1,'boolean','null_on_failure'),
            'expected' => filterOptions(1,'boolean','null_on_failure'),
            'color' => filterOptions(1,'regexp','null_on_failure',
                ['regexp'=>'/^[0-9a-f]{6}/i']),
            'icon' => filterOptions(0,'full_special_chars','null_on_failure')
        ]);

        $set = [];
        foreach($params as $field => $value)
        {
            if($value === null)
                unset($params[$field]);
            else
                $set[] = $field .' = :'. $field;
        }

        if(empty($params))
        {
            $this->flash[] = 'No changes made.';
            $this->index();
            exit;
        }

        $sql = 'UPDATE categories SET '.implode(',',$set)
            .' WHERE id = '.(int)$id;

        if($this->_exec($sql,$params))
            $this->flash[] = 'Category updated.';
    }

    private function statusEdit($id)
    {
        if(!selectCount('statuses','id = '.(int)$id))
            $this->abort('No such status!');
       
        $this->data['status'] = current(db()->query(
            'SELECT * FROM statuses WHERE id = '.(int)$id
        )->fetchAll(PDO::FETCH_ASSOC));

        if(empty($_POST))
        {
            $this->tpl .= '/statusEdit';
            exit;
        }

        $params = filter_input_array(INPUT_POST, [
            'title' => filterOptions(0,'full_special_chars','null_on_failure'),
            'color' => filterOptions(1,'regexp','null_on_failure',
                ['regexp'=>'/^[0-9a-f]{6}/i']),
            'icon' => filterOptions(0,'full_special_chars','null_on_failure')
        ]);

        $set = [];
        foreach($params as $field => $value)
        {
            if($value === null)
                unset($params[$field]);
            else
                $set[] = $field .' = :'. $field;
        }

        if(empty($params))
        {
            $this->flash[] = 'No changes made.';
            $this->index();
            exit;
        }

        $sql = 'UPDATE statuses SET '.implode(',',$set).' WHERE id = '.(int)$id;

        if($this->_exec($sql,$params))
            $this->flash[] = 'Status updated.';
    }

    private function priorityEdit($id)
    {
        if(!selectCount('priorities','id = '.(int)$id))
            $this->abort('No such priority!');
       
        $this->data['priority'] = current(db()->query(
            'SELECT * FROM priorities WHERE id = '.(int)$id
        )->fetchAll(PDO::FETCH_ASSOC));

        if(empty($_POST))
        {
            $this->tpl .= '/priorityEdit';
            exit;
        }

        $params = filter_input_array(INPUT_POST, [
            'id'    => filterOptions(0, 'number_int'),
            'title' => filterOptions(0,'full_special_chars','null_on_failure'),
            'color' => filterOptions(1,'regexp','null_on_failure',
                ['regexp'=>'/^[0-9a-f]{6}/i']),
            'icon' => filterOptions(0,'full_special_chars','null_on_failure')
        ]);

        $set = [];
        foreach($params as $field => $value)
        {
            if($value === null)
                unset($params[$field]);
            else
                $set[] = $field .' = :'. $field;
        }

        if(empty($params))
        {
            $this->flash[] = 'No changes made.';
            $this->index();
            exit;
        }

        $sql = 'UPDATE priorities SET '.implode(',',$set).' WHERE id = '.(int)$id;

        if($this->_exec($sql,$params))
            $this->flash[] = 'Priority updated.';
    }

    private function tagEdit($id)
    {
        if(!selectCount('tags','id = '.(int)$id))
            $this->abort('No such status!');
       
        $this->data['tag'] = current(db()->query(
            'SELECT * FROM tags WHERE id = '.(int)$id
        )->fetchAll(PDO::FETCH_ASSOC));

        if(empty($_POST))
        {
            $this->tpl .= '/tagEdit';
            $this->setBreadcrumbs(__FUNCTION__);
            exit;
        }

        $params = filter_input_array(INPUT_POST, [
            'title' => filterOptions(0,'full_special_chars','null_on_failure'),
            'color' => filterOptions(1,'regexp','null_on_failure',
                ['regexp'=>'/^[0-9a-f]{6}/i']),
            'icon' => filterOptions(0,'full_special_chars','null_on_failure')
        ]);

        $set = [];
        foreach($params as $field => $value)
        {
            if($value === null)
                unset($params[$field]);
            else
                $set[] = $field .' = :'. $field;
        }

        if(empty($params))
        {
            $this->flash[] = 'No changes made.';
            $this->index();
            exit;
        }

        $sql = 'UPDATE tags SET '.implode(',',$set).' WHERE id = '.(int)$id;

        if($this->_exec($sql,$params))
            $this->flash[] = 'Tag updated.';
    }
}
