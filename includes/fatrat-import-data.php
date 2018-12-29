<?php
/**
 * Copyright (c) 2018 Fat Rat Collect . All rights reserved.
 * 胖鼠采集要做wordpress最好用的采集器.
 * 如果你觉得我写的还不错.可以去Github上 Star
 * 现在架子已经有了.欢迎大牛加入开发.一起丰富胖鼠的功能
 * Github: https://github.com/fbtopcn/fatratcollect
 * @Author: fbtopcn
 * @CreateTime: 2018年12月30日 02:24
 */

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

class FRC_Import_Data extends WP_List_Table
{

    protected $wpdb;
    protected $table_post;
    protected $table_blogs;
    protected $table_options;

    /** Class constructor */
    public function __construct()
    {

        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table_post = $wpdb->prefix . 'fr_post';
        $this->table_options = $wpdb->prefix . 'fr_options';
        $this->table_blogs = $wpdb->blogs;

        parent::__construct(
            array(
                'singular' => esc_html__('采集配置', 'Fat Rat Collect'),
                'plural' => esc_html__('采集配置', 'Fat Rat Collect'),
                'ajax' => false,
            )
        );
    }


    /**
     * Retrieve snippets data from the database
     *
     * @param int $per_page
     * @param int $page_number
     *
     * @return mixed
     */
    public static function get_snippets($per_page = 10, $page_number = 1, $customvar = 'all')
    {

        global $wpdb;
        $table_name = "{$wpdb->prefix}fr_post";
        $sql = "SELECT * FROM $table_name where `is_post` = '0' ";

        if ($customvar != 'all') {
            $sql .= " and `post_type` = '$customvar'";
        }

        if (!empty($_REQUEST['orderby'])) {
            $sql .= ' ORDER BY ' . esc_sql($_REQUEST['orderby']);
            $sql .= !empty($_REQUEST['order']) ? ' ' . esc_sql($_REQUEST['order']) : ' ASC';
        } else {
            $sql .= ' ORDER BY `id` DESC';
        }

        $sql .= " LIMIT $per_page";
        $sql .= ' OFFSET ' . ($page_number - 1) * $per_page;

        $result = $wpdb->get_results($sql, 'ARRAY_A');
        return $result;
    }

    /**
     * Delete a snipppet record.
     *
     * @param int $id snippet ID
     */
    public static function delete_snippet($id)
    {

        global $wpdb;
        $table_name = "{$wpdb->prefix}fr_options";

        $wpdb->delete(
            $table_name, array('id' => $id), array('%d')
        );
    }

    /**
     * Activate a snipppet record.
     *
     * @param int $id snippet ID
     */
    public static function activate_snippet($id)
    {

    }

    /**
     * Deactivate a snipppet record.
     *
     * @param int $id snippet ID
     */
    public static function deactivate_snippet($id)
    {

    }

    /**
     * Returns the count of records in the database.
     *
     * @return null|string
     */
    public static function record_count($customvar = 'all')
    {

        global $wpdb;
        $table_name = "{$wpdb->prefix}fr_post";
        $sql = "SELECT COUNT(*) FROM $table_name where `is_post` = 0 ";

        if ($customvar != 'all') {
            $sql .= " and post_type = '$customvar'";
        }

        return $wpdb->get_var($sql);
    }

    /** Text displayed when no snippet data is available */
    public function no_items()
    {
        esc_html_e('亲. 目前没有可发布的文章。', 'Fat Rat Collect');
    }

    /**
     * Render a column when no column specific method exist.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {

        switch ($column_name) {
            case 'id':
            case 'image' :
            case 'post_type' :
            case 'link' :
            case 'is_post' :
            case 'author' :
            case 'created' :
                return esc_html($item[$column_name]);
                break;
            case 'title':
                return "<a href='{$item['link']}' target='_blank'>" . esc_html(mb_substr($item[$column_name], 0, 40)) . "</a><br /><span class='preview-article' value='{$item['id']}'><a href='#'>预览</a></span> | <span class='publish-articles' value='{$item['id']}'><a href='#'>发布</a></span>";
                break;
            case 'content':
                return esc_html('....');
                break;
        }
    }

    /**
     * Render the bulk edit checkbox
     *
     * @param array $item
     *
     * @return string
     */
    function column_cb($item)
    {
        return sprintf(
            '<input type="checkbox" name="snippets[]" value="%s" />', $item['id']
        );
    }

    /**
     * Method for name column
     *
     * @param array $item an array of DB data
     *
     * @return string
     */
    function column_name($item)
    {

    }

    /**
     *  Associative array of columns
     *
     * @return array
     */
    function get_columns()
    {
        $columns = array(
            'cb' => '<input type="checkbox" />',
            'id' => esc_html__('ID', 'Fat Rat Collect'),
            'title' => esc_html__('标题', 'Fat Rat Collect'),
            'author' => esc_html__('作者', 'Fat Rat Collect'),
            'created' => esc_html__('创建时间', 'Fat Rat Collect'),
        );

        return $columns;
    }

