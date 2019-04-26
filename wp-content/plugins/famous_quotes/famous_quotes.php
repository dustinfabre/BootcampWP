<?php
/*
* Plugin Name: Famous Quotes
* Description: A plugin for famous quotes maintenance.
*/

defined( 'ABSPATH' ) or die( 'Something went wrong..' );

function quote_admin_menu()
{
    add_menu_page(__('Famous Quotes Maintenance', 'api_quote'), __('Famous Quotes Maintenance', 'api_quote'), 'activate_plugins', 'quotes', 'quotes_page_handler');
    add_submenu_page('quotes', __('Famous Quotes Maintenance', 'api_quote'), __('Quotes', 'api_quote'), 'activate_plugins', 'quotes', 'quotes_page_handler');
    add_submenu_page('quotes', __('Add new', 'api_quote'), __('Add new', 'api_quote'), 'activate_plugins', 'quotes_form', 'quotes_form_page_handler');
}

add_action('admin_menu', 'quote_admin_menu');

function oauth()
{
    $curl = curl_init('http://dustin.bootcamp.architechlabs.com:8000/oauth/v2/token');
    $data = array(
        'client_id' => '1_5xdevo4qfdwkwgow4kww0sgokkkswkcsokkoc0ck44g08c4cko',
        'client_secret' => '2etr8lwbt1nos48ck0g4w848o0c0ccwsoogckg8gccss48w088',
        'grant_type' => 'password',
        'username' => 'dustin',
        'password' => 'bootcamp1234'
    );
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, false);
    $result = curl_exec($curl);
    $oauth = json_decode($result);
    return 'Authorization: Bearer ' . $oauth->access_token;
}

function callAPI($method, $url, $data)
{
    $curl = curl_init();
    $authorization = oauth();
    switch ($method){
        case "POST":
            curl_setopt($curl, CURLOPT_POST, 1);
                if ($data){
                    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
                }
            break;
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($data) {
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
            }		 					
            break;
        default:
            if ($data)
                $url = sprintf("%s?%s", $url, http_build_query($data));
    }

   // OPTIONS:
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        $authorization
    ));
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt ($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt ($curl, CURLOPT_SSL_VERIFYHOST, false);

   $result = curl_exec($curl);
   if(!$result){ die('Connection error...');}
   curl_close($curl);
   return $result;
}

function quotes_page_handler()
{
    ?>
    <style>
        table.dataTable thead th, table.dataTable thead td{
            border-bottom:1px solid rgba(17, 17, 17, .1) !important;
        }
        table.dataTable.no-footer{
            border-bottom:1px solid rgba(17, 17, 17, .1) !important;
        } 
    </style>
        <div class="wrap">
            <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
            <h2>Quotes 
                <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=quotes_form');?>">
                <?php _e('Add new', 'api_quote')?>
                </a>
            </h2>
            <?php echo $message; ?>
            <form id="contacts-table" method="POST">
                <input type="hidden" name="page" value="<?php echo $_REQUEST['page'] ?>"/>
                <table id="table_id" class="dataTable hover row-border">
                    <thead>
                        <tr>
                            <th>Quote</th>
                            <th>Name</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                        $url = callAPI('GET', 'http://dustin.bootcamp.architechlabs.com:8000/api/quote', false);
                        $data = json_decode($url, true);
                    // echo '<pre>';
                        //var_dump($data);
                        //echo '</pre>';
                        for ($x = 0; $x < count($data); $x++) {
                    ?>
                        <tr class="show_edit_delete_<?= $x ?>">
                            <td>
                                <?= $data[$x]['quote'] ?> 
                                <div class="edit_delete_<?= $x ?>" style="display:none;">
                                    <a href="?page=quotes_form&id=<?= $x ?>">Edit</a>
                                    <a href="?page=quotes&action=delete&id=<?= $x ?>" style="color:red;">Delete</a>
                                </div>
                            </td>
                            <td><?= $data[$x]['author']['name'] ?></td>
                        </tr>
                        <script type="text/javascript">
                            $(document).ready( function () {
                                $('.show_edit_delete_<?= $x ?>').mouseover(function(){
                                    $('.edit_delete_<?= $x ?>').show();
                                });
                                $('.show_edit_delete_<?= $x ?>').mouseout(function(){
                                    $('.edit_delete_<?= $x ?>').hide();
                                });
                            } );
                        </script>
                    <?php } ?>
                    </tbody>
                </table>
            </form>
        </div>
    <script type="text/javascript">
        $(document).ready( function () {
            $('#table_id').DataTable();
        } );
    </script>
<?php
}

