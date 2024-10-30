<?php
/*
Plugin Name: Blake.lt wordpress integration
Plugin URI: http://blake.lt/widgets/wordpress
Description: Blake.lt integration with your wordpress blog
Author: Aurelijus Valeiša
Version: 1.2.1
Author URI: http://aurelijus.eu
*/
/*  Copyright 2009  Aurelijus Valeiša  (email : aurelijus@astdev.lt)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
define('BLAKELT_TABLE_NAME', "blakelt_messages");
if (! class_exists('blake')) {
    class blake
    {
        /**
         * @var string   The name of the database table used by the plugin
         */
        var $db_table_name = '';
		var $plugin_db_version = '1.0';
        /**
         * PHP 4 Compatible Constructor
         */
        function blake ()
        {
            $this->__construct();
        }
        /**
         * PHP 5 Constructor
         */
        function __construct ()
        {
            global $wpdb;
            load_plugin_textdomain('blakelt-wordpress-integration', PLUGINDIR.'/'.dirname(plugin_basename(__FILE__)).'/blakelt-includes');            
            add_action("admin_menu", array(&$this , "add_admin_pages"));
            register_activation_hook(__FILE__, array(&$this , "install_on_activation"));
            add_action("plugins_loaded", array(&$this , "register_widget_blakelt_widget"));
            $this->db_table_name = $wpdb->prefix . BLAKELT_TABLE_NAME;

        }
        function add_admin_pages ()
        {
            add_submenu_page('options-general.php', "Blake.lt", "Blake.lt", 10, __FILE__, array(&$this , "output_sub_admin_page_0"));
        }
        /**
         * Outputs the HTML for the admin sub page.
         */
        function output_sub_admin_page_0 ()
        {
            ?>
<div class="wrap">
<div id="icon-options-general" class="icon32"><br />
</div>
<h2><?php
            _e('Blake.lt plugin settings', 'blakelt-wordpress-integration');
            ?></h2>
<p><?php
            _e('Because this is the first version of Blake.lt plugin, we do not have any configuration options, yet.', 'blakelt-wordpress-integration');
            ?></p>
<h3><?php
            _e('Blake.lt plugin usage', 'blakelt-wordpress-integration');
            ?></h3>
<p><?php
            _e('You can use this plugin as a widget. To do that go to "Appearance" > "Widgets" and add Blake.lt widget.', 'blakelt-wordpress-integration');
            ?></p>
<p><?php
            _e('Also you can use this plugin as a standalone function. Anywhere in your theme files you can use this:', 'blakelt-wordpress-integration')?></p>
<pre>
					&lt;?php if (function_exists('blake_list_messages')): ?&gt;		
						&lt;h2&gt;<?php
            _e('Updates', 'blakelt-wordpress-integration');
            ?>&lt;/h2&gt;
						&lt;?php echo blake_list_messages('username=YOUR_USERNAME'); ?&gt;
					&lt;?php endif; ?&gt;
				</pre>
<p><?php
            _e('Replace YOUR_USERNAME with your Blake.lt username. Of course you can set, how many messages do you want to display, or set whether you want to show your username, date or replies. Like the example below:', 'blakelt-wordpress-integration');
            ?></p>
<pre>
					&lt;?php if (function_exists('blake_list_messages')): ?&gt;		
						&lt;h2&gt;<?php
            _e('Updates', 'blakelt-wordpress-integration');
            ?>&lt;/h2&gt;
						&lt;?php echo blake_list_messages('username=YOUR_USERNAME&show_username=0&show_replies=1&count=1'); ?&gt;
					&lt;?php endif; ?&gt;
				</pre>
<p><?php
            _e('Using the example above, you will show 1 message with replies and no username included.', 'blakelt-wordpress-integration');
            ?></p>
<p><?php
            _e('Function <em>blake_list_messages</em> retrieves already formatted messages with &lt;ul&gt; and &lt;li&gt; tags. If you want to get only an array of messages, use <em>blake_get_messages</em> instead.', 'blakelt-wordpress-integration');
            ?></p>
<p><?php
            _e('Full documentation you can find at <a href="http://blakelt.pbwiki.com/Wordpress-priedas" title="Blake.lt wiki" target="_blank">Blake.lt Wiki</a>.', 'blakelt-wordpress-integration');
            ?></p>
</div>
<?php
        }
        /**
         * Creates or updates the database table, and adds a database table version number to the WordPress options.
         */
        function install_on_activation ()
        {
            global $wpdb;
            $installed_ver = get_option("blakelt_db_version");
            if ($installed_ver !== false && $installed_ver < '1.0')
            {             
                delete_option('blakelt_db_version');
                
                $blake_updates = get_option('blakelt_updates');
                delete_option('blakelt_updates');
                update_option('blake_updates', $blake_updates);
                
                $blake_options = get_option('blakelt_options');
                delete_option('blakelt_options');
                update_option('blake_options', $blake_options);
            }
            if ($installed_ver === false || $installed_ver < '1.0') {
                $wpdb->query("TRUNCATE TABLE " . $this->db_table_name);   
                $sql = "CREATE TABLE " . $this->db_table_name . " (
					  `id` mediumint(9) NOT NULL auto_increment,
					  `message_id` mediumint(9) NOT NULL,
					  `username` varchar(30) NOT NULL,
					  `message` varchar(160) default NULL,
					  `in_reply_to` mediumint(9) NOT NULL,
					  `created_on` datetime default '0000-00-00 00:00:00',
					  PRIMARY KEY  (`id`),
					  UNIQUE KEY `message_id` (`message_id`),
					  KEY `username` (`username`)
					);";
				
		      	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		      	dbDelta($sql);
                //add a database version number for future upgrade purposes
                update_option("blake_db_version", $this->plugin_db_version);
            }
        }
        /**
         * Registers the widget and the widget control for use
         */
        function register_widget_blakelt_widget ($args)
        {
            register_sidebar_widget("Blake.lt widget", array(&$this , "widget_blakelt_widget"));
            register_widget_control("Blake.lt widget", array(&$this , "widget_blakelt_widget_control"));
        }
        /**
         * Contains the widget logic
         */
        function widget_blakelt_widget ($args)
        {
            extract($args);
            $options = get_option('widget_blakelt_widget');
            $title = apply_filters('widget_title', $options['title']);
            echo $before_widget;
            ?>
			<?php
            echo $before_title . $title . $after_title;
            ?>
            
			<?php $this->list_messages($options); ?>
			
			<?php
            echo $after_widget;
            ?>
			<?php
        }
        /**
         * Contains the widget control html
         */
        function widget_blakelt_widget_control ()
        {
            $options = $newoptions = get_option('widget_blakelt_widget');
            if (isset($_POST['blakelt-widget-submit'])) {
                $newoptions['title'] = strip_tags(stripslashes($_POST['blakelt-widget-title']));
                $newoptions['username'] = strip_tags(stripslashes($_POST['blakelt-widget-username']));
                $newoptions['count'] = intval($_POST['blakelt-widget-count']);
                $newoptions['show_replies'] = isset($_POST['blakelt-widget-show_replies']);
                $newoptions['show_username'] = isset($_POST['blakelt-widget-show_username']);
                $newoptions['show_date'] = isset($_POST['blakelt-widget-show_date']);
                $newoptions['count'] = ($newoptions['count'] > 20 || $newoptions['count'] < 0) ? '5' : $newoptions['count'];
            }
            if ($options != $newoptions) {
                $options = $newoptions;
                update_option('widget_blakelt_widget', $options);
            }
            $title = attribute_escape($options['title']);
            $username = attribute_escape($options['username']);
            $count = attribute_escape($options['count']);
            $show_replies = (bool) $options['show_replies'];
            $show_username = (bool) $options['show_username'];
            $show_date = (bool) $options['show_date'];
            ?>
<p><label for="blakelt-widget-title"><?php
            _e('Title:', 'blakelt-wordpress-integration');
            ?> <input
	class="widefat" id="blakelt-widget-title" name="blakelt-widget-title"
	type="text" value="<?php
            echo $title;
            ?>" /></label></p>

<p><label for="blakelt-widget-username"><?php
            _e('Username:', 'blakelt-wordpress-integration');
            ?> <input
	type="text" value="<?php
            echo $username;
            ?>"
	name="blakelt-widget-username" id="blakelt-widget-username"
	class="widefat" /></label> <br />
<small><?php
            _e('Your Blake.lt username', 'blakelt-wordpress-integration');
            ?></small></p>
<p><label for="blakelt-widget-count"><?php
            _e('Number of updates to show:', 'blakelt-wordpress-integration');
            ?> <input
	style="width: 27px; text-align: center;" id="blakelt-widget-count"
	name="blakelt-widget-count" type="text" value="<?php
            echo $count;
            ?>" /></label>
<br />
<small><?php
            _e('(at most 20)', 'blakelt-wordpress-integration');
            ?></small></p>
            <p>
            <label for="blakelt-widget-show_replies">
					<input type="checkbox" class="checkbox" id="blakelt-widget-show_replies" name="blakelt-widget-show_replies" <?php checked( $show_replies, true ); ?> />
					<?php _e( 'Show your replies' , 'blakelt-wordpress-integration'); ?>
				</label></p>
            <p>
            <label for="blakelt-widget-show_username">
					<input type="checkbox" class="checkbox" id="blakelt-widget-show_username" name="blakelt-widget-show_username" <?php checked( $show_username, true ); ?> />
					<?php _e( 'Show message author' , 'blakelt-wordpress-integration'); ?>
				</label></p>
            <p>
            <label for="blakelt-widget-show_date">
					<input type="checkbox" class="checkbox" id="blakelt-widget-show_date" name="blakelt-widget-show_date" <?php checked( $show_date, true ); ?> />
					<?php _e( 'Show message date' , 'blakelt-wordpress-integration'); ?>
				</label></p>
<input type="hidden" id="blakelt-widget-submit"
	name="blakelt-widget-submit" value="1" />
<?php
        }
        /**
         * Fetch the latest messages from Blake.lt
         *
         * @param string $username
         */
        function update_messages($username) {
            global $wpdb;
            if (empty($username))
                die('Please set the username.');            
                
            $options = get_option('blake_options');
            $updates_options = get_option('blake_updates');
            
            $options['interval'] = empty($options['interval']) ? 2 : $options['interval'];
            $expiration = time() - $options['interval'] * 60;
            
            if (! isset($updates_options[$username]) || $updates_options[$username] < $expiration) {
                $updates_options[$username] = time();            
                require_once 'blakelt-includes/jsonphp.php';
                $json = new Services_JSON();
                $input = file_get_contents('http://blake.lt/' . $username . '/output.json');
                $jsonResults = $json->decode($input);
                if ($jsonResults !== null)
                {
                    $wpdb->hide_errors();
                    foreach ($jsonResults as $item) {
                        $message['message_id'] = $item->Message->message_id;
                        $message['message'] = $item->Message->message;
                        $message['username'] = $item->Account->username;
                        $message['created_on'] = $item->Message->created_on;
                        $message['in_reply_to'] = $item->Message->in_reply_to;
                        $wpdb->query($wpdb->prepare("INSERT INTO " . $wpdb->prefix . BLAKELT_TABLE_NAME . "
        				( message_id, message, username, in_reply_to, created_on )
        				VALUES ( %d, %s, %s, %d, %s )", $message['message_id'], $message['message'], $message['username'], $message['in_reply_to'], $message['created_on']));
                    }
                    update_option('blake_updates', $updates_options);
                }
            }
        }
        /**
         * Retrieve list of message objects
         *
 		 * @uses $wpdb
 		 * @uses wp_parse_args() Merges the defaults with those defined by $args and allows for strings.
 		 * 
         * @param string|array $args Optional. Change the defaults retrieving messages.
         * @return array List of messages.
         */
        function get_messages ( $args = '' )
        {
            global $wpdb;
            $defaults = array(
    			'username' => '', 'count' => 5,
    			'show_replies' => 0, 'show_username' => 1,
    			'show_date' => 1, 'date_type' => 1
    	    );
            $args = wp_parse_args( $args, $defaults );    	
            $args['count'] = intval($args['count']);
            extract($args, EXTR_SKIP);
            $where = '';
            $this->update_messages($username);
            $select = '`message_id`, `message`';
            
            if ($show_username == 1)
                $select .= ', `username`';
                
            if ($show_date == 1)
                $select .= ', `created_on`';
                
            if ($show_replies == 1)
                $select .= ', `in_reply_to`';
            else
                $where .= ' AND in_reply_to = \'0\' ';
           
            $messages = $wpdb->get_results($wpdb->prepare("SELECT $select FROM " . $wpdb->prefix . BLAKELT_TABLE_NAME . "
    		WHERE username = '%s' $where ORDER BY `created_on` DESC LIMIT %d", $username, $count));            
            $messages = $messages !== null ? $messages : array();
            if ($show_date == 1)
            {
                $messagesCount = count($messages);
                for($i = 0; $i < $messagesCount; $i++)
                {
                    if ($date_type == 1)
                        $messages[$i]->created_on_text = $this->date_to_string($messages[$i]->created_on);
                    else
                        $messages[$i]->created_on_text = $messages[$i]->created_on;
                    
                }
            }
            return $messages;
        }
        /**
         * Retrieve/show list of message objects
         *
 		 * @uses $wpdb
 		 * @uses wp_parse_args() Merges the defaults with those defined by $args and allows for strings.
 		 * 
         * @param string|array $args Optional. Change the defaults retrieving messages.
         * @return null|string HTML content only if 'echo' argument is 0.
         */
        function list_messages ($args)
        {
            $defaults = array(
    			'echo' => 1
    	    );    
            $args = wp_parse_args( $args, $defaults ); 	    
    	    $messages = $this->get_messages($args);
    	    
            $output = '<ul class="blake-messages">';
            foreach ($messages as $message) {
                $output .= '<li>';
                
                if (isset($message->username))
                    $output .= '<span class="blake-message-author"><a href="http://blake.lt/' . $message->username . '" title="' . $message->username . '">' . $message->username . '</a></span>';
                
                $output .= ' <span class="blake-message">' . $message->message . '</span>';
                
                if (isset($message->created_on))
                    $output .= ' <span class="blake-message-entrymeta"><a href="http://blake.lt/message/' . $message->message_id . '/" title="' . htmlspecialchars($message->message) . '">' . $message->created_on_text . '</a></span>';
                
                $output .= '</li>';
            }
            $output .= '</ul>';
            if ($args['echo'] == 0)
            {
                return $output;
            } else {
                echo $output;
                return null;
            }
            
        }
        /**
         * Formats string from datetime format
         *
         * @param unknown_type $date
         * @return unknown
         */
        function date_to_string ($date)
        {
            $time = strtotime($date);
            $timeNow = time();
            $timeDiff = $timeNow - $time;
            $str = '';
            if ($timeDiff < 60) {
                $str .= 'prieš ' . $timeDiff . ' sek.';
            } else 
                if ($timeDiff > 60 && $timeDiff < 3600) {
                    $str .= 'prieš ' . round($timeDiff / 60) . ' min.';
                } else 
                    if ($timeDiff > 3600 && $timeDiff < 86400) {
                        $str .= 'prieš ' . round($timeDiff / 3600) . ' val.';
                    } else {
                        $str .= 'prieš ' . round($timeDiff / 86400) . ' d.';
                    }
            return $str;
        }
    }
}
//instantiate the class
if (class_exists('blake')) {
    $blake = new blake();
}

// Functions for templates
if (!function_exists('blake_get_messages')) {
    function blake_get_messages ( $args = '' )
    {
        global $blake; //$wpdb
        return $blake->get_messages($args);
    }
}
if (! function_exists('blake_list_messages')) {
    function blake_list_messages ($args)
    {        
        global $blake;
        return $blake->list_messages($args); 
    }
}