    /**
     * Columns to make sortable.
     *
     * @return array
     */
    public function get_sortable_columns()
    {

        return array(
            'id' => array('id', true),
            'collect_type' => array('collect_type', true),
        );
    }

    /**
     * Returns an associative array containing the bulk action
     *
     * @return array
     */
    public function get_bulk_actions()
    {

        return array(
            'bulk-delete' => esc_html__('暂不开放', 'Fat Rat Collect'),
        );
    }

    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items()
    {

        $columns = $this->get_columns();
        $hidden = array();
        $sortable = $this->get_sortable_columns();

        $customvar = (isset($_REQUEST['customvar']) ? sanitize_text_field($_REQUEST['customvar']) : 'all');
        $this->_column_headers = array($columns, $hidden, $sortable);

        /** Process bulk action */
        $this->process_bulk_action();
        $this->views();
        $per_page = $this->get_items_per_page('snippets_per_page', 10);
        $current_page = $this->get_pagenum();
        $total_items = self::record_count();

        $this->set_pagination_args(array(
            'total_items' => $total_items,
            'per_page' => $per_page,
        ));

        $this->items = self::get_snippets($per_page, $current_page, $customvar);
    }


    public function get_views()
    {
        $views = array();
        $current = (!empty($_REQUEST['customvar']) ? sanitize_text_field($_REQUEST['customvar']) : 'all');

        //All link
        $class = 'all' === $current ? ' class="current"' : '';
        $all_url = remove_query_arg('customvar');
        $views['all'] = "<a href='{$all_url}' {$class} >" . esc_html__('全部', 'Fat Rat Collect') . ' (' . $this->record_count() . ')</a>';

        $options = $this->wpdb->get_results("select `id`, `collect_name` from $this->table_options", ARRAY_A);
        if (!empty($options)) {
            foreach ($options as $option) {
                $tmp_url = add_query_arg('customvar', $option['id']);
                $class = ($option['id'] === $current ? ' class="current"' : '');
                $views[$option['id']] = "<a href='{$tmp_url}' {$class} >" . esc_html__($option['collect_name'], 'Fat Rat Collect') . ' (' . $this->record_count($option['id']) . ')</a>';

            }
        }

        return $views;
    }


    public function process_bulk_action()
    {

    }


    public function system_publish_article(){
        $article_id = !empty($_REQUEST['article_id']) ? sanitize_text_field($_REQUEST['article_id']) : 0;
        if ($article_id === 0) {
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '文章ID错误'];
        }

        $article = $this->wpdb->get_row(
            "select * from $this->table_post where `id` =  " . $article_id,
            ARRAY_A
        );
        if (empty($article)){
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '亲,没找到这篇文章!'];
        }

        if ($this->article_to_storage($article)) {
            return ['code' => FRC_Api_Error::SUCCESS, 'msg' => 'Success.'];
        }

        return ['code' => FRC_Api_Error::FAIL, 'msg' => 'System Error.'];
    }


    public function system_preview_article(){
        $article_id = !empty($_REQUEST['article_id']) ? sanitize_text_field($_REQUEST['article_id']) : 0;
        if ($article_id === 0) {
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '文章ID错误'];
        }

        $article = $this->wpdb->get_row(
            "select * from $this->table_post where `id` =  " . $article_id,
            ARRAY_A
        );
        if (empty($article)){
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '亲,没找到这篇文章!'];
        }

        $preview_id = $this->article_to_storage($article, ['post_status' => 'draft']);

        return ['code' => FRC_Api_Error::SUCCESS, 'msg' => 'ok.', 'result' => ['preview_url' => get_permalink($preview_id)]];
    }


    public function system_import_article(){
        $count = !empty($_REQUEST['collect_count']) ? sanitize_text_field($_REQUEST['collect_count']) : 10;

        if ($count > 10){
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '数量超了. 回头考虑改改发布数量这个限制.'];
        }

        $articles = $this->wpdb->get_results(
            "select * from $this->table_post where `is_post` = 0 limit $count",
            ARRAY_A
        );
        if (empty($articles)){
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '没库存文章了, 亲!'];
        }

        collect($articles)->map(function ($article) {
            $this->article_to_storage($article);
        });

        return ['code' => FRC_Api_Error::SUCCESS, 'msg' => 'Success.'];
    }


    public function system_import_group_article(){
        if (!is_multisite()) {
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '你的站点不是站群.不能用这个功能. 站群的意思是一份代码支持N个网站!'];
        }

        $blogs = $this->wpdb->get_results(
            "select `blog_id` from $this->table_blogs",
            ARRAY_A
        );
        if (empty($blogs)) {
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '你的站群有点毛病! 不能用此功能. '];
        }

        $articles = $this->wpdb->get_results(
            "select * from $this->table_post where `is_post` = 0 limit " . count($blogs),
            ARRAY_A
        );
        if (empty($articles)){
            return ['code' => FRC_Api_Error::FAIL, 'msg' => '没库存文章了, 亲!'];
        }

        collect($articles)->map(function ($article, $key) use ($blogs) {
            if ($key != 0) {
                $this->wpdb->set_prefix($GLOBALS['table_prefix'] . $blogs[$key]['blog_id'] . '_');
            }
            $this->article_to_storage($article);
        });

        // 恢复表前缀 TODO 不恢复可能会影响什么。。。？？
        $this->wpdb->set_prefix($GLOBALS['table_prefix']);

        return ['code' => FRC_Api_Error::SUCCESS, 'msg' => 'Success.'];
    }


    private function article_to_storage($article, $release_config = [])
    {
        if (empty($release_config)){
            $release_config['post_status'] = 'publish';
        }
        $post = array(
            'post_title' => $article['title'],
            'post_name' => md5($article['title']),
            'post_content' => $article['content'],
            'post_status' => $release_config['post_status'],
            'post_author' => get_current_user_id(),
            'post_category' => array(1),
            'tags_input' => '',
            'post_type' => 'post',
        );

        // 草稿
        if ($post['post_status'] == 'draft'){
            return wp_insert_post($post);
        }

        // 发布
        if ($post['post_status'] == 'publish'){
            if ($article_id = wp_insert_post($post)) {
                $this->wpdb->update($this->table_post, ['is_post' => 1], ['id' => $article['id']], ['%d'], ['%d']);
                return $article_id;
            }
        }


        return false;
    }
}