function quotes_form_page_handler()
{
    if ( isset($_REQUEST['nonce']) && wp_verify_nonce($_REQUEST['nonce'], basename(__FILE__))) {
        
        $item = $_REQUEST;     

        $item_valid = validate_quotes($item);
        if ($item_valid === true) {
                $data = 
                    [
                        "quote" =>  filter_var($_REQUEST['quote'], FILTER_SANITIZE_STRING),
                        "name"  =>  filter_var($_REQUEST['name'], FILTER_SANITIZE_STRING)
                    ];
            if (!isset($_GET['id'])) {
                callAPI('POST', 'http://dustin.bootcamp.architechlabs.com:8000/api/quote', $data);
            } else {
                $url = callAPI('GET', 'http://dustin.bootcamp.architechlabs.com:8000/api/quote', false);
                $json_data = json_decode($url, true);
                callAPI('PUT', 'http://dustin.bootcamp.architechlabs.com:8000/api/quote'. $json_data[$_GET['id']]['id'], $data);
            }
                
        } else {
            $notice = $item_valid;
        }
    }

 add_meta_box('quotes_form_meta_box', __('Quote data', 'api_quote'), 'quotes_form_meta_box_handler', 'quote', 'normal', 'default');

?>
<div class="wrap">
    <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
    <h2><?php _e('Quotes', 'api_quote')?> 
        <a class="add-new-h2" href="<?php echo get_admin_url(get_current_blog_id(), 'admin.php?page=quotes');?>">
        <?php _e('back to list', 'api_quote')?>
        </a>
    </h2>

    <?php if (!empty($notice)): ?>
    <div id="notice" class="error"><p><?php echo $notice ?></p></div>
    <?php endif;?>

    <?php if (!empty($message)): ?>
    <div id="message" class="updated"><p><?php echo $message ?></p></div>
    <?php endif;?>

    <form id="form" method="POST">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
        <div class="metabox-holder" id="poststuff">
            <div id="post-body">
                <div id="post-body-content">
                    <?php do_meta_boxes('quote', 'normal', $item); ?>
                </div>
            </div>
        </div>
    </form>
</div>
<?php
}

function quotes_form_meta_box_handler()
{

?>
<tbody>
	<style>
        div.postbox{
            width: 40%; margin-left: 30px;
        }
	</style>
    <?php 
        $url = callAPI('GET', 'http://dustin.bootcamp.architechlabs.com:8000/api/quote', false);
        $data = json_decode($url, true);
    ?>
  
    <?php if(isset($_GET['id']) && !isset($data[$_GET['id']]['quote'])) { ?>
        <div id="notice" class="error"><p>Quote not found..</p></div>
    <?php } ?>
        <div class="formdata">		
            <form id="form" method="post">
                <label for="quote">Quote</label>
                <br>	
                <input id="quote" name="quote" type="text" style="width: 88.5%" value="<?php echo esc_attr($data[$_GET['id']]['quote'])?>"
                            required>
                <br> <br>
                    <label for="name">Name</label>
                <br>
                    <input id="name" name="name" type="text" style="width: 88.5%;" value="<?php echo esc_attr($data[$_GET['id']]['author']['name'])?>"
                            required>
                <br><br>   			
                <?php if(isset($_GET['id']) && !isset($data[$_GET['id']]['quote'])) { ?>
                    <input type="submit" value="<?php _e('Save', 'api_quote')?>" id="submit" class="button-primary" name="submit">
                <?php }else{ ?>
                <input type="submit" value="<?php _e('Save', 'api_quote')?>" id="submit" class="button-primary" name="submit">
                <?php } ?>
            </form>
        </div>
</tbody>
<script type="text/javascript">
    $("#name").on("keyup", function() {

        if ($(this).val().length >= 1) {
            $('.custom').fadeIn();
        } else {
            $('.custom').hide();
        }

        var value = $(this).val().toLowerCase();
            $("select option").filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });

        $('.custom select').click(function(){
            var selected = $(this).children("option:selected").val();
            $('#name').val(selected);
        });
    });
</script>
<?php
}

add_action( 'admin_enqueue_scripts', function() {
    wp_enqueue_script('handle', '//code.jquery.com/jquery-3.3.1.js');
    //wp_enqueue_style('bootstrapcss', '//cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.1.3/css/bootstrap.css');
    wp_enqueue_style('dataTablecss', '//cdn.datatables.net/1.10.19/css/jquery.dataTables.min.css');
    wp_enqueue_script('dataTablejs','//cdn.datatables.net/1.10.19/js/jquery.dataTables.min.js' );
    
});

function validate_quotes($item)
{
    $messages = array();

    if (empty($item['quote'])) $messages[] = __('Quotes is required', 'api_quote');
    if (empty($item['name'])) $messages[] = __('Name is required', 'api_quote');
 
    if (empty($messages)) return true;
    return implode('<br />', $messages);
}

function api_quote_languages()
{
    load_plugin_textdomain('api_quote', false, dirname(plugin_basename(__FILE__)));
}

add_action('init', 'api_quote_languages');