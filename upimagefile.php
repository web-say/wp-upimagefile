<?php
/**
 * The WordPress Plugin Update Image Files.
 *
 *
 * @package   Upimagefile
 * @license   GPL-2.0+
 * @copyright 2020
 *
 * @wordpress-plugin
 * Plugin Name:       Update Image Files
 * Description:       Update Image Files to /wp-content/uploads/.
 * Version:           0.0.1
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */



  /**
  * Holds current database version
  * and used on plugin update to sync database tables
  */

  global $upimagefiles_db_version;
  $upimagefiles_db_version = '0.1'; // version changed from 0.1


  /**
  * register_activation_hook implementation
  *
  * will be called when user activates plugin first time
  * must create needed database tables
  */

  function upimagefiles_install()
  {
      global $wpdb;
      global $upimagefiles_db_version;

      $table_name = $wpdb->prefix . 'imagick'; // do not forget about tables prefix


      // sql to create table

      $sql = " CREATE TABLE " . $table_name . " (
        `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
        `file_name` varchar(500) NOT NULL,
        `file_size` varchar(100) NOT NULL,
        `file_type` varchar(20) NOT NULL,
        `created` int(11) NOT NULL DEFAULT '0',
        `update` int(11) NOT NULL DEFAULT '0',+
        `file_size_end` varchar(100) NOT NULL,
        `ready` tinyint(1) unsigned zerofill NOT NULL DEFAULT '0',
        PRIMARY KEY (`id`) USING BTREE,
        KEY `filename` (`file_name`) USING BTREE,
        KEY `created` (`created`) USING BTREE
        );";
      // we do not execute sql directly
      // we are calling dbDelta which cant migrate database
      require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

      dbDelta($sql);

      // save current database version for later use (on upgrade)
      add_option('upimagefiles_db_version', $upimagefiles_db_version);

      /**
          * [OPTIONAL] Updating to 0.1 version
       */

      $installed_ver = get_option('upimagefiles_db_version');

  }

  register_activation_hook(__FILE__, 'upimagefiles_install');


/**
 * Trick to update plugin database, see docs
 */

  function upimagefiles_update_db_check()
  {
     global $upimagefiles_db_version;
     if (get_site_option('upimagefiles_db_version') != $upimagefiles_db_version) {
        upimagefiles_install();
     }
  }

 add_action('plugins_loaded', 'upimagefiles_update_db_check');


 /** Uninstall Plugin **/

 function upimagefile_deactivation() {

 		global $wpdb;

 		$wpdb->query( "DROP TABLE IF EXISTS " .  $wpdb->prefix . "imagick" );

 		delete_option("upimagefile_db_version");

 }

 register_deactivation_hook( __FILE__, 'upimagefile_deactivation' );



  /**
  * admin_menu hook implementation, will add pages
  */

  function upimagefile_admin_menu()
  {
      add_menu_page(__('Upimagefile', 'upimagefilehome'), __('Upimagefile', 'upimagefilehome'), 'activate_plugins', 'upimagefilehome', 'upimagefile_home_page_handler');

      add_submenu_page('upimagefilehome', __('Run files', 'upimagefilerun'), __('Run', 'upimagefilerun'), 'activate_plugins', 'upimagefilerun', 'upimagefile_run_page_handler');

      add_submenu_page('upimagefilehome', __('Run scan dir', 'upimagefilescandir'), __('Run scan dir', 'upimagefilescandir'), 'activate_plugins', 'upimagefilescandir', 'upimagefile_run_scandir_page_handler');
  }

  add_action('admin_menu', 'upimagefile_admin_menu');


  /**
   * Home page handler
   */
  function upimagefile_home_page_handler() {

     global $wpdb;

     $table_name = $wpdb->prefix . 'imagick';

     $update =  $wpdb->get_var(" SELECT COUNT(id) FROM ". $table_name." WHERE ready = '1'");

     $all =  $wpdb->get_var(" SELECT COUNT(id) FROM ". $table_name."");

      ?>
      <div class="wrap">
        <div class="postbox-container">
          <h1 class="wp-heading-inline">Upadte Image</h1>
          <div id="dashboard-widgets" class="metabox-holder">
            <div id="postbox-container-1" class="postbox-container">
      	       <div id="side-sortables" class="meta-box-sortables ui-sortable">
                  <div id="dashboard_activity" class="postbox ">
                    <h2 class="title hndle ui-sortable-handle"><span>Upadte Image</span></h2>
                    <div class="inside">
                      <div id="activity-widget">
                        <div id="published-posts" class="activity-block">
                           <p>Upadte - <?php print_r($update); ?> ( <strong>cron:</strong> /?upimagefile=run )</p>
                           <p>All - <?php print_r($all); ?></p>
                        </div>
                      </div>
                      <p class="community-events-footer">
                  		    <a class="" href="/wp-admin/admin.php?page=upimagefilerun" target="_blank">Run upadte<span class="screen-reader-text">(откроется в новом окне)</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>
                          |
                          <a class="" href="/wp-admin/admin.php?page=upimagefilescandir" target="_blank">Run file list creation<span class="screen-reader-text">(откроется в новом окне)</span><span aria-hidden="true" class="dashicons dashicons-external"></span></a>
                    	</p>
                    </div>
                  </div>
                </div>
            </div>
          </div>
        </div>
      </div>
      <?php
  }


  /**
  * Form page handler checks is there some data posted and tries to save it
  * Also it renders basic wrapper in which we are callin meta box render
  */
  function upimagefile_run_page_handler()
  {

     if (isset($_REQUEST['update_image_files']) && $_REQUEST['update_image_files'] == '1') {

      upimagefile_run_files();

     }

      ?>
      <div class="wrap">
          <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>
          <h2>Run upadte file</h2>

          <form id="form" method="POST">
              <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
              <?php /* NOTICE: here we storing id to determine will be item added or updated */ ?>
              <input type="hidden" name="update_image_files" value="1"/>
              <input type="submit" value="Run" id="submit" class="button-primary" name="submit">
          </form>

      </div>
      <?php
  }



  /**
  * Form page handler checks is there some data posted and tries to save it
  * Also it renders basic wrapper in which we are callin meta box render
  */
  function upimagefile_run_scandir_page_handler()
  {

     if (isset($_REQUEST['update_image_files_sacn_dir']) && $_REQUEST['update_image_files_sacn_dir'] == '1') {

       $catalogImages = [];

       $dirObjects = get_home_path().'wp-content/uploads';

       echo "<div class='formlist'>";

       $list_dir = goThroughDir($dirObjects);

       echo "</div>";
     }


     global $wpdb;

     $table_name = $wpdb->prefix . 'imagick';

       ?>
      <div class="wrap">

        <div class="icon32 icon32-posts-post" id="icon-edit"><br></div>

        <h2>Run scan dir</h2>

        <?php $all =  $wpdb->get_var(" SELECT COUNT(id) FROM ". $table_name."");   ?>

        <p>All - <?php print_r($all); ?></p>

        <form id="form" method="POST">
            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce(basename(__FILE__))?>"/>
            <input type="hidden" name="update_image_files_sacn_dir" value="1"/>
            <input type="submit" value="Run scan dir" id="submit" class="button-primary" name="submit">
        </form>

      </div>

      <?php

      if(!empty($list_dir)){

        ?>
        <div class="wrap">

          <h2>Step 2</h2>

          <button onclick="mainUploadImageFile();"> Start 2 </button>

          <script>

            function mainUploadImageFile()	{

              var elem = jQuery('.item_list_path li.item:first');

              var path  = elem.data('path');

              if(path.length > 0) {

                var requestData = {
                  action: 'run_files_resize',
                  _wpnonce: '<?= wp_create_nonce('run_files_resize') ?>',
                  path: path,
                  my_other_params: {}
                };

                var url = '<?= admin_url('/admin-ajax.php?action=run_files_resize') ?>' + '&_wpnonce=<?= wp_create_nonce('run_files_resize') ?>';

                jQuery.post(url, requestData, function(response) {
                  // $.get OR $.post
                  console.log(response);

                  elem.remove();

                  setTimeout( function(){ mainUploadImageFile() }, 500);

                });

              }

            }

          </script>

        </div>

        <div class="item_list_path">

          <ul>

            <?php  returnListItemForm($list_dir);  ?>

          </ul>

        </div>

        <?php
      }

  }


  add_action('wp_ajax_run_files_resize', 'run_files_resize_via_ajax');

  add_action('wp_ajax_nopriv_run_files_resize', 'run_files_resize_via_ajax');

  function run_files_resize_via_ajax() {
    if (check_ajax_referer($_REQUEST['action']) !== 1 || wp_verify_nonce($_REQUEST['_wpnonce'], $_REQUEST['action']) !== 1) {
      wp_send_json_error([
        'message' => 'Ошибка! Запрос не разрешен.'
      ]);
    }

    header('Content-Type: application/json');

    $response = [
      'success' => true,
      'data' => [
        'html' => goThroughFile($_REQUEST['path']),
      ]
    ];

    echo json_encode($response);

    die();

  }



function returnListItemForm ($list_dir){

  if(!empty($list_dir)){

    foreach ($list_dir as $key => $val) {

      echo "<li class='item'data-path='".$val['name']."'>".$val['name'].'</li>';

      if(is_array($val[0])) {

        returnListItemForm ($val[0]);

      }

    }

  }

}

function goThroughFile($dirName){

  $dirObjectsClear = get_home_path();

  $dirObjects = scandir($dirObjectsClear.$dirName);

  global $wpdb;

  $table_name = $wpdb->prefix . 'imagick';

  $counter = 0;

  $extensions = ['gif', 'jpg', 'jpeg', 'png', 'bmp'];

  foreach ($dirObjects as $dirObject) {

    if ($dirObject == '.' || $dirObject == '..') {
        continue;
    }

    $file = new SplFileInfo($dirObject);

    $currentExt = strtolower($file->getExtension());

    $fullName = $dirObjectsClear.$dirName . '/' . $dirObject;


    if (!is_dir($fullName)) {

      if (!in_array($currentExt, $extensions)) {
          continue;
      }

      $name = str_replace($dirObjectsClear, "", $fullName);

      $item = $wpdb->get_row($wpdb->prepare(" SELECT * FROM $table_name WHERE  `file_name` = '".$name."' "), ARRAY_A);

      if(empty($item)) {


        $wpdb->insert( $table_name, array('file_name' => str_replace($dirObjectsClear, "", $fullName), 'file_size' => filesize ($fullName), 'file_type' => $currentExt, 'created' => time(), 'update'     => '0', 'ready' => '0' ) );

        $counter++;

      }

    }

  }

  return $counter;

}


  function goThroughDir($dirName) {

    $dirObjects = scandir($dirName);

    $dir = '';

    $dirObjectsClear = get_home_path();

    foreach ($dirObjects as $dirObject) {

      if ($dirObject == '.' || $dirObject == '..') {
          continue;
      }

      $file = new SplFileInfo($dirObject);

      $currentExt = strtolower($file->getExtension());

      $fullName = $dirName . '/' . $dirObject;

      if (is_dir($fullName)) {

        $name = str_replace($dirObjectsClear, "", $fullName);

        $dir [] =  ['name' => $name , goThroughDir($fullName)];

      }

    }

    return $dir;

  }

/**
 * Path to url add cron /?upimagefile=run
**/

function upimagefile_query_vars($vars) {
    $new_vars = array('upimagefile');
    $vars = $new_vars + $vars;
    return $vars;
}

add_filter('query_vars', 'upimagefile_query_vars');

function upimagefile_parse_request($wp) {

    if (array_key_exists('upimagefile', $wp->query_vars)  && $wp->query_vars['upimagefile'] == 'run')  {

      upimagefile_run_files();

    }
}

add_action('parse_request', 'upimagefile_parse_request');


/**
 * Run update image
**/

function upimagefile_run_files() {

  global $wpdb;

  $table_name = $wpdb->prefix . 'imagick'; // do not forget about tables prefix

  $items = $wpdb->get_results($wpdb->prepare(" SELECT * FROM $table_name WHERE  `ready` = '0' AND `file_type` NOT IN ('png','gif') LIMIT 10 "), ARRAY_A);

  $dirObjectsClear = $_SERVER['DOCUMENT_ROOT'].'/';//get_home_path();

  foreach ($items as $key => $value) {

    if($value['file_type'] == 'png'){

      //compress_png($dirObjectsClear.$item['file_name'],'75');

    } elseif($value['file_type'] == 'jpg' || $value['file_type'] == 'jpeg' ) {

      $size = compress_jpg($dirObjectsClear.$value['file_name'], '75');
      //$wpdb->update( $table_name, [ 'update'=>time(), 'file_size_end' => $size, 'ready' => '1' ], [ 'ID'=>$value['ID'] ] );
      $wpdb->query(" UPDATE $table_name SET `update` =  '".time()."' , `file_size_end` = '".$size."' , `ready` = '1' WHERE `id` = '".$value['id']."' ");

    }

  }

}


function compress_jpg($imagePath, $quality = 75) {

  $backgroundImagick = new \Imagick(realpath($imagePath));
  $imagick = new \Imagick();
  $imagick->setCompressionQuality($quality);
  $imagick->newPseudoImage(
      $backgroundImagick->getImageWidth(),
      $backgroundImagick->getImageHeight(),
      'canvas:white'
  );

  $imagick->compositeImage(
      $backgroundImagick,
      \Imagick::COMPOSITE_ATOP,
      0,
      0
  );

  $imagick->setFormat("jpg");
  //header("Content-Type: image/jpg");
  $size = strlen($imagick->getImageBlob());
  $imagick->writeImage($imagePath);
  //$imagick->clear();

  return $size;

}

function compress_png($path_to_png_file, $max_quality = 90) {

    if (!file_exists($path_to_png_file)) {
        throw new Exception("File does not exist: $path_to_png_file");
    }

    // guarantee that quality won't be worse than that.
    $min_quality = 60;

    // '-' makes it use stdout, required to save to $compressed_png_content variable
    // '<' makes it read from the given file path
    // escapeshellarg() makes this safe to use with any path
    $compressed_png_content = shell_exec("pngquant --quality=$min_quality-$max_quality - < ".escapeshellarg(    $path_to_png_file));

    if (!$compressed_png_content) {
        throw new Exception("Conversion to compressed PNG failed. Is pngquant 1.8+ installed on the server?");
    }

    return $compressed_png_content;
}