/**
 * FRC_Import_Data (入口)
 */
function frc_import_data_interface() {

    $action_func = !empty($_REQUEST['action_func']) ? sanitize_text_field($_REQUEST['action_func']) : '';
    if (empty($action_func)){
        wp_send_json(['code' => 5001, 'msg' => 'Parameter error!']);
        wp_die();
    }

    $result = null;
    $action_func = 'system_'.$action_func;
    $frc_spider = new FRC_Import_Data();
    method_exists($frc_spider, $action_func) && $result = (new FRC_Import_Data())->$action_func();
    if ($result != null){
        wp_send_json($result);
        wp_die();
    }
    wp_send_json(['code' => 5002, 'result' => $result, 'msg' => 'Action there is no func! or Func is error!']);
    wp_die();
}
add_action( 'wp_ajax_frc_import_data_interface', 'frc_import_data_interface' );


/**
 * 定时发布 cron
 */
if (!wp_next_scheduled('frc_cron_publish_articles_hook')) {
    wp_schedule_event(time(), 'twohourly', 'frc_cron_publish_articles_hook');
}

function frc_publish_articles_timing_task()
{
    (new FRC_Import_Data())->system_import_group_article();
}
add_action('frc_cron_publish_articles_hook', 'frc_publish_articles_timing_task');
//wp_clear_scheduled_hook('frc_cron_publish_articles_hook');


function frc_import_data()
{
    $snippet_obj = new FRC_Import_Data();
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('数据发布中心', 'Fat Rat Collect') ?></h1>
        <input type="hidden" hidden id="request_url" value="<?php echo admin_url('admin-ajax.php'); ?>">
        <input type="hidden" hidden id="success_redirect_url" value="<?php echo admin_url('admin.php?page=frc-import-data'); ?>">

        <ul class="nav nav-tabs">
            <li class="active"><a href="#home" data-toggle="tab">* _ *</a></li>
            <li><a href="#singlesite" data-toggle="tab">单站点发布</a></li>
            <li><a href="#multiplesites" data-toggle="tab">多站点发布</a></li>
        </ul>
        <div class="tab-content">
            <div class="tab-pane fade in active" id="home">
                <form method="post">
                    <?php
                    $snippet_obj->prepare_items();
                    $snippet_obj->display();
                    ?>
                </form>
            </div>

            <div class="tab-pane fade" id="singlesite"><p></p>

                <p>Todo: 单站点发布</p>
                <p>Todo: 未开启自动发布功能.考虑开放给大家</p>
                <p>Todo: 发布 文章的 ID 正序</p>
                <p>Todo: 目前限制，最多一次发布10篇文章</p>
                发布篇数<input name="import-articles-count-button" type="text" value="3" />
                <input id="import-articles-button" type="button" class="button button-primary" value="发布">
            </div>

            <div class="tab-pane fade" id="multiplesites"><p></p>

                <p>Todo: 多站点发布</p>
                <p>Todo: 发布 文章的 ID 正序</p>
                <p>Todo: 站群定时发布已经自动开启。每两小时站群中每个站点自动发布一篇文章，非站群站点 不会自动发布</p>
                <p>Todo: 点击下方可手动执行一次站群发布，不影响计时任务</p>
                <p>
                    <input id="import-articles-button_group" type="button" class="button button-primary"
                           value="给站群每个站点发布一篇文章">
                </p>
            </div>
        </div>
    </div>
    <?php
}